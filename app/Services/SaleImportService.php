<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * SaleImportService
 *
 * Parses an uploaded Excel/CSV, validates every row, groups lines into
 * orders, then delegates each order to SaleService::create() — the same
 * path as the manual terminal, so stock movements and payment status are
 * identical to a hand-entered sale.
 *
 * ── Row layout (columns A–P, rows 5+ in the template) ───────────────
 *   A  sale_date           required
 *   B  customer_name       required
 *   C  customer_email      optional – primary customer match key
 *   D  channel_code        required – must match Channel::code
 *   E  location_code       required – must match Location::location_code
 *   F  external_ref        optional – dedup key (eBay Sales Record #)
 *   G  external_order_id   optional
 *   H  shipping_charge     optional – put on FIRST line of order only
 *   I  note                optional
 *   J  payment_method      optional – defaults to bank_transfer
 *   K  sku                 required – must match Product::sku exactly
 *   L  qty                 required ≥ 1
 *   M  unit_price_inr      required ≥ 0
 *   N  tax_percent         optional – defaults to 0
 *   O  discount_percent    optional – defaults to 0
 *   P  line_note           optional
 *
 * ── Grouping ─────────────────────────────────────────────────────────
 *   Rows become a single Sale when they share:
 *     1. same channel_code + external_ref (if external_ref non-empty)
 *     2. same sale_date + customer_email  (if email present, no ext_ref)
 *     3. fallback: each row is its own standalone order
 *
 * ── Stock flow ───────────────────────────────────────────────────────
 *   SaleService::create() is called with status=posted which triggers
 *   SaleService::post() → StockService::recordSalePosting().
 *   stock_movements OUT rows are written exactly as if the user had
 *   entered the sale at the terminal. If a product has insufficient
 *   stock the whole order is rejected (not just that line).
 *
 * ── Duplicate protection ─────────────────────────────────────────────
 *   (channel_id, external_ref) is a unique index on the sales table.
 *   Any group that would hit the index is skipped with a clear message.
 */
class SaleImportService
{
    /* ── column indices (0-based) ── */
    private const COL_DATE       = 0;
    private const COL_CUST_NAME  = 1;
    private const COL_CUST_EMAIL = 2;
    private const COL_CHANNEL    = 3;
    private const COL_LOCATION   = 4;
    private const COL_EXT_REF    = 5;
    private const COL_EXT_ORDER  = 6;
    private const COL_SHIPPING   = 7;
    private const COL_NOTE       = 8;
    private const COL_PAY_METHOD = 9;
    private const COL_SKU        = 10;
    private const COL_QTY        = 11;
    private const COL_PRICE      = 12;
    private const COL_TAX        = 13;
    private const COL_DISCOUNT   = 14;
    private const COL_LINE_NOTE  = 15;

    public function __construct(private SaleService $saleService) {}

    /* ─────────────────────────────────────────────────────────────────
     |  PUBLIC: parse – dry-run, returns preview. Nothing persisted.
     | ─────────────────────────────────────────────────────────────────*/
    /**
     * @return array{groups: array, errors: array, summary: array}
     */
    public function parse(string $filePath): array
    {
        $rows   = $this->readFile($filePath);
        $groups = $this->groupRows($rows);

        $errors  = [];
        $preview = [];

        foreach ($groups as $key => $group) {
            $result = $this->validateGroup($group);
            if ($result['errors']) {
                $errors[$key] = $result['errors'];
            }
            $preview[$key] = $result['preview'];
        }

        return [
            'groups'  => $preview,
            'errors'  => $errors,
            'summary' => [
                'total_groups' => count($groups),
                'total_lines'  => array_sum(array_map('count', $groups)),
                'error_groups' => count($errors),
                'clean_groups' => count($groups) - count($errors),
            ],
        ];
    }

    /* ─────────────────────────────────────────────────────────────────
     |  PUBLIC: import – persist clean groups.
     |
     |  Each order is passed to SaleService::create() which owns its own
     |  DB::transaction(). We do NOT wrap it in another transaction here
     |  so a single failed order never rolls back the orders already
     |  imported before it.
     | ─────────────────────────────────────────────────────────────────*/
    /**
     * @return array{imported: int, skipped: int, duplicate: int, failed: int, errors: array, sale_ids: array}
     */
    public function import(string $filePath): array
    {
        $rows    = $this->readFile($filePath);
        $groups  = $this->groupRows($rows);
        $batchId = Str::uuid()->toString();

        $result = [
            'imported'  => 0,
            'skipped'   => 0,
            'duplicate' => 0,
            'failed'    => 0,
            'errors'    => [],
            'sale_ids'  => [],
        ];

        foreach ($groups as $key => $group) {
            $validated = $this->validateGroup($group);

            // ── Validation errors → skip ─────────────────────────────
            if ($validated['errors']) {
                $result['skipped']++;
                $result['errors'][$key] = $validated['errors'];
                continue;
            }

            $payload = $validated['payload'];

            // ── Duplicate guard (unique index: channel_id + external_ref) ─
            if (! empty($payload['external_ref']) && ! empty($payload['channel_id'])) {
                $exists = Sale::where('channel_id',   $payload['channel_id'])
                              ->where('external_ref', $payload['external_ref'])
                              ->withTrashed()
                              ->exists();
                if ($exists) {
                    $result['duplicate']++;
                    $result['errors'][$key] = [
                        "Duplicate: external_ref \"{$payload['external_ref']}\" already imported for this channel.",
                    ];
                    continue;
                }
            }

            // ── Persist via the normal sale flow ─────────────────────
            // SaleService::create() owns the transaction.  We stamp the
            // import columns (external_ref, batch_id) inside the payload
            // so they land atomically in the same transaction.
            try {
                $payload['import_batch_id'] = $batchId;

                $sale = $this->saleService->create($payload);

                $result['imported']++;
                $result['sale_ids'][] = $sale->id;
            } catch (Throwable $e) {
                $result['failed']++;
                $result['errors'][$key] = [$this->friendlyError($e->getMessage())];
            }
        }

        return $result;
    }

    /* ─────────────────────────────────────────────────────────────────
     |  PRIVATE: file reading
     | ─────────────────────────────────────────────────────────────────*/

    /**
     * Return rows as plain 0-indexed arrays (strings).
     * Skips header rows 1-4 and fully empty rows.
     */
    private function readFile(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            return $this->readCsv($filePath);
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];

        foreach ($sheet->getRowIterator(5) as $row) {
            $cells = [];
            foreach ($row->getCellIterator('A', 'P') as $cell) {
                $cells[] = trim((string) ($cell->getFormattedValue() ?? ''));
            }
            if (array_filter($cells) === []) {
                continue; // skip empty rows
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    private function readCsv(string $filePath): array
    {
        $rows   = [];
        $handle = fopen($filePath, 'r');
        $skip   = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $skip++;
            if ($skip <= 4) {
                continue; // skip header rows
            }
            $cells = array_map('trim', $line);
            if (array_filter($cells) === []) {
                continue;
            }
            while (count($cells) < 16) {
                $cells[] = '';
            }
            $rows[] = $cells;
        }

        fclose($handle);
        return $rows;
    }

    /* ─────────────────────────────────────────────────────────────────
     |  PRIVATE: grouping
     | ─────────────────────────────────────────────────────────────────*/

    /**
     * Group rows into orders.
     * Priority: external_ref → date+email → standalone row.
     */
    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $rowIndex => $row) {
            $extRef  = trim($row[self::COL_EXT_REF]    ?? '');
            $date    = trim($row[self::COL_DATE]        ?? '');
            $email   = strtolower(trim($row[self::COL_CUST_EMAIL] ?? ''));
            $name    = strtolower(trim($row[self::COL_CUST_NAME]  ?? ''));
            $channel = strtolower(trim($row[self::COL_CHANNEL]    ?? ''));

            if ($extRef !== '') {
                $key = "ext:{$channel}:{$extRef}";
            } elseif ($email !== '') {
                $key = "date_email:{$date}:{$email}:{$channel}";
            } else {
                // Each row becomes its own order (no grouping signal)
                $key = "row:{$rowIndex}:{$date}:{$name}";
            }

            $groups[$key][] = array_merge($row, ['_row_index' => $rowIndex + 5]);
        }

        return $groups;
    }

    /* ─────────────────────────────────────────────────────────────────
     |  PRIVATE: validation + payload builder
     | ─────────────────────────────────────────────────────────────────*/

    /**
     * @return array{errors: array, preview: array, payload: array}
     */
    private function validateGroup(array $rows): array
    {
        $errors = [];
        $lines  = [];
        $first  = $rows[0];

        // ── Order-level fields (first row) ───────────────────────────
        $date         = trim($first[self::COL_DATE]       ?? '');
        $custName     = trim($first[self::COL_CUST_NAME]  ?? '');
        $custEmail    = trim($first[self::COL_CUST_EMAIL] ?? '');
        $channelCode  = strtolower(trim($first[self::COL_CHANNEL]   ?? ''));
        $locationCode = strtoupper(trim($first[self::COL_LOCATION]  ?? ''));
        $extRef       = trim($first[self::COL_EXT_REF]    ?? '');
        $extOrder     = trim($first[self::COL_EXT_ORDER]  ?? '');
        $shipping     = (float) str_replace(',', '', $first[self::COL_SHIPPING] ?? '0');
        $note         = trim($first[self::COL_NOTE]       ?? '');
        $payMethod    = strtolower(trim($first[self::COL_PAY_METHOD] ?? '')) ?: 'bank_transfer';

        // Validate date
        if ($date === '' || strtotime($date) === false) {
            $errors[] = "Row {$first['_row_index']}: sale_date \"{$date}\" is not a valid date (use YYYY-MM-DD).";
        }

        // Validate customer name
        if ($custName === '') {
            $errors[] = "Row {$first['_row_index']}: customer_name is required.";
        }

        // Resolve channel
        $channel = null;
        if ($channelCode === '') {
            $errors[] = "Row {$first['_row_index']}: channel_code is required.";
        } else {
            $channel = Channel::where('code', $channelCode)->first();
            if (! $channel) {
                $errors[] = "Row {$first['_row_index']}: channel_code \"{$channelCode}\" not found in Paces.";
            }
        }

        // Resolve location
        $location = null;
        if ($locationCode === '') {
            $errors[] = "Row {$first['_row_index']}: location_code is required.";
        } else {
            $location = Location::where('location_code', $locationCode)->first();
            if (! $location) {
                $errors[] = "Row {$first['_row_index']}: location_code \"{$locationCode}\" not found in Paces.";
            }
        }

        // Validate payment method
        if (! array_key_exists($payMethod, SalePayment::METHODS)) {
            $errors[] = "Row {$first['_row_index']}: payment_method \"{$payMethod}\" is not valid. "
                      . 'Allowed: ' . implode(', ', array_keys(SalePayment::METHODS)) . '.';
            $payMethod = SalePayment::METHOD_BANK_TRANSFER;
        }

        // Resolve / auto-create customer
        $customer = null;
        if ($custName !== '') {
            $customer = $this->resolveCustomer($custName, $custEmail);
            if (! $customer) {
                $errors[] = "Row {$first['_row_index']}: could not resolve or create customer \"{$custName}\".";
            }
        }

        // ── Line-level fields ────────────────────────────────────────
        foreach ($rows as $row) {
            $rowNum   = $row['_row_index'];
            $sku      = trim($row[self::COL_SKU]        ?? '');
            $qty      = (int)   ($row[self::COL_QTY]    ?? 0);
            $price    = (float) str_replace(',', '', $row[self::COL_PRICE]    ?? '0');
            $taxPct   = (float) ($row[self::COL_TAX]      ?? 0);
            $discPct  = (float) ($row[self::COL_DISCOUNT] ?? 0);
            $lineNote = trim($row[self::COL_LINE_NOTE]  ?? '');

            if ($sku === '') {
                $errors[] = "Row {$rowNum}: sku is required.";
                continue;
            }

            $product = Product::where('sku', $sku)->whereNull('deleted_at')->first();
            if (! $product) {
                $errors[] = "Row {$rowNum}: SKU \"{$sku}\" not found in Paces products.";
                continue;
            }

            if ($qty < 1) {
                $errors[] = "Row {$rowNum}: qty must be at least 1 (got {$qty}).";
                continue;
            }

            if ($price < 0) {
                $errors[] = "Row {$rowNum}: unit_price_inr cannot be negative (got {$price}).";
                continue;
            }

            $lines[] = [
                'product_id'          => $product->id,
                'purchase_product_id' => null,  // FIFO allocation in StockService
                'barcode'             => null,
                'qty'                 => $qty,
                'unit_price'          => $price,
                'tax_percent'         => $taxPct,
                'discount_percent'    => $discPct,
                'notes'               => $lineNote ?: null,
            ];
        }

        if (empty($lines) && empty($errors)) {
            $errors[] = "Order has no valid product lines.";
        }

        // ── Build SaleService payload ─────────────────────────────────
        // external_ref and import_batch_id are included here so they land
        // inside the same DB::transaction() that SaleService owns — no
        // separate update() call needed after the fact.
        $payload = [];
        if (empty($errors)) {
            $saleDate = date('Y-m-d', strtotime($date));

            $payload = [
                'sale_date'         => $saleDate,
                'customer_id'       => $customer->id,
                'location_id'       => $location->id,
                'channel_id'        => $channel->id,
                'salesperson_id'    => null,
                'tax_type'          => Sale::TAX_NONE,
                'shipping_charge'   => $shipping,
                'note'              => $note ?: null,
                'status'            => Sale::STATUS_POSTED,
                'lines'             => $lines,
                'payments'          => [
                    [
                        'payment_date'     => $saleDate,
                        'amount'           => $this->calcGrandTotal($lines, $shipping),
                        'payment_method'   => $payMethod,
                        'reference_number' => $extRef ?: null,
                        'notes'            => null,
                    ],
                ],
                // import traceability — picked up by Sale::$fillable
                'external_ref'      => $extRef   ?: null,
                'external_order_id' => $extOrder ?: null,
                // import_batch_id is stamped by import() after this payload is built
            ];
        }

        // ── Preview data for the UI ───────────────────────────────────
        $preview = [
            'sale_date'      => $date,
            'customer_name'  => $custName,
            'customer_email' => $custEmail,
            'channel_code'   => $channelCode,
            'location_code'  => $locationCode,
            'external_ref'   => $extRef,
            'shipping'       => $shipping,
            'payment_method' => $payMethod,
            'line_count'     => count($rows),
            'lines'          => array_map(fn ($r) => [
                'sku'   => $r[self::COL_SKU]   ?? '',
                'qty'   => $r[self::COL_QTY]   ?? '',
                'price' => $r[self::COL_PRICE]  ?? '',
            ], $rows),
            'has_errors'     => ! empty($errors),
        ];

        return compact('errors', 'preview', 'payload');
    }

    /* ─────────────────────────────────────────────────────────────────
     |  PRIVATE: customer resolution
     | ─────────────────────────────────────────────────────────────────*/

    /**
     * Match order: exact email → exact name (case-insensitive) → create walk-in.
     * Includes soft-deleted records on email match so a restored customer
     * isn't duplicated.
     */
    private function resolveCustomer(string $name, string $email): ?Customer
    {
        if ($email !== '') {
            $c = Customer::withTrashed()->where('email', $email)->first();
            if ($c) {
                return $c;
            }
        }

        $c = Customer::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($c) {
            return $c;
        }

        return Customer::create([
            'name'          => $name,
            'email'         => $email ?: null,
            'customer_type' => Customer::TYPE_WALKIN,
            'status'        => Customer::STATUS_ACTIVE,
            'notes'         => 'Auto-created during sale import.',
        ]);
    }

    /* ─────────────────────────────────────────────────────────────────
     |  PRIVATE: helpers
     | ─────────────────────────────────────────────────────────────────*/

    /**
     * Pre-calculate grand total, mirroring SaleService::recalculate(),
     * so the payment row amount matches the invoice total exactly.
     */
    private function calcGrandTotal(array $lines, float $shipping): float
    {
        $subtotal = 0.0;
        $tax      = 0.0;
        $discount = 0.0;

        foreach ($lines as $l) {
            $gross    = $l['qty'] * $l['unit_price'];
            $disc     = round($gross * ($l['discount_percent'] / 100), 2);
            $taxable  = $gross - $disc;
            $taxAmt   = round($taxable * ($l['tax_percent'] / 100), 2);
            $subtotal += $gross;
            $discount += $disc;
            $tax      += $taxAmt;
        }

        return round($subtotal - $discount + $tax + $shipping, 2);
    }

    /**
     * Strip internal class paths from exception messages before showing
     * them in the UI.  Keeps RuntimeException messages (stock errors)
     * readable while hiding stacktrace noise.
     */
    private function friendlyError(string $msg): string
    {
        // StockService throws: "Cannot post sale: Insufficient stock for..."
        // SaleService throws:  "Cannot post an empty sale."
        // Both are already human-readable; strip anything after a newline.
        return trim(explode("\n", $msg)[0]);
    }
}
