<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchasePayment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        // Cancelled purchases only reach validation defensively — the
        // controller's editBlockReason() gate normally redirects/blocks
        // before this. Drafts and editable Posted purchases (no sales yet,
        // within the edit window) both get the full line-item ruleset.
        if ($purchase && $purchase->isCancelled()) {
            return [
                'note' => ['nullable', 'string'],
            ];
        }

        return [
            'purchase_date' => ['required', 'date'],
            'location_id'   => ['required', 'integer', 'exists:locations,id'],
            'tax_type'      => ['required', 'in:' . implode(',', Purchase::TAX_TYPES)],
            'note'          => ['nullable', 'string'],

            // ── Payments (optional; multiple rows, like a sale). Only
            // applied when the caller explicitly sends a `payments` key —
            // see PurchaseService::update(), which otherwise leaves
            // existing payments untouched. ───────────────────────
            'payments'                    => ['nullable', 'array'],
            'payments.*.payment_date'     => ['required_with:payments.*.amount', 'date'],
            'payments.*.amount'           => ['required_with:payments.*.payment_date', 'numeric'],
            'payments.*.payment_method'   => ['required_with:payments.*.amount', Rule::in(array_keys(PurchasePayment::METHODS))],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
            'payments.*.notes'            => ['nullable', 'string', 'max:500'],

            'lines'      => ['required', 'array', 'min:1'],
            // Rows/lines already on this purchase echo their id back so
            // PurchaseService::syncLines() can update them in place
            // instead of recreating their product. Anything without an
            // id is treated as new.
            'lines.*.id' => ['nullable', 'integer'],

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

            'lines.*.carat_weight'  => ['nullable', 'numeric', 'min:0.001', 'max:99999.999'],
            'lines.*.stone_type'    => ['nullable', 'string', Rule::in(Product::STONE_TYPES)],
            'lines.*.colour_grade'  => ['nullable', 'string', 'max:100'],
            'lines.*.clarity_grade' => ['nullable', 'string', Rule::in(Product::CLARITY_GRADES)],
            'lines.*.cut_shape'     => ['nullable', 'string', Rule::in(Product::CUT_SHAPES)],
            'lines.*.treatment'     => ['nullable', 'string', Rule::in(Product::TREATMENTS)],
            'lines.*.stone_description' => ['nullable', 'string'],

            'lines.*.type'         => ['required', 'in:' . implode(',', PurchaseLine::TYPES)],
            'lines.*.package_name' => ['nullable', 'string', 'max:50'],
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
            'lines.*.rows.*.expiry_date'      => ['nullable', 'date'],
            'lines.*.rows.*.manufacture_date' => ['nullable', 'date', 'before_or_equal:lines.*.rows.*.expiry_date'],
            'lines.*.rows.*.remarks'          => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Purchase|null $purchase */
        $purchase = $this->route('purchase');
        if ($purchase && $purchase->isCancelled()) {
            return;
        }

        $validator->after(function (Validator $v) {
            $lines = $this->input('lines', []);
            if (! is_array($lines)) {
                return;
            }

            // Supplier is locked on edit — read it off the purchase itself
            // rather than the request, which never posts a supplier_id.
            /** @var Purchase|null $purchase */
            $purchase = $this->route('purchase');
            $supplierCategoryIds = $purchase && $purchase->supplier
                ? $purchase->supplier->categories()->pluck('categories.id')->all()
                : [];

            foreach ($lines as $i => $line) {
                $categoryId = $line['category_id'] ?? null;
                if (! $categoryId) {
                    continue;
                }

                if ($supplierCategoryIds && ! in_array((int) $categoryId, $supplierCategoryIds, true)) {
                    $v->errors()->add("lines.{$i}.category_id", 'This category is not mapped to the selected supplier.');
                }

                $category = Category::find($categoryId);
                if (! $category || ! (bool) $category->is_gemstone) {
                    continue;
                }

                if (! isset($line['carat_weight']) || $line['carat_weight'] === '') {
                    $v->errors()->add("lines.{$i}.carat_weight", 'Carat weight is required for gemstone items.');
                }
                if (empty($line['stone_type'])) {
                    $v->errors()->add("lines.{$i}.stone_type", 'Stone type is required for gemstone items.');
                }
                if (empty($line['treatment'])) {
                    $v->errors()->add("lines.{$i}.treatment", 'Treatment is required for gemstone items.');
                }
            }
        });
    }
}
