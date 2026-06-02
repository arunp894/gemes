<?php

namespace App\Http\Requests;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update flow:
 *   - Drafts accept the FULL payload (same rules as create)
 *   - Non-drafts only accept note + shipping_charge (anything else is
 *     ignored by SaleService::update); we still apply lenient rules so
 *     a misclick on the edit form doesn't 422 the whole request.
 */
class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullFks = ['channel_id', 'salesperson_id'];
        $merged  = [];
        foreach ($nullFks as $f) {
            if ($this->input($f) === '' || $this->input($f) === '0') {
                $merged[$f] = null;
            }
        }
        if ($this->input('shipping_charge') === '' || $this->input('shipping_charge') === null) {
            $merged['shipping_charge'] = 0;
        }
        if ($merged) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'sale_date'       => ['sometimes', 'required', 'date'],
            'customer_id'     => ['sometimes', 'required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'location_id'     => ['sometimes', 'required', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'channel_id'      => ['nullable', 'integer', Rule::exists('channels', 'id')->whereNull('deleted_at')],
            'salesperson_id'  => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],

            'tax_type'        => ['sometimes', 'required', Rule::in(Sale::TAX_TYPES)],
            'shipping_charge' => ['nullable', 'numeric', 'min:0'],
            'note'            => ['nullable', 'string', 'max:2000'],

            'lines'                       => ['sometimes', 'array', 'min:1'],
            'lines.*.product_id'          => ['required_with:lines', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'lines.*.purchase_product_id' => ['nullable', 'integer', Rule::exists('purchase_products', 'id')->whereNull('deleted_at')],
            'lines.*.barcode'             => ['nullable', 'string', 'max:100'],
            'lines.*.qty'                 => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.unit_price'          => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.tax_percent'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_percent'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes'               => ['nullable', 'string', 'max:500'],

            'payments'                    => ['nullable', 'array'],
            'payments.*.payment_date'     => ['required_with:payments.*.amount', 'date'],
            'payments.*.amount'           => ['required_with:payments.*.payment_date', 'numeric'],
            'payments.*.payment_method'   => ['required_with:payments.*.amount', Rule::in(array_keys(SalePayment::METHODS))],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
            'payments.*.notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
