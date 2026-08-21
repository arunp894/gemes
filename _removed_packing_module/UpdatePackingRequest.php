<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('packings.edit') ?? false;
    }

    /**
     * Outputs are no longer part of the payload -- every selected source
     * automatically becomes one product (see
     * PackingService::syncOutputsFromSources()).
     */
    public function rules(): array
    {
        return [
            'packing_date'    => ['required', 'date'],
            'location_id'     => ['required', 'integer', 'exists:locations,id'],
            'note'            => ['nullable', 'string'],

            'sources'                          => ['required', 'array', 'min:1'],
            'sources.*.purchase_product_id'    => ['required', 'integer', 'exists:purchase_products,id'],
            'sources.*.qty_taken'              => ['required', 'integer', 'min:1'],
            // Per-row -- see PackingService::syncSources() for defaulting.
            'sources.*.website_enabled'        => ['nullable', 'boolean'],
            'sources.*.website_price'          => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sources.required' => 'Select at least one raw piece to pack.',
        ];
    }
}
