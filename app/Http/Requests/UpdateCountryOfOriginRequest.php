<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryOfOriginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('country-origins.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
        ]);
    }

    public function rules(): array
    {
        $originId = $this->route('countryOrigin')?->id ?? $this->route('countryOrigin');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('countries_of_origin', 'name')->ignore($originId)->whereNull('deleted_at'),
            ],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status'        => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This country is already in the list.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'          => 'Name',
            'display_order' => 'Display Order',
            'status'        => 'Status',
        ];
    }
}
