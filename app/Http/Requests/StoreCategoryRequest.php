<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize an empty-string parent_id (from the modal/select) to null.
        if ($this->has('parent_id') && $this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categories', 'name')->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('categories', 'code')->whereNull('deleted_at'),
            ],
            // parent_id: optional. Must reference an active TOP-LEVEL category
            // (so we keep the 2-level structure described in the spec).
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at')
                    ->whereNull('parent_id')
                    ->where('status', 1),
            ],
            'description'   => ['nullable', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'status'        => ['required', 'boolean'],
            'image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'      => 'Category name already exists.',
            'code.unique'      => 'Category code already exists.',
            'code.regex'       => 'Category code may only contain letters, numbers, and underscores (no spaces).',
            'parent_id.exists' => 'The selected parent category is invalid or inactive.',
            'image.max'        => 'The image must not be larger than 2 MB.',
            'image.mimes'      => 'The image must be a JPG or PNG file.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'          => 'Category Name',
            'code'          => 'Category Code',
            'parent_id'     => 'Parent Category',
            'display_order' => 'Display Order',
            'image'         => 'Category Image',
        ];
    }
}
