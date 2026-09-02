<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'location_id'   => ['required', 'integer', 'exists:locations,id'],
            'purchase_date' => ['required', 'date'],
            'tax_type'      => ['required', 'in:' . implode(',', Purchase::TAX_TYPES)],
            'note'          => ['nullable', 'string'],
            'status'        => ['nullable', 'in:' . Purchase::STATUS_DRAFT . ',' . Purchase::STATUS_POSTED],

            // ── Payments (optional; multiple rows, like a sale) ─────
            'payments'                    => ['nullable', 'array'],
            'payments.*.payment_date'     => ['required_with:payments.*.amount', 'date'],
            'payments.*.amount'           => ['required_with:payments.*.payment_date', 'numeric'],
            'payments.*.payment_method'   => ['required_with:payments.*.amount', Rule::in(array_keys(PurchasePayment::METHODS))],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
            'payments.*.notes'            => ['nullable', 'string', 'max:500'],

            'lines'               => ['required', 'array', 'min:1'],
            // Present (and matched against the purchase's own lines) only
            // when editing; always absent/null on a fresh create.
            'lines.*.id'          => ['nullable', 'integer'],

            // Product-creation template — see PurchaseService::syncLines().
            'lines.*.category_id'       => [
                'required', 'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at')->where('status', 1),
            ],
            'lines.*.title'              => ['nullable', 'string', 'max:200'],
            'lines.*.short_description'  => ['nullable', 'string', 'max:500'],
            'lines.*.full_description'   => ['nullable', 'string'],
            'lines.*.country_of_origin_id' => ['nullable', 'integer', Rule::exists('countries_of_origin', 'id')->whereNull('deleted_at')],
            'lines.*.notes_tags'         => ['nullable', 'string', 'max:1000'],
            'lines.*.website_price'      => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'lines.*.website_enabled'    => ['nullable', 'boolean'],

            // Gemstone fields are conditionally required in withValidator().
            'lines.*.carat_weight'  => ['nullable', 'numeric', 'min:0.001', 'max:99999.999'],
            'lines.*.stone_type'    => ['nullable', 'string', Rule::in(Product::STONE_TYPES)],
            'lines.*.colour_grade'  => ['nullable', 'string', 'max:100'],
            'lines.*.clarity_grade' => ['nullable', 'string', Rule::in(Product::CLARITY_GRADES)],
            'lines.*.cut_shape'     => ['nullable', 'string', Rule::in(Product::CUT_SHAPES)],
            'lines.*.treatment'     => ['nullable', 'string', Rule::in(Product::TREATMENTS)],
            'lines.*.stone_description' => ['nullable', 'string'],

            'lines.*.type'         => ['required', 'in:' . implode(',', PurchaseLine::TYPES)],
            'lines.*.package_name' => ['nullable', 'string', 'max:50'],
            // Row count for 'box' lines; ignored/forced to 1 server-side
            // for 'piece' lines. See PurchaseService::syncLines().
            'lines.*.package_qty'  => ['required', 'integer', 'min:1'],
            'lines.*.remarks'      => ['nullable', 'string'],

            'lines.*.rows'                    => ['required', 'array', 'min:1'],
            'lines.*.rows.*.id'               => ['nullable', 'integer'],
            'lines.*.rows.*.qty'              => ['required', 'integer', 'min:0'],
            'lines.*.rows.*.carat_weight'     => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
            'lines.*.rows.*.barcode'          => ['nullable', 'string', 'max:100'],
            'lines.*.rows.*.rack_id'          => ['nullable', 'integer', 'exists:racks,id'],
            'lines.*.rows.*.serial_number'    => ['nullable', 'string', 'max:100'],
            'lines.*.rows.*.price'            => ['required', 'numeric', 'min:0'],
            'lines.*.rows.*.website_price'    => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'lines.*.rows.*.website_enabled'  => ['nullable', 'boolean'],
            'lines.*.rows.*.expiry_date'      => ['nullable', 'date'],
            'lines.*.rows.*.manufacture_date' => ['nullable', 'date', 'before_or_equal:lines.*.rows.*.expiry_date'],
            'lines.*.rows.*.remarks'          => ['nullable', 'string'],
        ];
    }

    /**
     * Cross-field validation: a line's category must actually be mapped to
     * the chosen supplier (when that supplier has any mapping at all — see
     * Supplier::categories() docblock). Gemstone grading fields (carat,
     * stone type, treatment, etc.) are NOT collected on the purchase form
     * and so are never required here — they're set later by editing the
     * individual Product once it exists, same as photos.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $lines = $this->input('lines', []);
            if (! is_array($lines)) {
                return;
            }

            $supplier = Supplier::find($this->input('supplier_id'));
            // A supplier with nothing mapped yet is unrestricted — see
            // Supplier::categories() docblock. Only enforce the check once
            // someone has actually mapped at least one category.
            $supplierCategoryIds = $supplier ? $supplier->categories()->pluck('categories.id')->all() : [];

            foreach ($lines as $i => $line) {
                $categoryId = $line['category_id'] ?? null;
                if ($categoryId && $supplierCategoryIds && ! in_array((int) $categoryId, $supplierCategoryIds, true)) {
                    $v->errors()->add("lines.{$i}.category_id", 'This category is not mapped to the selected supplier.');
                }

                // Website Price is required per row once that row is
                // listed for sale online — mirrors the exact line->row
                // fallback PurchaseService::syncLines() uses to resolve
                // each row's effective website_enabled/website_price, so
                // this check can never disagree with what actually gets
                // saved to the Product.
                $lineWebsiteEnabled = (bool) ($line['website_enabled'] ?? false);
                $lineWebsitePrice   = $line['website_price'] ?? null;

                foreach (($line['rows'] ?? []) as $j => $row) {
                    $rowWebsiteEnabled = array_key_exists('website_enabled', $row) && $row['website_enabled'] !== null
                        ? (bool) $row['website_enabled']
                        : $lineWebsiteEnabled;

                    $rowWebsitePrice = isset($row['website_price']) && $row['website_price'] !== ''
                        ? $row['website_price']
                        : $lineWebsitePrice;

                    if ($rowWebsiteEnabled && (! is_numeric($rowWebsitePrice) || (float) $rowWebsitePrice <= 0)) {
                        $v->errors()->add(
                            "lines.{$i}.rows.{$j}.website_price",
                            'Selling Price is required when this row is enabled for the website.'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'lines.required'             => 'Add at least one item to the purchase.',
            'lines.*.category_id.required' => 'Choose a category for each item.',
            'lines.*.rows.required'      => 'Each item needs at least one inventory row.',
            'lines.*.rows.*.qty.required'   => 'Quantity is required for every inventory row.',
            'lines.*.rows.*.qty.min'     => 'Quantity must be zero or more.',
            'lines.*.rows.*.price.required' => 'Price is required for every inventory row.',
            'lines.*.rows.*.price.min'   => 'Price cannot be negative.',
        ];
    }

    /**
     * Friendly names for the wildcard array fields above — without these,
     * a validation failure on e.g. lines.0.rows.1.qty that isn't covered
     * by a specific message() key falls back to Laravel's generic
     * ":attribute field is required" using the raw dot-path itself as
     * the attribute name ("The lines.0.rows.1.qty field is required."),
     * which means nothing to the person filling out the form.
     */
    public function attributes(): array
    {
        return [
            'lines.*.category_id'      => 'stone',
            'lines.*.country_of_origin_id' => 'country of origin',
            'lines.*.package_qty'      => 'pack qty',
            'lines.*.rows.*.qty'          => 'quantity',
            'lines.*.rows.*.carat_weight' => 'carat weight',
            'lines.*.rows.*.price'        => 'price',
            'lines.*.rows.*.barcode'      => 'barcode',
        ];
    }
}
