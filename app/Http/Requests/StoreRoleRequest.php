<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // is_super comes from a checkbox — coerce to bool.
        $this->merge([
            'is_super' => $this->boolean('is_super'),
        ]);

        // Auto-slugify if slug not supplied: lowercase, dashes, alnum only.
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->input('name')),
            ]);
        }

        // Permission IDs come from checkboxes — normalize to int array.
        $permIds = $this->input('permission_ids', []);
        if (is_string($permIds)) {
            $permIds = array_filter(array_map('trim', explode(',', $permIds)));
        }
        $this->merge([
            'permission_ids' => array_values(array_filter(
                array_map('intval', (array) $permIds),
                fn ($v) => $v > 0,
            )),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('roles', 'slug')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_super'    => ['required', 'boolean'],

            // Permissions optional — a super role doesn't need any, and an
            // empty non-super role is allowed (effectively no access yet).
            'permission_ids'   => ['nullable', 'array'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'A role with this slug already exists.',
            'slug.regex'  => 'Slug may only contain lowercase letters, numbers, and dashes.',
            'permission_ids.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'           => 'Role Name',
            'slug'           => 'Role Slug',
            'is_super'       => 'Super Role',
            'permission_ids' => 'Permissions',
        ];
    }
}
