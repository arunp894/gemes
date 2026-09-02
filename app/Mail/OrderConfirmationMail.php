<?php

namespace App\Mail;

use App\Models\Sale;
use App\Services\SettingService;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly Sale $sale) {}

    public function build(): self
    {
        $siteName = app(SettingService::class)->get('site_name', 'Sukaina Gems');

        return $this
            ->subject("Order Confirmation – {$siteName} | #{$this->sale->sale_number}")
            ->view('emails.customer.order-confirmation', ['sale' => $this->sale]);
    }
}
