<?php

namespace App\Http\Requests;

use App\Models\Barcode;
use App\Models\Category;
use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise raw input before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status'           => $this->boolean('status'),
            'website_enabled'  => $this->boolean('website_enabled'),
            'featured_product' => $this->boolean('featured_product'),
        ]);

        if ($this->has('barcodes') && is_string($this->input('barcodes'))) {
            $decoded = json_decode($this->input('barcodes'), true);
            if (is_array($decoded)) {
                $this->merge(['barcodes' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        $productId = $product?->id;

        return [
            /* ----------------------------- Core ----------------------------- */
            'title'             => ['required', 'string', 'max:200'],
            'sku'               => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('products', 'sku')
                    ->ignore($productId)
                    ->whereNull('deleted_at'),
            ],

            // 'category_id' => [
            //     'required',
            //     'integer',
            //     Rule::exists('categories', 'id')
            //         ->whereNull('deleted_at')
            //         ->where('status', 1)
            //         ->whereNotNull('parent_id'),
            // ],

            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description'  => ['nullable', 'string'],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'notes_tags'        => ['nullable', 'string', 'max:1000'],
            'status'            => ['required', 'boolean'],

            /* -------------------- Gemstone-specific fields ------------------ */
            'carat_weight'       => ['nullable', 'numeric', 'min:0.001', 'max:99999.999'],
            'stone_type'         => ['nullable', 'string', Rule::in(Product::STONE_TYPES)],
            'colour_grade'       => ['nullable', 'string', 'max:100'],
            'clarity_grade'      => ['nullable', 'string', Rule::in(Product::CLARITY_GRADES)],
            'cut_shape'          => ['nullable', 'string', Rule::in(Product::CUT_SHAPES)],
            'treatment'          => ['nullable', 'string', Rule::in(Product::TREATMENTS)],
            'certificate_number' => ['nullable', 'string', 'max:100'],

            /* ------------------------ Website visibility -------------------- */
            'website_enabled'     => ['required', 'boolean'],
            'website_price'       => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'website_title'       => ['nullable', 'string', 'max:200'],
            'website_description' => ['nullable', 'string'],
            'featured_product'    => ['required', 'boolean'],
            'website_sort_order'  => ['nullable', 'integer', 'min:0', 'max:99999'],

            /* ----------------------------- Images --------------------------- */
            'primary_image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'remove_primary_image'  => ['nullable', 'boolean'],
            'gallery_images'        => ['nullable', 'array', 'max:' . Product::MAX_GALLERY_IMAGES],
            'gallery_images.*'      => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'remove_gallery_ids'    => ['nullable', 'array'],
            'remove_gallery_ids.*'  => ['integer'],
            'certificate_image'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'remove_certificate_image' => ['nullable', 'boolean'],

            /* ----------------------------- Barcodes ------------------------- */
            // Update allows existing barcode rows (with `id`) plus new ones.
            'barcodes'                 => ['required', 'array', 'min:1', 'max:' . Barcode::MAX_BARCODES_PER_PRODUCT],
            'barcodes.*.id'            => ['nullable', 'integer'],
            'barcodes.*.value'         => ['required', 'string', 'max:100'],
            'barcodes.*.format'        => ['required', 'string', Rule::in(Barcode::FORMATS)],
            'barcodes.*.label'         => ['nullable', 'string', 'max:100'],
            'barcodes.*.is_primary'    => ['required', 'boolean'],
            'barcodes.*.channel_ids'   => ['nullable', 'array'],
            'barcodes.*.channel_ids.*' => [
                'integer',
                Rule::exists('channels', 'id')->whereNull('deleted_at')->where('status', 1),
            ],
        ];
    }

    /**
     * Cross-field validation after simple rules pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->validateGemstoneFields($v);
            $this->validateBarcodes($v);
        });
    }

    protected function validateGemstoneFields(Validator $v): void
    {
        $categoryId = $this->input('category_id');
        if (! $categoryId) {
            return;
        }

        $category = Category::with('parent')->find($categoryId);
        if (! $category) {
            return;
        }

        $top = $category->parent ?? $category;

        if (! (bool) $top->is_gemstone) {
            return;
        }

        if (! $this->filled('carat_weight')) {
            $v->errors()->add('carat_weight', 'Carat weight is required for gemstone products.');
        }
        if (! $this->filled('stone_type')) {
            $v->errors()->add('stone_type', 'Stone type is required for gemstone products.');
        }
        if (! $this->filled('treatment')) {
            $v->errors()->add('treatment', 'Treatment is required for gemstone products.');
        }
    }

    /**
     * Barcode cross-field rules.
     * Update mode: each barcode may carry an `id` referring to an existing
     * row; uniqueness must ignore that id.
     */
    protected function validateBarcodes(Validator $v): void
    {
        $barcodes = $this->input('barcodes', []);
        if (! is_array($barcodes) || empty($barcodes)) {
            return;
        }

        /** @var Product $product */
        $product = $this->route('product');

        // Rule 1: exactly one primary.
        $primaryCount = 0;
        foreach ($barcodes as $b) {
            if (! empty($b['is_primary'])) {
                $primaryCount++;
            }
        }
        if ($primaryCount === 0) {
            $v->errors()->add('barcodes', 'Please designate one barcode as Primary.');
        } elseif ($primaryCount > 1) {
            $v->errors()->add('barcodes', 'Only one barcode can be marked as Primary.');
        }

        // Rule 2: no duplicates within this submission.
        $valuesInRequest = [];
        foreach ($barcodes as $i => $b) {
            $value = trim((string) ($b['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            if (isset($valuesInRequest[$value])) {
                $v->errors()->add(
                    "barcodes.{$i}.value",
                    'Duplicate barcode value within this product.'
                );
            }
            $valuesInRequest[$value] = true;
        }

        // Rule 3: format + platform-wide uniqueness (ignoring the matching id).
        /** @var BarcodeService $service */
        $service = app(BarcodeService::class);

        // Whitelist of barcode IDs that BELONG to this product — values on
        // these rows are allowed to "collide" with themselves.
        $allowedExistingIds = $product
            ? $product->barcodes()->pluck('id')->all()
            : [];

        foreach ($barcodes as $i => $b) {
            $value  = trim((string) ($b['value']  ?? ''));
            $format = (string) ($b['format'] ?? '');
            $rowId  = isset($b['id']) ? (int) $b['id'] : null;

            if ($value === '' || $format === '') {
                continue;
            }

            // Format-specific check.
            $err = $service->validateFormat($value, $format);
            if ($err !== null) {
                $v->errors()->add("barcodes.{$i}.value", $err);
                continue;
            }

            // Uniqueness — ignore the row's own id when updating; also ignore
            // any other barcode id that belongs to this same product (so
            // reassigning a value between rows is permitted).
            $ignoreId = null;
            if ($rowId !== null && in_array($rowId, $allowedExistingIds, true)) {
                $ignoreId = $rowId;
            }

            if ($service->existsInDatabase($value, $ignoreId)) {
                // Final safety: still allow if the existing-id-with-this-value
                // belongs to this product.
                $owner = Barcode::withTrashed()
                    ->where('barcode_value', $value)
                    ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                    ->first();

                if ($owner && $product && $owner->product_id === $product->id) {
                    continue;
                }

                $v->errors()->add(
                    "barcodes.{$i}.value",
                    'Barcode is already assigned to another product.'
                );
            }
        }
    }

    public function messages(): array
    {
        return [
            'sku.unique'           => 'SKU already exists. Choose a different SKU.',
            'sku.regex'            => 'SKU may only contain letters, numbers, hyphens, and underscores (no spaces).',
            'category_id.exists'   => 'Please choose a valid Subcategory.',
            'primary_image.max'    => 'Primary image must not be larger than 5 MB.',
            'primary_image.mimes'  => 'Primary image must be a JPG or PNG file.',
            'gallery_images.max'   => 'You may upload at most ' . Product::MAX_GALLERY_IMAGES . ' gallery images.',
            'gallery_images.*.max' => 'Each gallery image must not be larger than 5 MB.',
            'barcodes.min'         => 'Please add at least one barcode.',
            'barcodes.max'         => 'A product may have at most ' . Barcode::MAX_BARCODES_PER_PRODUCT . ' barcodes.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'              => 'Product Title',
            'sku'                => 'SKU',
            'category_id'        => 'Subcategory',
            'short_description'  => 'Short Description',
            'full_description'   => 'Full Description',
            'country_of_origin'  => 'Country of Origin',
            'carat_weight'       => 'Carat Weight',
            'stone_type'         => 'Stone Type',
            'colour_grade'       => 'Colour Grade',
            'clarity_grade'      => 'Clarity Grade',
            'cut_shape'          => 'Cut / Shape',
            'treatment'          => 'Treatment',
            'certificate_number' => 'Certificate Number',
            'primary_image'      => 'Primary Image',
            'gallery_images'     => 'Gallery Images',
            'certificate_image'  => 'Certificate File',
            'website_enabled'    => 'Website Enabled',
            'website_price'      => 'Website Price',
            'website_title'      => 'Website Title',
            'website_description' => 'Website Description',
            'featured_product'   => 'Featured Product',
            'website_sort_order' => 'Website Sort Order',
        ];
    }
}
