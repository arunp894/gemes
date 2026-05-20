<?php

namespace App\Http\Requests;

use App\Models\Barcode;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        // Defaults so missing checkboxes don't fail "required boolean" rules.
        $this->merge([
            'status'           => $this->boolean('status'),
            'website_enabled'  => $this->boolean('website_enabled'),
            'featured_product' => $this->boolean('featured_product'),
        ]);

        // Decode JSON-encoded barcodes/channel-assignments arrays if the
        // front-end submits them as strings (multipart/form-data forms
        // can't cleanly send nested arrays).
        if ($this->has('barcodes') && is_string($this->input('barcodes'))) {
            $decoded = json_decode($this->input('barcodes'), true);
            if (is_array($decoded)) {
                $this->merge(['barcodes' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            /* ----------------------------- Core ----------------------------- */
            'title'             => ['required', 'string', 'max:200'],
            'sku'               => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('products', 'sku')->whereNull('deleted_at'),
            ],

            // category_id MUST be a leaf-level category (subcategory in spec
            // language — i.e. has a non-null parent_id) AND must be active.
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('deleted_at')
                    ->where('status', 1)
                    ->whereNotNull('parent_id'),
            ],

            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description'  => ['nullable', 'string'],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'notes_tags'        => ['nullable', 'string', 'max:1000'],
            'status'            => ['required', 'boolean'],

            /* -------------------- Gemstone-specific fields ------------------ */
            // Gemstone fields are conditionally required in withValidator().
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
            'primary_image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'], // 5 MB
            'gallery_images'        => ['nullable', 'array', 'max:' . Product::MAX_GALLERY_IMAGES],
            'gallery_images.*'      => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'certificate_image'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], // 10 MB for PDF

            /* ----------------------------- Barcodes ------------------------- */
            // At least 1 barcode is required (the product cannot exist without
            // physical identification per spec §5).
            'barcodes'                 => ['required', 'array', 'min:1', 'max:' . Barcode::MAX_BARCODES_PER_PRODUCT],
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
     * Cross-field validation after the simple rules pass.
     *  - Gemstone field requirements based on the chosen category's parent
     *  - Exactly one barcode marked primary
     *  - No duplicate barcode values within the request
     *  - Format-specific validation (check digits) via BarcodeService
     *  - Platform-wide uniqueness of each barcode value
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->validateGemstoneFields($v);
            $this->validateBarcodes($v);
        });
    }

    /**
     * Conditional gemstone field requirements.
     * If the chosen subcategory's parent code is in the gemstone group,
     * carat_weight / stone_type / treatment become required.
     */
    protected function validateGemstoneFields(Validator $v): void
    {
        $categoryId = $this->input('category_id');
        if (! $categoryId) {
            return; // already errored on category_id rule
        }

        $category = Category::with('parent')->find($categoryId);
        if (! $category) {
            return;
        }

        $top = $category->parent ?? $category;
        $isGemstone = in_array(
            strtoupper((string) $top->code),
            Product::GEMSTONE_PARENT_CODES,
            true
        );

        if (! $isGemstone) {
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
     * Cross-cut barcode validation. Runs after the simple per-field rules.
     */
    protected function validateBarcodes(Validator $v): void
    {
        $barcodes = $this->input('barcodes', []);
        if (! is_array($barcodes) || empty($barcodes)) {
            return; // already errored at base rule
        }

        // Rule 1: exactly one barcode must be primary.
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

        // Rule 2: no duplicate barcode values within the same submission.
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

        // Rule 3: format-specific validation + platform-wide uniqueness.
        /** @var BarcodeService $service */
        $service = app(BarcodeService::class);

        foreach ($barcodes as $i => $b) {
            $value  = trim((string) ($b['value']  ?? ''));
            $format = (string) ($b['format'] ?? '');

            if ($value === '' || $format === '') {
                continue;
            }

            // Format-specific (length, charset, check digit).
            $err = $service->validateFormat($value, $format);
            if ($err !== null) {
                $v->errors()->add("barcodes.{$i}.value", $err);
                continue;
            }

            // Platform-wide DB uniqueness.
            if ($service->existsInDatabase($value)) {
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
            'website_description'=> 'Website Description',
            'featured_product'   => 'Featured Product',
            'website_sort_order' => 'Website Sort Order',
        ];
    }
}
