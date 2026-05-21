<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('module')) {
            $this->merge(['module' => strtolower(trim($this->input('module')))]);
        }
        if ($this->filled('slug')) {
            $this->merge(['slug' => strtolower(trim($this->input('slug')))]);
        }
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id ?? $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:150'],
            // Slug is editable but middleware everywhere references the old
            // slug — admins should change at their own risk. We still enforce
            // format + uniqueness.
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:[\-\.][a-z0-9]+)*$/',
                Rule::unique('permissions', 'slug')
                    ->ignore($permissionId)
                    ->whereNull('deleted_at'),
            ],
            'module'      => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique'  => 'A permission with this slug already exists.',
            'slug.regex'   => 'Slug must be lowercase, e.g. "products.edit" or "barcodes.print".',
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
