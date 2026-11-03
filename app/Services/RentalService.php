<?php

namespace App\Services;

use App\Models\PurchaseProduct;
use App\Models\Rental;
use App\Models\RentalLine;
use App\Models\RentalLog;
use App\Models\RentalPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stone rental lifecycle. Stock moves only through StockService
 * (rent_out on create, rent_return on return / cancel / lost).
 */
class RentalService
{
    public function __construct(private StockService $stock) {}

    /**
     * @param array{customer_id:int, location_id:int, start_at:string, due_at:string, note?:?string,
     *              lines:array, rent_paid?:float, deposit_paid?:float, payment_method?:string} $data
     */
    public function create(array $data): Rental
    {
        return DB::transaction(function () use ($data) {
            $start = Carbon::parse($data['start_at']);
            $due   = Carbon::parse($data['due_at']);
            if ($due->lte($start)) {
                throw new InvalidArgumentException('The "To" date must be after the "From" date.');
            }

            $rental = Rental::create([
                'rent_number' => Rental::generateNumber($start),
                'customer_id' => $data['customer_id'],
                'location_id' => $data['location_id'],
                'start_at'    => $start,
                'due_at'      => $due,
                'status'      => Rental::STATUS_ACTIVE,
                'note'        => $data['note'] ?? null,
            ]);

            foreach ($data['lines'] as $l) {
                $pp = PurchaseProduct::findOrFail($l['purchase_product_id']);
                $product = $pp->resolved_product;
                if (! $product) {
                    throw new InvalidArgumentException("Piece {$pp->lot_code} has no product.");
                }
                $rental->lines()->create([
                    'product_id'          => $product->id,
                    'purchase_product_id' => $pp->id,
                    'qty'                 => max(1, (int) $l['qty']),
                    'carat_weight'        => ! empty($l['carat_weight']) ? (float) $l['carat_weight'] : null,
                    'rent_amount'         => (float) ($l['rent_amount'] ?? 0),
                    'deposit_amount'      => (float) ($l['deposit_amount'] ?? 0),
                    'status'              => RentalLine::STATUS_OUT,
                ]);
            }

            $rental->update([
                'rent_total'    => $rental->lines()->sum('rent_amount'),
                'deposit_total' => $rental->lines()->sum('deposit_amount'),
            ]);

            $method = $data['payment_method'] ?? 'cash';
            $this->pay($rental, RentalPayment::TYPE_RENT, (float) ($data['rent_paid'] ?? 0), $method);
            $this->pay($rental, RentalPayment::TYPE_DEPOSIT, (float) ($data['deposit_paid'] ?? 0), $method);

            $this->stock->recordRentalOut($rental);
            $this->log($rental, 'created', $rental->lines()->count() . ' stone(s), due ' . $due->format('d M Y H:i'));

            return $rental->fresh(['lines', 'payments']);
        });
    }

