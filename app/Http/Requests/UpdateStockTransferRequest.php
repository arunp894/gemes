<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Updating a transfer is only allowed while it's still a draft. The
 * controller enforces that with abort_unless($transfer->isEditable());
 * the rules here mirror the create flow so the same client form works.
 */
class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_date'    => ['sometimes', 'required', 'date'],
            'from_location_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('locations', 'id')->whereNull('deleted_at'),
                'different:to_location_id',
            ],
            'to_location_id'   => [
                'sometimes', 'required', 'integer',
                Rule::exists('locations', 'id')->whereNull('deleted_at'),
                'different:from_location_id',
            ],
            'note'             => ['nullable', 'string', 'max:2000'],

            'lines'                              => ['sometimes', 'array', 'min:1'],
            'lines.*.purchase_product_id'        => ['required_with:lines', 'integer', Rule::exists('purchase_products', 'id')->whereNull('deleted_at')],
            'lines.*.qty'                        => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.to_rack_id'                 => ['nullable', 'integer', Rule::exists('racks', 'id')->whereNull('deleted_at')],
            'lines.*.notes'                      => ['nullable', 'string', 'max:500'],
        ];
    }
}
