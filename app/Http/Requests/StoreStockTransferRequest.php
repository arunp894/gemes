<?php

namespace App\Http\Requests;

use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_date'    => ['required', 'date'],
            'from_location_id' => [
                'required', 'integer',
                Rule::exists('locations', 'id')->whereNull('deleted_at'),
                'different:to_location_id',
            ],
            'to_location_id'   => [
                'required', 'integer',
                Rule::exists('locations', 'id')->whereNull('deleted_at'),
                'different:from_location_id',
            ],
            'status'           => ['required', Rule::in([
                StockTransfer::STATUS_DRAFT,
                StockTransfer::STATUS_IN_TRANSIT,
            ])],
            'note'             => ['nullable', 'string', 'max:2000'],

            'lines'                              => ['required', 'array', 'min:1'],
            'lines.*.purchase_product_id'        => ['required', 'integer', Rule::exists('purchase_products', 'id')->whereNull('deleted_at')],
            'lines.*.qty'                        => ['required', 'integer', 'min:1'],
            'lines.*.to_rack_id'                 => ['nullable', 'integer', Rule::exists('racks', 'id')->whereNull('deleted_at')],
            'lines.*.notes'                      => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_location_id.different' => 'Source and destination must be different locations.',
            'to_location_id.different'   => 'Source and destination must be different locations.',
            'lines.required'             => 'Add at least one piece to transfer.',
        ];
    }

    public function attributes(): array
    {
        return [
            'from_location_id' => 'Source Location',
            'to_location_id'   => 'Destination Location',
        ];
    }
}
