<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEbayTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'   => ['required', 'integer', 'exists:categories,id'],
            'title'         => ['required', 'string', 'max:80'],
            'status'        => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
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
            'category_id'   => 'Stone',
            'title'         => 'eBay Title',
            'display_order' => 'Display Order',
        ];
    }
}
