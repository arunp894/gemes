<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Lowercase module + slug; let the user type whatever, normalize here.
        if ($this->filled('module')) {
            $this->merge(['module' => strtolower(trim($this->input('module')))]);
        }
        if ($this->filled('slug')) {
            $this->merge(['slug' => strtolower(trim($this->input('slug')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            // Slug convention: "module.action" — lowercase alnum, dot, dash.
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:[\-\.][a-z0-9]+)*$/',
                Rule::unique('permissions', 'slug')->whereNull('deleted_at'),
            ],
            'module'      => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'A permission with this slug already exists.',
            'slug.regex'  => 'Slug must be lowercase, e.g. "products.edit" or "barcodes.print".',
            'module.regex' => 'Module name must be lowercase letters, numbers, or dashes.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'   => 'Permission Name',
            'slug'   => 'Permission Slug',
            'module' => 'Module',
        ];
    }
}
