<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // is_active comes from a switch (1/0 or absent); coerce to bool.
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);

        // Normalize role_ids: accept array or comma-separated list, drop empties.
        $roles = $this->input('role_ids', []);
        if (is_string($roles)) {
            $roles = array_filter(array_map('trim', explode(',', $roles)));
        }
        $this->merge([
            'role_ids' => array_values(array_filter(
                array_map('intval', (array) $roles),
                fn ($v) => $v > 0,
            )),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email:rfc',
                'max:191',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_active' => ['required', 'boolean'],

            // Multi-role assignment. At least one role required so the user
            // has *some* defined access; pure no-role users are essentially
            // useless in this RBAC model.
            'role_ids'   => ['required', 'array', 'min:1'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'    => 'A user with this email already exists.',
            'role_ids.required' => 'Assign at least one role to the user.',
            'role_ids.min'    => 'Assign at least one role to the user.',
            'role_ids.*.exists' => 'One or more selected roles are invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'Full Name',
            'email'    => 'Email Address',
            'password' => 'Password',
            'role_ids' => 'Roles',
        ];
    }
}
