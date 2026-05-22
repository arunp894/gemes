<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Treat empty strings on optional fields as null so DB stores NULL
        // instead of "" — keeps queries and casting predictable.
        $nullable = [
            'supplier_code', 'company_name', 'invoice_prefix', 'email', 'alternate_phone',
            'gst_number', 'tax_number', 'website',
            'country', 'state', 'city', 'zip_code', 'address',
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

        // Default the money fields to 0 when blank to satisfy `numeric|min:0`.
        if ($this->input('opening_balance') === '' || $this->input('opening_balance') === null) {
            $this->merge(['opening_balance' => 0]);
        }
        if ($this->input('credit_limit') === '' || $this->input('credit_limit') === null) {
            $this->merge(['credit_limit' => 0]);
        }
    }

    public function rules(): array
    {
        return [
            // Optional on input — the model will auto-generate (SUP-0001) when blank.
            'supplier_code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_\-]+$/',
                Rule::unique('suppliers', 'supplier_code')->whereNull('deleted_at'),
            ],

            'name'            => ['required', 'string', 'max:191'],
            'company_name'    => ['nullable', 'string', 'max:191'],

            'invoice_prefix'  => [
                'nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/i',
            ],

            'email'           => ['nullable', 'email', 'max:191'],
            'phone'           => ['required', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],

            'gst_number'      => ['nullable', 'string', 'max:50'],
            'tax_number'      => ['nullable', 'string', 'max:50'],
            'website'         => ['nullable', 'url', 'max:191'],

            'country'         => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'city'            => ['nullable', 'string', 'max:100'],
            'zip_code'        => ['nullable', 'string', 'max:20'],
            'address'         => ['nullable', 'string', 'max:1000'],

            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'credit_limit'    => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],

            'status'          => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_code.unique' => 'Supplier code already exists.',
            'supplier_code.regex'  => 'Supplier code may only contain letters, numbers, hyphens, and underscores.',
            'email.email'          => 'Please enter a valid email address.',
            'website.url'          => 'Please enter a valid website URL (e.g. https://example.com).',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_code'   => 'Supplier Code',
            'name'            => 'Contact Name',
            'company_name'    => 'Company Name',
            'email'           => 'Email',
            'phone'           => 'Phone',
            'alternate_phone' => 'Alternate Phone',
            'gst_number'      => 'GST Number',
            'tax_number'      => 'Tax Number',
            'website'         => 'Website',
            'country'         => 'Country',
            'state'           => 'State',
            'city'            => 'City',
            'zip_code'        => 'Zip Code',
            'address'         => 'Address',
            'opening_balance' => 'Opening Balance',
            'credit_limit'    => 'Credit Limit',
            'status'          => 'Status',
        ];
    }
}
