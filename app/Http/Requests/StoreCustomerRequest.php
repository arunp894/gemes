<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'customer_code', 'company_name', 'email', 'alternate_phone',
            'gst_number', 'pan_number',
            'address_line1', 'address_line2',
            'city', 'state', 'country', 'zip_code',
            'notes',
        ];
        $merged = [];
        foreach ($nullable as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merged[$field] = null;
            }
        }
        if ($merged) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'customer_code' => [
                'nullable', 'string', 'max:50',
                'regex:/^[A-Za-z0-9_\-]+$/',
                Rule::unique('customers', 'customer_code')->whereNull('deleted_at'),
            ],
            'name'          => ['required', 'string', 'max:191'],
            'company_name'  => ['nullable', 'string', 'max:191'],
            'customer_type' => ['required', 'string', Rule::in(array_keys(Customer::TYPES))],

            'email'           => ['nullable', 'email', 'max:191'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],

            'gst_number' => ['nullable', 'string', 'max:50'],
            'pan_number' => ['nullable', 'string', 'max:20'],

            'address_line1' => ['nullable', 'string', 'max:191'],
            'address_line2' => ['nullable', 'string', 'max:191'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'country'       => ['nullable', 'string', 'max:100'],
            'zip_code'      => ['nullable', 'string', 'max:20'],

            'status' => ['required', 'boolean'],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_code.unique' => 'Customer code already exists.',
            'customer_code.regex'  => 'Customer code may only contain letters, numbers, hyphens, and underscores.',
            'customer_type.in'     => 'Please pick a valid customer type.',
            'email.email'          => 'Please enter a valid email address.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_code'   => 'Customer Code',
            'name'            => 'Name',
            'company_name'    => 'Company Name',
            'customer_type'   => 'Customer Type',
            'phone'           => 'Phone',
            'gst_number'      => 'GST Number',
            'pan_number'      => 'PAN Number',
            'address_line1'   => 'Address Line 1',
            'address_line2'   => 'Address Line 2',
            'zip_code'        => 'Zip Code',
            'status'          => 'Status',
        ];
    }
}
