<?php

namespace App\Http\Requests;

use App\Models\Rack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('racks.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code'   => $this->input('code')   ? strtoupper(trim($this->input('code'))) : null,
            'status' => (bool) $this->input('status', true),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('racks', 'code')->whereNull('deleted_at'),
            ],
            'name'        => ['required', 'string', 'max:100'],
            'location'    => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', 'boolean'],
        ];
    }
}
