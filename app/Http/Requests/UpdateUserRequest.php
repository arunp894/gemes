<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);

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

        // Normalize location_ids.
        $locations = $this->input('location_ids', []);
        if (is_string($locations)) {
            $locations = array_filter(array_map('trim', explode(',', $locations)));
        }
        $this->merge([
            'location_ids' => array_values(array_filter(
                array_map('intval', (array) $locations),
                fn ($v) => $v > 0,
            )),
        ]);
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email:rfc',
                'max:191',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->whereNull('deleted_at'),
            ],
            // Password optional on update — only validate when provided.
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => ['required', 'boolean'],
            'location_ids'   => ['nullable', 'array'],
            'location_ids.*' => [
                'integer',
                Rule::exists('locations', 'id')->whereNull('deleted_at')->where('status', true),
            ],

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
            'role_ids.required' => 'A user must have at least one role.',
            'role_ids.min'    => 'A user must have at least one role.',
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
