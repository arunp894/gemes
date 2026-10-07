<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Products point at the Treatment / Shape / Colour / Clarity master tables
 * through real foreign keys. The old free-text columns (treatment, cut_shape,
 * colour_grade, clarity_grade) stay as a mirrored copy of the master's name —
 * Product::booted() keeps them in step — so every existing reader keeps working.
 *
 * This migration also maps existing data: every distinct text value already on
 * a product is matched to a master row by name (case/space-insensitive), and a
 * master row is created for any value that has none, so nothing is lost.
 * Re-running is harmless.
 */
return new class extends Migration
{
    /** products text column => [master table, products FK column] */
    private const MAP = [
        'treatment'     => ['treatments', 'treatment_id'],
        'cut_shape'     => ['shapes', 'shape_id'],
        'colour_grade'  => ['colors', 'color_id'],
        'clarity_grade' => ['clarities', 'clarity_id'],
    ];

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (self::MAP as [$master, $fk]) {
                if (! Schema::hasColumn('products', $fk)) {
                    $table->foreignId($fk)->nullable()->after('status')
                        ->constrained($master)->nullOnDelete();
                }
            }
        });

        // Default options the old dropdowns offered, so the master lists
        // aren't empty on a fresh install.
        $defaults = [
            'treatments'  => Product::TREATMENTS,
            'shapes'      => Product::CUT_SHAPES,
            'clarities'   => Product::CLARITY_GRADES,
            'colors'      => [],
        ];
        foreach ($defaults as $master => $names) {
            foreach ($names as $name) {
                $this->masterId($master, $name);
            }
        }

        // Map existing products.
        foreach (self::MAP as $textCol => [$master, $fk]) {
            $values = DB::table('products')
                ->whereNotNull($textCol)->where($textCol, '!=', '')
                ->whereNull($fk)
                ->distinct()->pluck($textCol);

            foreach ($values as $value) {
                $id = $this->masterId($master, $value);
                if ($id) {
                    DB::table('products')->where($textCol, $value)->whereNull($fk)->update([$fk => $id]);
                }
            }

            // Normalise the mirrored text to the master's exact name (fixes case/spacing).
            DB::statement("UPDATE products p JOIN {$master} m ON m.id = p.{$fk} SET p.{$textCol} = m.name");
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (self::MAP as [$master, $fk]) {
                if (Schema::hasColumn('products', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
        });
    }

    /** Find a master row by trimmed name, or create it. Returns its id. */
    private function masterId(string $master, ?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        $name = Str::limit($name, 50, '');

        $existing = DB::table($master)->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            // A soft-deleted master would hide the value from the dropdown — bring it back.
            if ($existing->deleted_at !== null) {
                DB::table($master)->where('id', $existing->id)->update(['deleted_at' => null, 'status' => 1]);
            }
            return (int) $existing->id;
        }

        $base = Str::limit(Str::upper(Str::slug($name, '_')) ?: 'ITEM', 26, '');
        $code = $base;
        for ($i = 2; DB::table($master)->where('code', $code)->exists(); $i++) {
            $code = $base . '_' . $i;
        }

        return (int) DB::table($master)->insertGetId([
            'name'          => $name,
            'code'          => $code,
            'status'        => 1,
            'display_order' => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
};
