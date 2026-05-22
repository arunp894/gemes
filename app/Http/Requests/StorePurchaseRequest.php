<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use App\Models\PurchaseLine;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchases.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id'   => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'tax_type'      => ['required', 'in:' . implode(',', Purchase::TAX_TYPES)],
            'note'          => ['nullable', 'string'],
            'paid_amount'   => ['nullable', 'numeric', 'min:0'],
            'status'        => ['nullable', 'in:' . Purchase::STATUS_DRAFT . ',' . Purchase::STATUS_POSTED],

            'lines'                       => ['required', 'array', 'min:1'],
            'lines.*.product_id'          => ['required', 'integer', 'exists:products,id'],
            'lines.*.type'                => ['required', 'in:' . implode(',', PurchaseLine::TYPES)],
            'lines.*.package_name'        => ['nullable', 'string', 'max:50'],
            'lines.*.package_qty'         => ['required', 'integer', 'min:1'],
            'lines.*.unit_contains'       => ['nullable', 'integer', 'min:1'],
            'lines.*.remarks'             => ['nullable', 'string'],

            'lines.*.rows'                       => ['required', 'array', 'min:1'],
            'lines.*.rows.*.qty'                 => ['required', 'integer', 'min:0'],
            'lines.*.rows.*.barcode'             => ['nullable', 'string', 'max:100'],
            'lines.*.rows.*.rack_id'             => ['nullable', 'integer', 'exists:racks,id'],
            'lines.*.rows.*.serial_number'       => ['nullable', 'string', 'max:100'],
            'lines.*.rows.*.price'               => ['required', 'numeric', 'min:0'],
            'lines.*.rows.*.tax_percent'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.rows.*.discount_percent'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.rows.*.expiry_date'         => ['nullable', 'date'],
            'lines.*.rows.*.manufacture_date'    => ['nullable', 'date', 'before_or_equal:lines.*.rows.*.expiry_date'],
            'lines.*.rows.*.remarks'             => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required'             => 'Add at least one product to the purchase.',
            'lines.*.rows.required'      => 'Each product line needs at least one inventory row.',
            'lines.*.rows.*.qty.min'     => 'Quantity must be zero or more.',
            'lines.*.rows.*.price.min'   => 'Price cannot be negative.',
        ];
    }
}
