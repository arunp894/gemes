<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            // General
            'site_name'         => ['required', 'string', 'max:120'],
            'site_tagline'      => ['nullable', 'string', 'max:255'],
            'currency_symbol'   => ['required', 'string', 'max:10'],
            'currency_code'     => ['required', 'string', 'size:3'],
            'currency_position' => ['required', 'in:before,after'],
            'contact_email'     => ['nullable', 'email', 'max:120'],
            'contact_phone'     => ['nullable', 'string', 'max:30'],
            'contact_whatsapp'  => ['nullable', 'string', 'max:30'],
            'cart_enabled'      => ['nullable', 'boolean'],
            'checkout_enabled'  => ['nullable', 'boolean'],

            // PayPal
            'paypal_enabled'    => ['nullable', 'boolean'],
            'paypal_mode'       => ['nullable', 'in:sandbox,live'],
            'paypal_client_id'  => ['nullable', 'string', 'max:255'],
            'paypal_secret'     => ['nullable', 'string', 'max:255'],
            'paypal_webhook_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
