<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\StockAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAudit>
 */
class StockAuditFactory extends Factory
{
    protected $model = StockAudit::class;

    public function definition(): array
    {
        return [
            'audit_number'   => 'AUD-TEST-' . fake()->unique()->numberBetween(100000, 999999),
            'audit_date'     => now()->toDateString(),
            'location_id'    => Location::factory(),
            'status'         => StockAudit::STATUS_IN_PROGRESS,
            'expected_total' => 0,
            'matched_total'  => 0,
            'started_at'     => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status'       => StockAudit::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status'       => StockAudit::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}
