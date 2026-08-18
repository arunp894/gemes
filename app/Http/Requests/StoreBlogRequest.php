<?php

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('blogs.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['slug', 'excerpt', 'meta_title', 'meta_description'];
        $merged   = [];
        foreach ($nullable as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merged[$field] = null;
            }
        }
        if ($merged) {
            $this->merge($merged);
        }

        $this->merge([
            'status' => $this->boolean('status'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:191'],
            'slug'    => [
                'nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blogs', 'slug')->whereNull('deleted_at'),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status'  => ['required', 'boolean'],
            'image'   => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Slug may only contain lowercase letters, numbers, and hyphens (e.g. my-post-title).',
            'slug.unique' => 'That slug is already in use by another post.',
            'image.image' => 'The featured image must be an image file.',
            'image.mimes' => 'Accepted image types: JPEG, PNG, WebP.',
            'image.max'   => 'Featured image must not exceed 4 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'   => 'Title',
            'slug'    => 'Slug',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'meta_title'       => 'Meta Title',
            'meta_description' => 'Meta Description',
            'status'  => 'Status',
            'image'   => 'Featured Image',
        ];
    }
}
