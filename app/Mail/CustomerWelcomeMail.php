<?php

namespace App\Mail;

use App\Models\Customer;
use App\Services\SettingService;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerWelcomeMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly Customer $customer) {}

    public function build(): self
    {
        $siteName = app(SettingService::class)->get('site_name', 'Sukaina Gems');

        return $this
            ->subject("Welcome to {$siteName}")
            ->view('emails.customer.welcome', ['customer' => $this->customer]);
    }
}
