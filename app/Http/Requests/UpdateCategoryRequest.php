<?php

namespace App\Http\Requests;

use App\Models\Category;
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
        if ($this->has('parent_id') && $this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }

        // Coerce the gemstone checkbox to a boolean so the rule accepts it
        // whether the form sends '1', 'true', or omits it entirely.
        $this->merge([
            'is_gemstone' => $this->boolean('is_gemstone'),
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
            // parent_id: nullable. Must reference an active TOP-LEVEL category
            // OTHER than this one (no self-parenting). If THIS category has
            // children, it cannot itself become a child (would create 3 levels).
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at')
                    ->whereNull('parent_id')
                    ->where('status', 1)
                    ->where(function ($q) use ($categoryId) {
                        $q->where('id', '!=', $categoryId);
                    }),
                function ($attribute, $value, $fail) use ($categoryId) {
                    if (! $value || ! $categoryId) {
                        return;
                    }
                    if (Category::where('parent_id', $categoryId)->exists()) {
                        $fail('This category has subcategories, so it cannot itself become a subcategory.');
                    }
                },
            ],
            'description'   => ['nullable', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'status'        => ['required', 'boolean'],
            'is_gemstone'   => ['nullable', 'boolean'],
            'image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_image'  => ['nullable', 'boolean'],
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
            'is_gemstone'   => 'Gemstone Category',
            'image'         => 'Category Image',
        ];
    }
}
