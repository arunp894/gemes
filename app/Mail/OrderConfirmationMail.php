<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly Sale $sale) {}

    public function build(): self
    {
        return $this
            ->subject("Order Confirmed — {$this->sale->sale_number}")
            ->view('emails.customer.order-confirmation', ['sale' => $this->sale]);
    }
}
