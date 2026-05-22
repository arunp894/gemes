<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('racks.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => (bool) $this->input('status', true),
        ]);
    }

    public function rules(): array
    {
        $rackId = $this->route('rack')?->id;

        return [
            'name'        => ['required', 'string', 'max:100'],
            'location'    => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', 'boolean'],
            // code is immutable on edit — silently ignored if posted.
        ];
    }
}
