<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pages.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug') && $this->input('slug') === '') {
            $this->merge(['slug' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'slug'  => [
                'nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')->whereNull('deleted_at'),
            ],
            'content'          => ['required', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Slug may only contain lowercase letters, numbers, and hyphens (e.g. shipping-policy).',
            'slug.unique' => 'That slug is already in use by another page.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'   => 'Title',
            'slug'    => 'Slug',
            'content' => 'Content',
            'meta_title'       => 'Meta Title',
            'meta_description' => 'Meta Description',
        ];
    }
}
