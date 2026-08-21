<?php

namespace App\Console\Commands;

use App\Models\Product;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-generates realistic Purchase and Sale transactions for demo /
 * load-testing purposes.
 *
 * WHY THIS EXISTS (token / performance rationale)
 * ------------------------------------------------
 * Going through PurchaseService::create()/post() and SaleService::create()
 * one row at a time for 10,000+ purchases would mean tens of thousands of
 * full Eloquent lifecycles (model events, per-row SKU/lot-code/invoice-
 * number queries, individual transactions). That's correct but far too
 * slow at this volume.
 *
 * Instead this command:
 *   - Builds each purchase/sale's full plan in PHP memory (Faker-driven),
 *   - Replicates the handful of DB-derived sequences (SKU, EAN-13 barcode,
 *     invoice number, lot codes, sale number, customer code) as in-memory
 *     counters seeded ONCE from the DB, instead of one query per row,
 *   - Writes every table with chunked multi-row DB::table()->insert()
 *     calls instead of Model::create() in a loop,
 *   - Recovers auto-increment IDs for newly inserted children via
 *     lastInsertId() (MySQL/InnoDB guarantees a contiguous ascending ID
 *     block for every row in a single multi-row INSERT, regardless of
 *     innodb_autoinc_lock_mode -- see MySQL docs on AUTO_INCREMENT
 *     Handling in InnoDB), instead of re-querying.
 *
 * This keeps the generator itself small while being able to produce any
 * number of rows -- the cost doesn't scale with the row count the way
 * hand-written seed data would.
 *
 * BUSINESS LOGIC REPLICATED (kept in sync with, but independent of):
 *   - PurchaseService::syncLines()       (raw-row generation, box vs
 *                                          piece expansion, product-per-row
 *                                          creation)
 *   - Product::generateSku()             (per-category SKU sequence)
 *   - PurchaseProduct::generateLotCode() (SS-CCC-UUU sequence)
 *   - Purchase::generateInvoiceNumber()  (PREFIX-YYYYMM-#### per supplier)
 *   - Sale::generateSaleNumber()         (SALE-YYYYMM-#### global)
 *   - BarcodeService::generateUniqueEan13() (GS1 200-prefix + check digit)
 *   - StockService (append-only ledger, global stock pool, FIFO-ish pick)
 *   - PurchaseService::recalculatePayments() / SaleService equivalent
 *
 * FLOW: each posted Purchase row creates its own Product + primary
 * Barcode directly (mirroring PurchaseService::syncLines()) and credits
 * the stock pool; Sales draw only from that pool. Only Purchases with
 * status=posted write stock_movements and credit the pool; only Sales
 * with status != draft consume it -- this mirrors the real app's "draft
 * has no stock impact" rule exactly.
 *
 * USAGE
 * -----
 *   php artisan demo:seed-transactions --purchases=100 --sales=100   (test run first!)
 *   php artisan demo:seed-transactions                                (defaults: 10,000 / 10,000)
 *   php artisan demo:seed-transactions --purchases=20000 --sales=15000 --chunk=300
 *
 * Requires the base seeders to have already run (suppliers, locations,
 * gemstone categories, channels, walk-in customer) -- i.e. php artisan db:seed.
 */
class SeedDemoTransactions extends Command
{
    protected $signature = 'demo:seed-transactions
        {--purchases=10000 : Number of purchase invoices to generate}
        {--sales=10000 : Number of sale invoices to generate}
        {--chunk=200 : Purchases planned + inserted per DB transaction}
        {--sale-chunk=300 : Sales planned + inserted per DB transaction}
        {--customers=400 : Demo customers to create}
        {--memory-limit=1024M : PHP memory_limit override for this run (e.g. 512M, 2G, -1 for unlimited)}';

    protected $description = 'Bulk-generate realistic purchase & sale transactions via chunked raw inserts (Faker-driven, no Eloquent overhead).';

    private FakerGenerator $faker;

    /** @var array<string, string> colour descriptors -- no canonical list exists on the Product model, unlike clarity/cut/treatment */
    private const COLOUR_GRADES = [
        'Vivid Red', 'Pigeon Blood Red', 'Royal Blue', 'Cornflower Blue', 'Vivid Green',
        'Padparadscha', 'Vivid Pink', 'Deep Blue', 'Golden Yellow', 'Vivid Orange', 'Lavender',
    ];

    // ── Reference data (loaded once) ──────────────────────────────
    private array $suppliers = [];     // supplier_id => ['invoice_prefix'=>, 'lot_prefix'=>]
    private array $locationIds = [];
    private array $rackIds = [];
    private array $gemCategories = []; // category_id => ['name'=>,'code'=>,'stone_type'=>,'rough'=>bool]
    private array $channelIds = [];    // code => id
    private ?int $adminId = null;
    private ?int $walkinCustomerId = null;
    private array $customerIds = [];
    private array $countryIds = [];

    // ── Sequence caches (seeded once from DB, then incremented in memory) ──
    private array $skuCounter = [];        // category_id => int
    private int $barcodeCounter = 0;
    private array $invoiceSeq = [];        // "supplierId_ym" => int
    private array $saleSeq = [];           // ym => int
    private array $catSeqMap = [];         // "prefix|categoryId" => "003"
    private array $maxCatSeqByPrefix = []; // prefix => int
    private array $unitCounterByStub = []; // "PFX-003-" => int
    private int $customerCodeCounter = 0;

    // ── Stock -- fed by posted Purchases (each row creates its own
    //    Product + Barcode immediately, see generatePurchases()), drained
    //    by Sales. O(1) random-pick + O(1) removal via a swap-to-end-and-
    //    pop index (array_rand() on a 50k+ array would be O(n) per call
    //    and dominate runtime). ─────────────────────────────────────────
    private array $stockPool = [];       // pp_id => ['product_id','location_id','price','remaining','purchase_date']
    private array $stockPoolOrder = [];
    private array $stockPoolIndex = [];

    public function handle(): void
    {
        // Safety net -- many Windows/XAMPP php.ini defaults cap CLI scripts
        // at 128M, which the stock pool alone can approach at full 10K+
        // scale. Override for just this process; doesn't touch php.ini.
        $memoryLimit = (string) $this->option('memory-limit');
        if ($memoryLimit !== '') {
            ini_set('memory_limit', $memoryLimit);
        }

        $startedAt = microtime(true);
        $this->faker = $this->makeFaker();

        $this->info('Loading reference data...');
        $this->loadReferenceData();

        if (empty($this->suppliers) || empty($this->locationIds) || empty($this->gemCategories)) {
            $this->error('Missing base data -- run "php artisan db:seed" first (need suppliers, locations, and gemstone categories).');
            return;
        }

        $customerCount = (int) $this->option('customers');
        if ($customerCount > 0) {
            $this->info("Creating {$customerCount} demo customers...");
            $this->createCustomers($customerCount);
        }

        $purchaseTarget = max(0, (int) $this->option('purchases'));
        $saleTarget     = max(0, (int) $this->option('sales'));

        if ($purchaseTarget > 0) {
            $this->info("Generating {$purchaseTarget} purchases...");
            $this->generatePurchases($purchaseTarget, max(1, (int) $this->option('chunk')));
        }

        $actualSales = 0;
        if ($saleTarget > 0) {
            $this->info("Generating up to {$saleTarget} sales against " . count($this->stockPoolOrder) . ' available stock rows...');
            $actualSales = $this->generateSales($saleTarget, max(1, (int) $this->option('sale-chunk')));
        }

        $elapsed = round(microtime(true) - $startedAt, 1);
        $this->newLine();
        $this->info('Done in ' . $elapsed . 's.');
        $this->line("  Customers created : {$customerCount}");
        $this->line("  Purchases created : {$purchaseTarget}");
        $this->line("  Sales created     : {$actualSales}" . ($actualSales < $saleTarget ? ' (stopped early -- stock pool exhausted)' : ''));
    }

    /* ═══════════════════════════════════════════════════════════════
     |  Reference data + sequence bootstrapping
     ═══════════════════════════════════════════════════════════════ */

    private function makeFaker(): FakerGenerator
    {
        try {
            return FakerFactory::create('en_IN');
        } catch (\Throwable $e) {
            return FakerFactory::create();
        }
    }

    private function loadReferenceData(): void
    {
        $this->adminId = DB::table('users')->where('email', 'admin@paces.local')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        foreach (DB::table('suppliers')->where('status', 1)->whereNull('deleted_at')->get(['id', 'name', 'company_name', 'invoice_prefix']) as $s) {
            $name    = $s->company_name ?: $s->name ?: '';
            $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
            $this->suppliers[$s->id] = [
                'invoice_prefix' => strtoupper((string) $s->invoice_prefix),
                'lot_prefix'     => str_pad(substr($letters, 0, 2), 2, 'X'),
            ];
        }

        $this->locationIds = DB::table('locations')->where('status', 1)->whereNull('deleted_at')->pluck('id')->all();
        $this->rackIds     = DB::table('racks')->where('status', 1)->whereNull('deleted_at')->pluck('id')->all();
        $this->countryIds  = DB::table('countries_of_origin')->where('status', 1)->pluck('id')->all();

        foreach (DB::table('categories')->where('is_gemstone', 1)->where('status', 1)->whereNull('deleted_at')->get(['id', 'name', 'code']) as $c) {
            $stoneType = match (true) {
                str_contains($c->name, 'Ruby')     => 'Ruby',
                str_contains($c->name, 'Sapphire') => 'Sapphire',
                str_contains($c->name, 'Emerald')  => 'Emerald',
                str_contains($c->name, 'Diamond')  => 'Diamond',
                default                            => 'Other',
            };
            $this->gemCategories[$c->id] = [
                'name'       => $c->name,
                'code'       => $c->code,
                'stone_type' => $stoneType,
                'rough'      => str_contains($c->name, 'Rough'),
            ];
        }

        $this->channelIds       = DB::table('channels')->where('status', 1)->pluck('id', 'code')->all();
        $this->walkinCustomerId = DB::table('customers')->where('customer_code', 'WALKIN')->value('id');

        // ── Sequence seeds -- one cheap query per sequence, never per row ──
        foreach ($this->gemCategories as $catId => $cat) {
            $stub = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cat['code'])) . '-';
            $max  = DB::table('products')->where('sku', 'like', $stub . '%')
                ->pluck('sku')->map(fn ($sku) => (int) substr($sku, strlen($stub)))->max();
            $this->skuCounter[$catId] = (int) $max;
        }

        $maxBarcode = DB::table('barcodes')->where('barcode_value', 'like', '200%')->max('barcode_value');
        $this->barcodeCounter = $maxBarcode ? (int) substr((string) $maxBarcode, 3, 9) : 0;

        // Every one of these blocks is aggregated IN SQL (GROUP BY / MAX),
        // never a raw ->get() of every purchase/sale/lot-code row. A
        // ->get() here would grow with the TABLE size, not with anything
        // this command controls -- after this command has run once and the
        // tables hold 100K+ rows, a naive full-table pull to seed a few
        // running counters is exactly the kind of thing that exhausts a
        // 128M CLI memory_limit on a second run. These stay at (suppliers
        // x months) / (months) / (suppliers x categories) rows, always.
        $invoiceAgg = DB::table('purchases')
            ->selectRaw("supplier_id, DATE_FORMAT(purchase_date, '%Y%m') as ym, "
                . "MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as max_seq")
            ->groupBy('supplier_id', 'ym')
            ->get();
        foreach ($invoiceAgg as $row) {
            $this->invoiceSeq[$row->supplier_id . '_' . $row->ym] = (int) $row->max_seq;
        }

        $saleAgg = DB::table('sales')
            ->selectRaw("DATE_FORMAT(sale_date, '%Y%m') as ym, "
                . "MAX(CAST(SUBSTRING_INDEX(sale_number, '-', -1) AS UNSIGNED)) as max_seq")
            ->groupBy('ym')
            ->get();
        foreach ($saleAgg as $row) {
            $this->saleSeq[$row->ym] = (int) $row->max_seq;
        }

        $lotAgg = DB::table('purchase_products')
            ->join('purchase_lines', 'purchase_products.purchase_line_id', '=', 'purchase_lines.id')
            ->whereNotNull('purchase_products.lot_code')
            ->selectRaw("SUBSTRING_INDEX(purchase_products.lot_code, '-', 1) as prefix, "
                . "SUBSTRING_INDEX(SUBSTRING_INDEX(purchase_products.lot_code, '-', 2), '-', -1) as cat_seq, "
                . 'purchase_lines.category_id as category_id, '
                . "MAX(CAST(SUBSTRING_INDEX(purchase_products.lot_code, '-', -1) AS UNSIGNED)) as max_unit_seq")
            ->groupBy('prefix', 'cat_seq', 'purchase_lines.category_id')
            ->get();
        foreach ($lotAgg as $row) {
            $key = $row->prefix . '|' . $row->category_id;
            $this->catSeqMap[$key]                 = $row->cat_seq;
            $this->maxCatSeqByPrefix[$row->prefix]  = max($this->maxCatSeqByPrefix[$row->prefix] ?? 0, (int) $row->cat_seq);
            $stub = $row->prefix . '-' . $row->cat_seq . '-';
            $this->unitCounterByStub[$stub] = max($this->unitCounterByStub[$stub] ?? 0, (int) $row->max_unit_seq);
        }

        $maxCustCode = DB::table('customers')->where('customer_code', 'like', 'CUST-%')
            ->pluck('customer_code')->map(fn ($c) => (int) substr($c, 5))->max();
        $this->customerCodeCounter = (int) $maxCustCode;
    }

    /* ═══════════════════════════════════════════════════════════════
     |  Sequence generators (in-memory, DB-free per call)
     ═══════════════════════════════════════════════════════════════ */

    private function ean13CheckDigit(string $first12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $first12[$i];
            $sum  += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        return (string) ((10 - ($sum % 10)) % 10);
    }

    private function nextBarcode(): string
    {
        $this->barcodeCounter++;
        $base = '200' . str_pad((string) $this->barcodeCounter, 9, '0', STR_PAD_LEFT);
        return $base . $this->ean13CheckDigit($base);
    }

    private function nextSku(int $categoryId, string $code): string
    {
        $stub = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code)) . '-';
        $this->skuCounter[$categoryId] = ($this->skuCounter[$categoryId] ?? 0) + 1;
        return $stub . str_pad((string) $this->skuCounter[$categoryId], 5, '0', STR_PAD_LEFT);
    }

    private function nextInvoiceNumber(int $supplierId, string $prefix, Carbon $date): string
    {
        $ym  = $date->format('Ym');
        $key = $supplierId . '_' . $ym;
        $this->invoiceSeq[$key] = ($this->invoiceSeq[$key] ?? 0) + 1;
        return "{$prefix}-{$ym}-" . str_pad((string) $this->invoiceSeq[$key], 4, '0', STR_PAD_LEFT);
    }

    private function nextSaleNumber(Carbon $date): string
    {
        $ym = $date->format('Ym');
        $this->saleSeq[$ym] = ($this->saleSeq[$ym] ?? 0) + 1;
        return "SALE-{$ym}-" . str_pad((string) $this->saleSeq[$ym], 4, '0', STR_PAD_LEFT);
    }

    private function nextLotCode(string $prefix, int $categoryId): string
    {
        $key = $prefix . '|' . $categoryId;
        if (! isset($this->catSeqMap[$key])) {
            $next = ($this->maxCatSeqByPrefix[$prefix] ?? 0) + 1;
            $this->catSeqMap[$key]           = str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $this->maxCatSeqByPrefix[$prefix] = $next;
        }
        $catSeq = $this->catSeqMap[$key];
        $stub   = "{$prefix}-{$catSeq}-";
        $this->unitCounterByStub[$stub] = ($this->unitCounterByStub[$stub] ?? 0) + 1;
        return $stub . str_pad((string) $this->unitCounterByStub[$stub], 3, '0', STR_PAD_LEFT);
    }

    private function nextCustomerCode(): string
    {
        $this->customerCodeCounter++;
        return 'CUST-' . str_pad((string) $this->customerCodeCounter, 4, '0', STR_PAD_LEFT);
    }

    /* ═══════════════════════════════════════════════════════════════
     |  Stock pool -- O(1) random pick + O(1) removal.
     ═══════════════════════════════════════════════════════════════ */

    private function addToPool(array &$pool, array &$order, array &$index, int $id, array $data): void
    {
        $pool[$id]  = $data;
        $index[$id] = count($order);
        $order[]    = $id;
    }

    private function pickRandomPoolKey(array $order): ?int
    {
        if (empty($order)) {
            return null;
        }
        return $order[mt_rand(0, count($order) - 1)];
    }

    private function decrementPool(array &$pool, array &$order, array &$index, int $id, int $qty): void
    {
        $pool[$id]['remaining'] -= $qty;
        if ($pool[$id]['remaining'] <= 0) {
            $idx      = $index[$id];
            $lastIdx  = count($order) - 1;
            $lastId   = $order[$lastIdx];
            $order[$idx]    = $lastId;
            $index[$lastId] = $idx;
            array_pop($order);
            unset($index[$id], $pool[$id]);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     |  Small helpers
     ═══════════════════════════════════════════════════════════════ */

    private function weighted(array $weights): string
    {
        $total = array_sum($weights);
        $r     = mt_rand(1, max(1, $total));
        $cum   = 0;
        foreach ($weights as $key => $w) {
            $cum += $w;
            if ($r <= $cum) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }

    private function randomDateBetween(Carbon $start, Carbon $end): Carbon
    {
        $startTs = $start->timestamp;
        $endTs   = max($startTs, $end->timestamp);
        return Carbon::createFromTimestamp(mt_rand($startTs, $endTs));
    }

    private function capDate(Carbon $d): Carbon
    {
        $now = Carbon::now();
        return $d->greaterThan($now) ? $now : $d;
    }

    private function caratRange(array $cat): array
    {
        if ($cat['rough']) {
            return [1.0, 20.0];
        }
        return match ($cat['stone_type']) {
            'Diamond' => [0.2, 3.0],
            default   => [0.3, 8.0],
        };
    }

    private function caratPriceRange(array $cat): array
    {
        if ($cat['rough']) {
            return [400, 5000];
        }
        return match ($cat['stone_type']) {
            'Diamond'  => [15000, 150000],
            'Ruby'     => [3000, 40000],
            'Sapphire' => [2000, 30000],
            'Emerald'  => [2000, 25000],
            default    => [500, 8000],
        };
    }

    private function productTitle(array $cat): string
    {
        $adjectives = ['Natural', 'Certified', 'Untreated', 'Fine', 'Premium', 'Rare', 'Exceptional'];
        return $this->faker->randomElement($adjectives) . ' ' . $cat['name'];
    }

    /**
     * Plan a purchase's payment rows without touching the DB. Mirrors
     * PurchaseService::recalculatePayments() math (unpaid / partial /
     * paid thresholds) so the header fields this returns are exactly
     * what that method would have computed.
     *
     * @return array{0: float, 1: array<int, array{amount: float, method: string, date: Carbon}>}
     */
    private function planPurchasePayments(float $grandTotal, Carbon $date, string $status): array
    {
        if ($status === 'cancelled' || $grandTotal <= 0) {
            return [0.0, []];
        }

        $bucket = $status === 'posted'
            ? $this->weighted(['paid' => 40, 'partial' => 35, 'unpaid' => 25])
            : $this->weighted(['paid' => 15, 'partial' => 20, 'unpaid' => 65]); // drafts are rarely paid

        if ($bucket === 'unpaid') {
            return [0.0, []];
        }

        $target = $bucket === 'paid' ? $grandTotal : round($grandTotal * (mt_rand(15, 85) / 100), 2);
        $split  = $this->faker->boolean(25) ? 2 : 1;

        $payments  = [];
        $remaining = $target;
        for ($i = 0; $i < $split; $i++) {
            $amount = ($i === $split - 1) ? $remaining : round($target * (mt_rand(30, 70) / 100), 2);
            $amount = min($amount, $remaining);
            if ($amount <= 0) {
                continue;
            }
            $payments[] = [
                'amount' => $amount,
                'method' => $this->weighted(['cash' => 30, 'bank_transfer' => 35, 'upi' => 20, 'cheque' => 10, 'card' => 5]),
                'date'   => $this->randomDateBetween($date, $this->capDate($date->copy()->addDays(30))),
            ];
            $remaining -= $amount;
        }

        $paidAmount = round(array_sum(array_column($payments, 'amount')), 2);
        return [$paidAmount, $payments];
    }

    /**
     * Same idea for a sale -- single payment row (POS-realistic), higher
     * paid-in-full weighting than purchases.
     *
     * @return array{0: float, 1: array<int, array{amount: float, method: string, date: Carbon}>}
     */
    private function planSalePayments(float $subtotal, Carbon $date): array
    {
        if ($subtotal <= 0) {
            return [0.0, []];
        }

        $bucket = $this->weighted(['paid' => 55, 'partial' => 30, 'unpaid' => 15]);
        if ($bucket === 'unpaid') {
            return [0.0, []];
        }

        $target = $bucket === 'paid' ? $subtotal : round($subtotal * (mt_rand(20, 80) / 100), 2);

        return [$target, [[
            'amount' => $target,
            'method' => $this->weighted(['cash' => 35, 'card' => 20, 'upi' => 30, 'bank_transfer' => 10, 'cheque' => 5]),
            'date'   => $date->copy(),
        ]]];
    }

    /* ═══════════════════════════════════════════════════════════════
     |  Customers
     ═══════════════════════════════════════════════════════════════ */

    private function createCustomers(int $count): void
    {
        $rows = [];
        $now  = now();
        for ($i = 0; $i < $count; $i++) {
            $isCompany = $this->faker->boolean(20);
            $rows[] = [
                'customer_code'   => $this->nextCustomerCode(),
                'name'            => $this->faker->name(),
                'company_name'    => $isCompany ? $this->faker->company() : null,
                'customer_type'   => $this->weighted(['retail' => 70, 'wholesale' => 20, 'walk_in' => 10]),
                'email'           => $this->faker->unique()->safeEmail(),
                'phone'           => $this->faker->phoneNumber(),
                'alternate_phone' => null,
                'gst_number'      => null,
                'pan_number'      => null,
                'address_line1'   => $this->faker->streetAddress(),
                'address_line2'   => null,
                'city'            => $this->faker->city(),
                'state'           => $this->faker->state(),
                'country'         => 'India',
                'zip_code'        => $this->faker->postcode(),
                'status'          => 1,
                'notes'           => null,
                'created_by'      => $this->adminId,
                'updated_by'      => $this->adminId,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('customers')->insert($chunk);
        }

        $this->customerIds = DB::table('customers')
            ->whereIn('customer_code', array_column($rows, 'customer_code'))
            ->pluck('id')->all();

        if ($this->walkinCustomerId) {
            $this->customerIds[] = $this->walkinCustomerId;
        }
    }

    /* ═══════════════════════════════════════════════════════════════
     |  Purchases -- each row creates its own sellable Product + primary
     |  Barcode immediately (mirrors PurchaseService::syncLines()).
     ═══════════════════════════════════════════════════════════════ */

    private function generatePurchases(int $target, int $chunkSize): void
    {
        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $rangeStart  = Carbon::now()->subMonths(18);
        $rangeEnd    = Carbon::now();
        $supplierIds = array_keys($this->suppliers);
        $categoryIds = array_keys($this->gemCategories);

        $done = 0;
        while ($done < $target) {
            $batchSize = min($chunkSize, $target - $done);

            DB::transaction(function () use ($batchSize, $supplierIds, $categoryIds, $rangeStart, $rangeEnd) {
                $now   = now();
                $plans = [];

                // ── 1. Build the full in-memory plan for this batch ──
                for ($i = 0; $i < $batchSize; $i++) {
                    $supplierId = $supplierIds[array_rand($supplierIds)];
                    $prefix     = $this->suppliers[$supplierId]['invoice_prefix'];
                    $lotPrefix  = $this->suppliers[$supplierId]['lot_prefix'];
                    $date       = $this->randomDateBetween($rangeStart, $rangeEnd);
                    $createdAt  = $date->copy()->setTime(mt_rand(9, 18), mt_rand(0, 59), mt_rand(0, 59));
                    $status     = $this->weighted(['posted' => 85, 'draft' => 10, 'cancelled' => 5]);
                    $locationId = $this->locationIds[array_rand($this->locationIds)];

                    $lineCount  = mt_rand(1, 4);
                    $lines      = [];
                    $grandTotal = 0.0;

                    for ($l = 0; $l < $lineCount; $l++) {
                        $categoryId = $categoryIds[array_rand($categoryIds)];
                        $cat        = $this->gemCategories[$categoryId];
                        $type       = $this->weighted(['piece' => 55, 'box' => 45]);
                        $packageQty = $type === 'box' ? mt_rand(2, 6) : 1;
                        $totalQty   = $type === 'piece' ? 1 : $packageQty;

                        $priceRange = $this->caratPriceRange($cat);
                        $caratRange = $this->caratRange($cat);

                        // Line-level product template -- stamped onto
                        // every product this line creates below, exactly
                        // like PurchaseService::syncLines()' $lineFields.
                        // ~30% list on the website, matching the density
                        // the old two-stage (purchase hint -> packing
                        // roll) flow produced in practice.
                        $websiteEnabled = $this->faker->boolean(30);

                        $rows = [];
                        for ($r = 0; $r < $totalQty; $r++) {
                            $carat = round(mt_rand((int) round($caratRange[0] * 100), (int) round($caratRange[1] * 100)) / 100, 3);
                            $price = mt_rand($priceRange[0], $priceRange[1]);
                            $qty   = $type === 'piece' ? mt_rand(1, 5) : mt_rand(1, 2);
                            // Selling price -- seeds both the row's own
                            // website_price and the Product it creates.
                            $websitePrice = round($carat * $price * (mt_rand(150, 300) / 100), 2);

                            $rows[] = [
                                'carat_weight'  => $carat,
                                'price'         => $price,
                                'qty'           => $qty,
                                'website_price' => $websitePrice,
                                'rack_id'       => $this->rackIds ? $this->rackIds[array_rand($this->rackIds)] : null,
                            ];

                            $grandTotal += $carat * $price;
                        }

                        $lines[] = [
                            'category_id'      => $categoryId,
                            'cat'              => $cat,
                            'type'             => $type,
                            'package_qty'      => $packageQty,
                            'total_qty'        => $totalQty,
                            'title'            => $this->productTitle($cat),
                            'website_enabled'  => $websiteEnabled,
                            'rows'             => $rows,
                            'line_total'       => array_sum(array_map(fn ($r) => $r['carat_weight'] * $r['price'], $rows)),
                        ];
                    }

                    [$paidAmount, $payments] = $this->planPurchasePayments($grandTotal, $date, $status);
                    $dueAmount     = round(max(0, $grandTotal - $paidAmount), 2);
                    $paymentStatus = $paidAmount <= 0.0001
                        ? 'unpaid'
                        : (($paidAmount + 0.0001 >= $grandTotal) ? 'paid' : 'partial');

                    $plans[] = [
                        'supplier_id'    => $supplierId,
                        'prefix'         => $prefix,
                        'lot_prefix'     => $lotPrefix,
                        'date'           => $date,
                        'created_at'     => $createdAt,
                        'status'         => $status,
                        'location_id'    => $locationId,
                        'tax_type'       => $this->weighted(['none' => 60, 'igst' => 25, 'cgst_sgst' => 15]),
                        'lines'          => $lines,
                        'grand_total'    => round($grandTotal, 2),
                        'paid_amount'    => $paidAmount,
                        'due_amount'     => $dueAmount,
                        'payment_status' => $paymentStatus,
                        'payments'       => $payments,
                    ];
                }

                // ── 2. Purchases header ──
                $purchaseRows = [];
                foreach ($plans as $p) {
                    $purchaseRows[] = [
                        'invoice_number'  => $this->nextInvoiceNumber((int) $p['supplier_id'], $p['prefix'], $p['date']),
                        'purchase_date'   => $p['date']->toDateString(),
                        'supplier_id'     => $p['supplier_id'],
                        'location_id'     => $p['location_id'],
                        'tax_type'        => $p['tax_type'],
                        'subtotal'        => $p['grand_total'],
                        'tax_total'       => 0,
                        'discount_total'  => 0,
                        'grand_total'     => $p['grand_total'],
                        'paid_amount'     => $p['paid_amount'],
                        'due_amount'      => $p['due_amount'],
                        'payment_status'  => $p['payment_status'],
                        'note'            => $this->faker->boolean(20) ? $this->faker->sentence(8) : null,
                        'status'          => $p['status'],
                        'created_by'      => $this->adminId,
                        'updated_by'      => $this->adminId,
                        'created_at'      => $p['created_at'],
                        'updated_at'      => $now,
                    ];
                }
                DB::table('purchases')->insert($purchaseRows);
                $firstPurchaseId = (int) DB::getPdo()->lastInsertId();
                foreach ($plans as $idx => &$p) {
                    $p['id'] = $firstPurchaseId + $idx;
                }
                unset($p);

                // ── 3. Purchase lines. Also stashes each line's rolled
                //      description/colour/clarity/cut/treatment onto the
                //      plan (product_template) so step 4 stamps the SAME
                //      values onto every product the line creates, rather
                //      than re-rolling them per row. ──
                $lineRows = [];
                $lineMeta = [];
                foreach ($plans as $pIdx => $p) {
                    foreach ($p['lines'] as $lIdx => $line) {
                        $countryId    = $this->countryIds ? $this->countryIds[array_rand($this->countryIds)] : null;
                        $shortDesc    = $this->faker->boolean(40) ? $this->faker->sentence(10) : null;
                        $colourGrade  = $this->faker->randomElement(self::COLOUR_GRADES);
                        $clarityGrade = $this->faker->randomElement(Product::CLARITY_GRADES);
                        $cutShape     = $this->faker->randomElement(Product::CUT_SHAPES);
                        $treatment    = $this->faker->randomElement(Product::TREATMENTS);

                        $lineRows[] = [
                            'purchase_id'          => $p['id'],
                            'product_id'           => null,
                            'title'                => $line['title'],
                            'category_id'          => $line['category_id'],
                            'short_description'    => $shortDesc,
                            'full_description'     => null,
                            'country_of_origin'    => null,
                            'country_of_origin_id' => $countryId,
                            'notes_tags'           => null,
                            'website_price'        => null,
                            'website_enabled'      => $line['website_enabled'],
                            'carat_weight'         => $line['rows'][0]['carat_weight'] ?? null,
                            'stone_type'           => $line['cat']['stone_type'],
                            'colour_grade'         => $colourGrade,
                            'clarity_grade'        => $clarityGrade,
                            'cut_shape'            => $cutShape,
                            'treatment'            => $treatment,
                            'type'                 => $line['type'],
                            'package_name'         => $line['type'] === 'piece' ? 'Piece' : 'Box',
                            'package_qty'          => $line['package_qty'],
                            'total_qty'            => $line['total_qty'],
                            'unit_contains'        => null,
                            'subtotal'             => round($line['line_total'], 2),
                            'total'                => round($line['line_total'], 2),
                            'remarks'              => null,
                            'created_at'           => $p['created_at'],
                            'updated_at'           => $now,
                        ];
                        $lineMeta[] = [$pIdx, $lIdx];

                        $plans[$pIdx]['lines'][$lIdx]['product_template'] = [
                            'short_description'    => $shortDesc,
                            'country_of_origin_id' => $countryId,
                            'stone_type'           => $line['cat']['stone_type'],
                            'colour_grade'         => $colourGrade,
                            'clarity_grade'        => $clarityGrade,
                            'cut_shape'            => $cutShape,
                            'treatment'            => $treatment,
                        ];
                    }
                }
                DB::table('purchase_lines')->insert($lineRows);
                $firstLineId = (int) DB::getPdo()->lastInsertId();
                foreach ($lineMeta as $offset => [$pIdx, $lIdx]) {
                    $plans[$pIdx]['lines'][$lIdx]['id'] = $firstLineId + $offset;
                }

                // ── 4. Products -- one per row, created directly
                //      alongside the purchase (see
                //      PurchaseService::syncLines()). Must exist before
                //      purchase_products, which references product_id. ──
                $productRows = [];
                $productMeta = []; // [pIdx, lIdx, rIdx]
                foreach ($plans as $pIdx => $p) {
                    foreach ($p['lines'] as $lIdx => $line) {
                        $tpl = $line['product_template'];
                        foreach ($line['rows'] as $rIdx => $row) {
                            $productRows[] = [
                                'title'                => $line['title'],
                                'sku'                  => $this->nextSku($line['category_id'], $line['cat']['code']),
                                'category_id'          => $line['category_id'],
                                'short_description'    => $tpl['short_description'],
                                'full_description'     => null,
                                'country_of_origin'    => null,
                                'country_of_origin_id' => $tpl['country_of_origin_id'],
                                'notes_tags'           => null,
                                // Storefront listing needs BOTH
                                // website_enabled and an Active status
                                // (see WebsiteController).
                                'status'               => $line['website_enabled'] ? 1 : ($this->faker->boolean(85) ? 1 : 0),
                                'pack_type'            => 'piece',
                                'outer_pack_name'      => null,
                                'outer_pack_contains'  => null,
                                'inner_pack_name'      => null,
                                'inner_pack_contains'  => null,
                                'carat_weight'         => $row['carat_weight'],
                                'stone_type'           => $tpl['stone_type'],
                                'colour_grade'         => $tpl['colour_grade'],
                                'clarity_grade'        => $tpl['clarity_grade'],
                                'cut_shape'            => $tpl['cut_shape'],
                                'treatment'            => $tpl['treatment'],
                                'certificate_number'   => $this->faker->boolean(15) ? strtoupper($this->faker->bothify('GIA-########')) : null,
                                'website_enabled'      => $line['website_enabled'],
                                'website_price'        => $row['website_price'],
                                'website_title'        => null,
                                'website_description'  => null,
                                'featured_product'     => $line['website_enabled'] && $this->faker->boolean(10),
                                'website_sort_order'   => null,
                                'website_enabled_at'   => $line['website_enabled'] ? $p['created_at'] : null,
                                'website_disabled_at'  => null,
                                'created_by'           => $this->adminId,
                                'updated_by'           => $this->adminId,
                                'created_at'           => $p['created_at'],
                                'updated_at'           => $now,
                            ];
                            $productMeta[] = [$pIdx, $lIdx, $rIdx];
                        }
                    }
                }
                DB::table('products')->insert($productRows);
                $firstProductId = (int) DB::getPdo()->lastInsertId();
                foreach ($productMeta as $offset => [$pIdx, $lIdx, $rIdx]) {
                    $plans[$pIdx]['lines'][$lIdx]['rows'][$rIdx]['product_id'] = $firstProductId + $offset;
                }

                // ── 5. Barcodes -- one primary EAN-13 per product ──
                $barcodeRows = [];
                foreach ($plans as $p) {
                    foreach ($p['lines'] as $line) {
                        foreach ($line['rows'] as $row) {
                            $barcodeRows[] = [
                                'product_id'      => $row['product_id'],
                                'barcode_value'   => $this->nextBarcode(),
                                'barcode_format'  => 'EAN-13',
                                'barcode_label'   => null,
                                'is_primary'      => 1,
                                'sequence_number' => 1,
                                'created_by'      => $this->adminId,
                                'updated_by'      => $this->adminId,
                                'created_at'      => $p['created_at'],
                                'updated_at'      => $now,
                            ];
                        }
                    }
                }
                DB::table('barcodes')->insert($barcodeRows);

                // ── 6. purchase_products -- the inventory rows, each
                //      already carrying the product_id from step 4. ──
                $ppRows = [];
                $ppMeta = [];
                foreach ($plans as $pIdx => $p) {
                    foreach ($p['lines'] as $lIdx => $line) {
                        foreach ($line['rows'] as $rIdx => $row) {
                            $lotCode = $this->nextLotCode($p['lot_prefix'], $line['category_id']);
                            $ppRows[] = [
                                'purchase_line_id' => $line['id'],
                                'product_id'       => $row['product_id'],
                                'qty'              => $row['qty'],
                                'carat_weight'     => $row['carat_weight'],
                                'barcode'          => null,
                                'lot_code'         => $lotCode,
                                'rack_id'          => $row['rack_id'],
                                'serial_number'    => null,
                                'price'            => $row['price'],
                                'website_price'    => $row['website_price'],
                                'tax_percent'      => 0,
                                'tax_amount'       => 0,
                                'discount_percent' => 0,
                                'discount_amount'  => 0,
                                'expiry_date'      => null,
                                'manufacture_date' => null,
                                'remarks'          => null,
                                'created_at'       => $p['created_at'],
                                'updated_at'       => $now,
                            ];
                            $ppMeta[] = [$pIdx, $lIdx, $rIdx];
                        }
                    }
                }
                DB::table('purchase_products')->insert($ppRows);
                $firstPpId = (int) DB::getPdo()->lastInsertId();
                foreach ($ppMeta as $offset => [$pIdx, $lIdx, $rIdx]) {
                    $plans[$pIdx]['lines'][$lIdx]['rows'][$rIdx]['pp_id'] = $firstPpId + $offset;
                }

                // ── 7. Stock movements (IN) + stock pool. Posted
                //      purchases only. Every row already has a product,
                //      so every movement carries product_id. ──
                $movementRows = [];
                foreach ($plans as $p) {
                    if ($p['status'] !== 'posted') {
                        continue;
                    }
                    foreach ($p['lines'] as $line) {
                        foreach ($line['rows'] as $row) {
                            if ($row['qty'] <= 0) {
                                continue;
                            }
                            $movementRows[] = [
                                'purchase_product_id' => $row['pp_id'],
                                'product_id'          => $row['product_id'],
                                'location_id'         => $p['location_id'],
                                'direction'           => 'in',
                                'qty'                 => $row['qty'],
                                'reason'              => 'purchase',
                                'source_type'         => 'purchase',
                                'source_id'           => $p['id'],
                                'source_line_id'      => $line['id'],
                                'rack_id'             => $row['rack_id'],
                                'movement_date'       => $p['date']->toDateString(),
                                'notes'               => null,
                                'created_by'          => $this->adminId,
                                'created_at'          => $p['created_at'],
                                'updated_at'          => $now,
                            ];

                            $this->addToPool($this->stockPool, $this->stockPoolOrder, $this->stockPoolIndex, $row['pp_id'], [
                                'product_id'    => $row['product_id'],
                                'location_id'   => $p['location_id'],
                                'price'         => $row['price'],
                                'remaining'     => $row['qty'],
                                // Int timestamp, not a Carbon copy -- at
                                // 50K-70K pool entries, one Carbon object
                                // per row is real memory. Converted back to
                                // Carbon only when actually consumed.
                                'purchase_date' => $p['date']->timestamp,
                            ]);
                        }
                    }
                }
                if (! empty($movementRows)) {
                    DB::table('stock_movements')->insert($movementRows);
                }

                // ── 8. Purchase payments ──
                $paymentRows = [];
                foreach ($plans as $p) {
                    foreach ($p['payments'] as $pay) {
                        $paymentRows[] = [
                            'purchase_id'      => $p['id'],
                            'payment_date'     => $pay['date']->toDateString(),
                            'amount'           => $pay['amount'],
                            'payment_method'   => $pay['method'],
                            'reference_number' => null,
                            'notes'            => null,
                            'created_by'       => $this->adminId,
                            'created_at'       => $pay['date'],
                            'updated_at'       => $now,
                        ];
                    }
                }
                if (! empty($paymentRows)) {
                    DB::table('purchase_payments')->insert($paymentRows);
                }
            });

            $done += $batchSize;
            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine();
    }

    /* ═══════════════════════════════════════════════════════════════
     |  Sales -- draw only from the stock pool credited by posted
     |  Purchases above.
     ═══════════════════════════════════════════════════════════════ */

    private function generateSales(int $target, int $chunkSize): int
    {
        if (empty($this->stockPoolOrder)) {
            $this->warn('No stock available (no posted purchases) -- skipping sales.');
            return 0;
        }

        $bar = $this->output->createProgressBar($target);
        $bar->start();

        $customerIds    = $this->customerIds;
        $locationIds    = $this->locationIds;
        $channelWeights = ['pos' => 55, 'website' => 20, 'ebay' => 10, 'sukainagems' => 10, 'catawiki' => 5];

        $created = 0;
        while ($created < $target && ! empty($this->stockPoolOrder)) {
            $batchSize = min($chunkSize, $target - $created);

            $actualInBatch = DB::transaction(function () use ($batchSize, $customerIds, $locationIds, $channelWeights) {
                $plans = [];

                for ($i = 0; $i < $batchSize; $i++) {
                    if (empty($this->stockPoolOrder)) {
                        break;
                    }

                    $lineCount       = mt_rand(1, 3);
                    $lines           = [];
                    $subtotal        = 0.0;
                    $saleDateFloorTs = null;

                    for ($l = 0; $l < $lineCount; $l++) {
                        $ppId = $this->pickRandomPoolKey($this->stockPoolOrder);
                        if ($ppId === null) {
                            break;
                        }

                        $piece = $this->stockPool[$ppId];
                        $qty   = min(mt_rand(1, 3), $piece['remaining']);
                        if ($qty <= 0) {
                            continue;
                        }

                        $costPrice = (float) $piece['price'];
                        $unitPrice = round($costPrice * (mt_rand(130, 280) / 100), 2);

                        $lines[] = [
                            'product_id'          => $piece['product_id'],
                            'purchase_product_id' => $ppId,
                            'qty'                 => $qty,
                            'unit_price'          => $unitPrice,
                            'cost_price'          => $costPrice,
                            'location_id'         => $piece['location_id'],
                        ];

                        $subtotal += $qty * $unitPrice;
                        if ($saleDateFloorTs === null || $piece['purchase_date'] > $saleDateFloorTs) {
                            $saleDateFloorTs = $piece['purchase_date'];
                        }

                        $this->decrementPool($this->stockPool, $this->stockPoolOrder, $this->stockPoolIndex, $ppId, $qty);
                    }

                    if (empty($lines)) {
                        continue;
                    }

                    $saleDateFloor = Carbon::createFromTimestamp($saleDateFloorTs ?? Carbon::now()->subMonths(18)->timestamp);
                    $upperBound    = $this->capDate($saleDateFloor->copy()->addDays(mt_rand(1, 200)));
                    $saleDate      = $this->randomDateBetween($saleDateFloor, $upperBound);
                    $createdAt     = $saleDate->copy()->setTime(mt_rand(9, 20), mt_rand(0, 59), mt_rand(0, 59));

                    $status = $this->weighted(['draft' => 10, 'posted' => 50, 'completed' => 40]);

                    if ($status === 'draft') {
                        $paidAmount = 0.0;
                        $payments   = [];
                    } else {
                        [$paidAmount, $payments] = $this->planSalePayments($subtotal, $saleDate);
                    }
                    $balanceDue    = round(max(0, $subtotal - $paidAmount), 2);
                    $paymentStatus = $paidAmount <= 0.0001
                        ? 'unpaid'
                        : (($paidAmount + 0.0001 >= $subtotal) ? 'paid' : 'partial');

                    $customerId = ($customerIds && $this->faker->boolean(85))
                        ? $customerIds[array_rand($customerIds)]
                        : $this->walkinCustomerId;

                    $plans[] = [
                        'customer_id'    => $customerId ?? $this->walkinCustomerId,
                        'location_id'    => $locationIds[array_rand($locationIds)],
                        'channel_code'   => $this->weighted($channelWeights),
                        'date'           => $saleDate,
                        'created_at'     => $createdAt,
                        'status'         => $status,
                        'lines'          => $lines,
                        'subtotal'       => round($subtotal, 2),
                        'paid_amount'    => $paidAmount,
                        'balance_due'    => $balanceDue,
                        'payment_status' => $paymentStatus,
                        'payments'       => $payments,
                    ];
                }

                if (empty($plans)) {
                    return 0;
                }

                $now = now();

                // ── Sale headers ──
                $saleRows = [];
                foreach ($plans as $p) {
                    $saleRows[] = [
                        'sale_number'       => $this->nextSaleNumber($p['date']),
                        'sale_date'         => $p['date']->toDateString(),
                        'customer_id'       => $p['customer_id'],
                        'location_id'       => $p['location_id'],
                        'channel_id'        => $this->channelIds[$p['channel_code']] ?? null,
                        'salesperson_id'    => $this->adminId,
                        'tax_type'          => 'none',
                        'subtotal'          => $p['subtotal'],
                        'tax_total'         => 0,
                        'discount_total'    => 0,
                        'shipping_charge'   => 0,
                        'grand_total'       => $p['subtotal'],
                        'paid_amount'       => $p['paid_amount'],
                        'balance_due'       => $p['balance_due'],
                        'payment_status'    => $p['payment_status'],
                        'status'            => $p['status'],
                        'note'              => null,
                        'external_ref'      => null,
                        'external_order_id' => null,
                        'import_batch_id'   => null,
                        'created_by'        => $this->adminId,
                        'updated_by'        => $this->adminId,
                        'created_at'        => $p['created_at'],
                        'updated_at'        => $now,
                    ];
                }
                DB::table('sales')->insert($saleRows);
                $firstSaleId = (int) DB::getPdo()->lastInsertId();
                foreach ($plans as $idx => &$p) {
                    $p['id'] = $firstSaleId + $idx;
                }
                unset($p);

                // ── Sale lines ──
                $lineRows = [];
                $lineMeta = [];
                foreach ($plans as $pIdx => $p) {
                    foreach ($p['lines'] as $lIdx => $line) {
                        $total = round($line['qty'] * $line['unit_price'], 2);
                        $lineRows[] = [
                            'sale_id'             => $p['id'],
                            'product_id'          => $line['product_id'],
                            'purchase_product_id' => $line['purchase_product_id'],
                            'barcode'             => null,
                            'qty'                 => $line['qty'],
                            'unit_price'          => $line['unit_price'],
                            'tax_percent'         => 0,
                            'tax_amount'          => 0,
                            'discount_percent'    => 0,
                            'discount_amount'     => 0,
                            'subtotal'            => $total,
                            'total'               => $total,
                            'cost_price'          => $line['cost_price'],
                            'notes'               => null,
                            'created_at'          => $p['created_at'],
                            'updated_at'          => $now,
                        ];
                        $lineMeta[] = [$pIdx, $lIdx];
                    }
                }
                DB::table('sale_lines')->insert($lineRows);
                $firstLineId = (int) DB::getPdo()->lastInsertId();
                foreach ($lineMeta as $offset => [$pIdx, $lIdx]) {
                    $plans[$pIdx]['lines'][$lIdx]['id'] = $firstLineId + $offset;
                }

                // ── Stock movements (OUT) -- draft sales have no stock impact ──
                $movementRows = [];
                foreach ($plans as $p) {
                    if ($p['status'] === 'draft') {
                        continue;
                    }
                    foreach ($p['lines'] as $line) {
                        $movementRows[] = [
                            'purchase_product_id' => $line['purchase_product_id'],
                            'product_id'          => $line['product_id'],
                            'location_id'         => $line['location_id'],
                            'direction'           => 'out',
                            'qty'                 => $line['qty'],
                            'reason'              => 'sale',
                            'source_type'         => 'sale',
                            'source_id'           => $p['id'],
                            'source_line_id'      => $line['id'],
                            'rack_id'             => null,
                            'movement_date'       => $p['date']->toDateString(),
                            'notes'               => null,
                            'created_by'          => $this->adminId,
                            'created_at'          => $p['created_at'],
                            'updated_at'          => $now,
                        ];
                    }
                }
                if (! empty($movementRows)) {
                    DB::table('stock_movements')->insert($movementRows);
                }

                // ── Sale payments -- draft sales have none ──
                $paymentRows = [];
                foreach ($plans as $p) {
                    if ($p['status'] === 'draft') {
                        continue;
                    }
                    foreach ($p['payments'] as $pay) {
                        $paymentRows[] = [
                            'sale_id'          => $p['id'],
                            'payment_date'     => $pay['date']->toDateString(),
                            'amount'           => $pay['amount'],
                            'payment_method'   => $pay['method'],
                            'reference_number' => null,
                            'notes'            => null,
                            'created_by'       => $this->adminId,
                            'created_at'       => $pay['date'],
                            'updated_at'       => $now,
                        ];
                    }
                }
                if (! empty($paymentRows)) {
                    DB::table('sale_payments')->insert($paymentRows);
                }

                return count($plans);
            });

            $created += $actualInBatch;
            $bar->advance($actualInBatch);

            if ($actualInBatch === 0) {
                break;
            }
        }

        $bar->finish();
        $this->newLine();

        return $created;
    }
}
