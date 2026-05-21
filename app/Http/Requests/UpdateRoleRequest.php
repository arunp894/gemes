<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_super' => $this->boolean('is_super'),
        ]);

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
        $roleId = $this->route('role')?->id ?? $this->route('role');

        return [
            'name' => ['required', 'string', 'max:100'],
            // Slug is immutable after creation — kept in the form as read-only.
            // We still accept it `sometimes` so a stale submission doesn't fail.
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('roles', 'slug')
                    ->ignore($roleId)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_super'    => ['required', 'boolean'],

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
