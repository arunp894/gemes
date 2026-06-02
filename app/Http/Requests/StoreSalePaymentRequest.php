<?php

namespace App\Http\Requests;

use App\Models\SalePayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_date'     => ['required', 'date'],
            'amount'           => ['required', 'numeric', 'not_in:0'],
            'payment_method'   => ['required', Rule::in(array_keys(SalePayment::METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.not_in'        => 'Payment amount cannot be zero.',
            'payment_method.in'    => 'Pick a valid payment method.',
        ];
    }
}