    /**
     * Return and/or write off lines of an active rental.
     *
     * @param int[] $returnIds lines coming back
     * @param int[] $lostIds   lines not coming back (deposit kept, stock written off)
     */
    public function returnLines(Rental $rental, array $returnIds, array $lostIds, float $deduction, float $refund, string $method, ?string $note = null): Rental
    {
        if (! $rental->isActive()) {
            throw new InvalidArgumentException('Only an active rental can be returned.');
        }
        $ids = array_values(array_unique(array_merge($returnIds, $lostIds)));
        if (! $ids) {
            throw new InvalidArgumentException('Select at least one stone.');
        }

        return DB::transaction(function () use ($rental, $returnIds, $lostIds, $ids, $deduction, $refund, $method, $note) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            $lines  = $rental->lines()->where('status', RentalLine::STATUS_OUT)->whereIn('id', $ids)->get();
            if ($lines->count() !== count($ids)) {
                throw new InvalidArgumentException('Some selected stones are already returned.');
            }

            $refundable = 0.0;
            $forfeited  = 0.0;
            foreach ($lines as $line) {
                $lost = in_array($line->id, $lostIds, true);
                $this->stock->returnRentalLine($rental, $line, $lost);
                $line->update(['status' => $lost ? RentalLine::STATUS_LOST : RentalLine::STATUS_RETURNED, 'returned_at' => Rental::wallNow()]);
                $lost ? $forfeited += (float) $line->deposit_amount : $refundable += (float) $line->deposit_amount;
            }

            $deduction = round(max(0, $deduction), 2);
            $refund    = round(max(0, $refund), 2);
            // The refund cannot exceed this return's deposit less deductions, nor what is actually held.
            if ($refund > round($refundable - $deduction, 2) + 0.005 || $refund > $rental->depositHeld() + 0.005) {
                throw new InvalidArgumentException('Refund is more than the deposit available for these stones.');
            }

            $this->pay($rental, RentalPayment::TYPE_REFUND, $refund, $method, $note);
            $rental->deposit_refunded = (float) $rental->deposit_refunded + $refund;
            $rental->deduction_total  = (float) $rental->deduction_total + $deduction + $forfeited;

            if (! $rental->lines()->where('status', RentalLine::STATUS_OUT)->exists()) {
                $rental->status      = Rental::STATUS_RETURNED;
                $rental->returned_at = Rental::wallNow();
            }
            $rental->save();

            $this->log($rental, $lostIds ? 'lost' : 'returned',
                count($returnIds) . ' returned, ' . count($lostIds) . ' lost; deduction ' . $deduction . '; refund ' . $refund . ($note ? ' - ' . $note : ''));

            return $rental->fresh(['lines', 'payments']);
        });
    }

    /** Undo a rental where nothing has come back yet: stones return to stock, everything collected is refunded. */
    public function cancel(Rental $rental, string $method = 'cash'): Rental
    {
        if (! $rental->isActive() || $rental->lines()->where('status', '!=', RentalLine::STATUS_OUT)->exists()) {
            throw new InvalidArgumentException('Only an active rental with no returned stones can be cancelled.');
        }

        return DB::transaction(function () use ($rental, $method) {
            foreach ($rental->lines as $line) {
                $this->stock->returnRentalLine($rental, $line, false, 'Rental cancelled ' . $rental->rent_number);
                $line->update(['status' => RentalLine::STATUS_RETURNED, 'returned_at' => Rental::wallNow()]);
            }
            $back = round((float) $rental->rent_paid + (float) $rental->deposit_paid - (float) $rental->deposit_refunded, 2);
            $this->pay($rental, RentalPayment::TYPE_REFUND, $back, $method, 'Rental cancelled');
            $rental->update(['status' => Rental::STATUS_CANCELLED, 'returned_at' => Rental::wallNow()]);
            $this->log($rental, 'cancelled', 'Refunded ' . $back);

            return $rental->fresh(['lines', 'payments']);
        });
    }

    public function extend(Rental $rental, string $newDue): Rental
    {
        $due = Carbon::parse($newDue);
        if (! $rental->isActive() || $due->lte($rental->due_at)) {
            throw new InvalidArgumentException('New due date must be later than the current one.');
        }
        $old = $rental->due_at->format('d M Y H:i');
        $rental->update(['due_at' => $due]);
        $this->log($rental, 'extended', "Due {$old} to " . $due->format('d M Y H:i'));

        return $rental;
    }

    /** Book one payment row and roll it into the header counters. */
    private function pay(Rental $rental, string $type, float $amount, string $method, ?string $note = null): void
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return;
        }
        $rental->payments()->create([
            'type' => $type, 'payment_method' => $method, 'amount' => $amount,
            'payment_date' => Rental::wallNow()->toDateString(), 'note' => $note, 'created_by' => auth()->id(),
        ]);
        match ($type) {
            RentalPayment::TYPE_RENT    => $rental->rent_paid    = (float) $rental->rent_paid + $amount,
            RentalPayment::TYPE_DEPOSIT => $rental->deposit_paid = (float) $rental->deposit_paid + $amount,
            default                     => null, // refund: counted by the caller (deposit_refunded)
        };
        $rental->save();
    }

    private function log(Rental $rental, string $action, ?string $detail = null): void
    {
        RentalLog::create(['rental_id' => $rental->id, 'user_id' => auth()->id(), 'action' => $action, 'detail' => $detail]);
    }
}
