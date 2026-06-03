<?php

namespace App\Http\Requests;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Empty-string FKs → null so exists rules don't break.
        $nullFks = ['salesperson_id'];
        $merged  = [];
        foreach ($nullFks as $f) {
            if ($this->input($f) === '' || $this->input($f) === '0') {
                $merged[$f] = null;
            }
        }

        // Default shipping to 0 if blank.
        if ($this->input('shipping_charge') === '' || $this->input('shipping_charge') === null) {
            $merged['shipping_charge'] = 0;
        }

        if ($merged) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        // Allowed statuses on create — completed is allowed when the
        // customer paid in full at the terminal.
        $allowedStatuses = [
            Sale::STATUS_DRAFT,
            Sale::STATUS_POSTED,
            Sale::STATUS_COMPLETED,
        ];

        return [
            'sale_date'       => ['required', 'date'],
            'customer_id'     => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'location_id'     => ['required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'salesperson_id'  => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],

            'tax_type'        => ['required', Rule::in(Sale::TAX_TYPES)],
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string', 'max:2000'],
            'status'          => ['required', Rule::in($allowedStatuses)],

            // ── Lines (at least one) ─────────────────────────────────
            'lines'                            => ['required', 'array', 'min:1'],
            'lines.*.product_id'               => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'lines.*.purchase_product_id'      => ['nullable', 'integer', Rule::exists('purchase_products', 'id')->whereNull('deleted_at')],
            'lines.*.barcode'                  => ['nullable', 'string', 'max:100'],
            'lines.*.qty'                      => ['required', 'integer', 'min:1'],
            'lines.*.unit_price'               => ['required', 'numeric', 'min:0'],
            'lines.*.tax_percent'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_percent'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes'                    => ['nullable', 'string', 'max:500'],

            // ── Payments (optional on draft; should sum to grand on completed) ─
            'payments'                         => ['nullable', 'array'],
            'payments.*.payment_date'          => ['required_with:payments.*.amount', 'date'],
            'payments.*.amount'                => ['required_with:payments.*.payment_date', 'numeric'],
            'payments.*.payment_method'        => ['required_with:payments.*.amount', Rule::in(array_keys(SalePayment::METHODS))],
            'payments.*.reference_number'      => ['nullable', 'string', 'max:100'],
            'payments.*.notes'                 => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required'                => 'Add at least one product to the sale.',
            'lines.min'                     => 'Add at least one product to the sale.',
            'lines.*.product_id.required'   => 'Product is required on every line.',
            'lines.*.qty.min'               => 'Quantity must be at least 1.',
            'lines.*.unit_price.required'   => 'Unit price is required on every line.',
            'payments.*.payment_method.in'  => 'Pick a valid payment method.',
            'customer_id.exists'            => 'Selected customer is not valid.',
            'location_id.exists'            => 'Selected location is not valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sale_date'       => 'Sale Date',
            'customer_id'     => 'Customer',
            'location_id'     => 'Location',
            'salesperson_id'  => 'Salesperson',
            'tax_type'        => 'Tax Type',
            'shipping_charge' => 'Shipping Charge',
        ];
    }
}
