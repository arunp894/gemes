<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Coerce checkboxes to booleans so the rule accepts '1'/'true'/omitted.
        // show_on_frontend defaults to true when omitted — see
        // StoreCategoryRequest for why.
        $this->merge([
            'is_gemstone'      => $this->boolean('is_gemstone'),
            'show_on_frontend' => $this->boolean('show_on_frontend', true),
        ]);
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categories', 'name')
                    ->ignore($categoryId)
                    ->whereNull('deleted_at'),
            ],
            // Per spec Section 2.3: "All fields except Category Code can be edited after creation."
            // We still accept and validate code if posted, but the form leaves it read-only.
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('categories', 'code')
                    ->ignore($categoryId)
                    ->whereNull('deleted_at'),
            ],
            // parent_id removed — categories are a flat, single-level list.
            'description'   => ['nullable', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'status'           => ['required', 'boolean'],
            'is_gemstone'      => ['nullable', 'boolean'],
            'show_on_frontend' => ['nullable', 'boolean'],
            'image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_image'     => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'      => 'Category name already exists.',
            'code.unique'      => 'Category code already exists.',
            'code.regex'       => 'Category code may only contain letters, numbers, and underscores (no spaces).',
            'image.max'        => 'The image must not be larger than 2 MB.',
            'image.mimes'      => 'The image must be a JPG or PNG file.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'             => 'Category Name',
            'code'             => 'Category Code',
            'display_order'    => 'Display Order',
            'is_gemstone'      => 'Gemstone Category',
            'show_on_frontend' => 'Show on Frontend',
            'image'            => 'Category Image',
        ];
    }
}
