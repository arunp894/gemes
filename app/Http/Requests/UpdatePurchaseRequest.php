<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use App\Models\PurchaseLine;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchases.edit') ?? false;
    }

    public function rules(): array
    {
        /** @var Purchase $purchase */
        $purchase = $this->route('purchase');

        // Posted/cancelled purchases can only edit the lightweight fields.
        if ($purchase && ! $purchase->isDraft()) {
            return [
                'note'        => ['nullable', 'string'],
                'paid_amount' => ['nullable', 'numeric', 'min:0'],
            ];
        }

        return [
            'purchase_date' => ['required', 'date'],
            'location_id'   => ['required', 'integer', 'exists:locations,id'],
            'tax_type'      => ['required', 'in:' . implode(',', Purchase::TAX_TYPES)],
            'note'          => ['nullable', 'string'],
            'paid_amount'   => ['nullable', 'numeric', 'min:0'],

            'lines'                       => ['required', 'array', 'min:1'],
            'lines.*.product_id'          => ['required', 'integer', 'exists:products,id'],
            'lines.*.type'                => ['required', 'in:' . implode(',', PurchaseLine::TYPES)],
            'lines.*.package_name'        => ['nullable', 'string', 'max:50'],
            'lines.*.package_qty'         => ['required', 'integer', 'min:1'],
            'lines.*.unit_contains'       => ['nullable', 'integer', 'min:1'],
            'lines.*.remarks'             => ['nullable', 'string'],

            'lines.*.rows'                       => ['required', 'array', 'min:1'],
            'lines.*.rows.*.qty'                 => ['required', 'integer', 'min:0'],
            'lines.*.rows.*.carat_weight'        => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
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
}
