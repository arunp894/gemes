<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:50', Rule::unique('channels', 'name')->whereNull('deleted_at')],
            'code'          => ['required', 'string', 'max:30', Rule::unique('channels', 'code')->whereNull('deleted_at')],
            'icon'          => ['nullable', 'string', 'max:50'],
            'status'        => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalise code to lowercase slug
        if ($this->input('code')) {
            $this->merge(['code' => strtolower(preg_replace('/[^a-z0-9_-]/i', '', $this->input('code')))]);
        }
        if (is_null($this->input('display_order')) || $this->input('display_order') === '') {
            $this->merge(['display_order' => 0]);
        }
        if (is_null($this->input('status'))) {
            $this->merge(['status' => true]);
        }
    }

    public function attributes(): array
    {
        return [
            'name'          => 'Channel Name',
            'code'          => 'Channel Code',
            'display_order' => 'Display Order',
        ];
    }
}
