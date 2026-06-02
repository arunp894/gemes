<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'description',
            'address_line1', 'address_line2',
            'city', 'state', 'country', 'zip_code',
            'phone', 'email',
            'latitude', 'longitude',
            'notes',
        ];
        $merged = [];
        foreach ($nullable as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merged[$field] = null;
            }
        }
        if ($this->input('manager_id') === '' || $this->input('manager_id') === '0') {
            $merged['manager_id'] = null;
        }
        if ($merged) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        // Route-model binding gives us the Location instance; fall back to raw id.
        $locationId = $this->route('location')?->id ?? $this->route('location');

        return [
            // Code is immutable in the UI (read-only) but tolerated on input
            // as long as it doesn't collide with another row.
            'location_code' => [
                'sometimes', 'required', 'string', 'max:50',
                'regex:/^[A-Za-z0-9_\-]+$/',
                Rule::unique('locations', 'location_code')
                    ->ignore($locationId)
                    ->whereNull('deleted_at'),
            ],

            'name'        => ['required', 'string', 'max:191'],
            'type'        => ['required', 'string', Rule::in(array_keys(Location::TYPES))],
            'description' => ['nullable', 'string', 'max:2000'],

            'manager_id'  => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],

            'address_line1' => ['nullable', 'string', 'max:191'],
            'address_line2' => ['nullable', 'string', 'max:191'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'country'       => ['nullable', 'string', 'max:100'],
            'zip_code'      => ['nullable', 'string', 'max:20'],

            'phone'         => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:191'],

            'latitude'      => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'     => ['nullable', 'numeric', 'between:-180,180'],

            'is_default'    => ['required', 'boolean'],
            'status'        => ['required', 'boolean'],

            'notes'         => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_code.unique' => 'Location code already exists.',
            'location_code.regex'  => 'Location code may only contain letters, numbers, hyphens, and underscores.',
            'type.in'              => 'Please pick a valid location type.',
            'manager_id.exists'    => 'Selected manager is not a valid user.',
            'email.email'          => 'Please enter a valid email address.',
            'latitude.between'     => 'Latitude must be between -90 and 90.',
            'longitude.between'    => 'Longitude must be between -180 and 180.',
        ];
    }

    public function attributes(): array
    {
        return [
            'location_code' => 'Location Code',
            'name'          => 'Name',
            'type'          => 'Type',
            'manager_id'    => 'Manager',
            'address_line1' => 'Address Line 1',
            'address_line2' => 'Address Line 2',
            'zip_code'      => 'Zip Code',
            'is_default'    => 'Default Location',
            'status'        => 'Status',
        ];
    }
}
