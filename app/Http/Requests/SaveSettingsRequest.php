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
            'contact_address'   => ['nullable', 'string', 'max:255'],
            'cart_enabled'      => ['nullable', 'boolean'],
            'checkout_enabled'  => ['nullable', 'boolean'],

            // Branding
            'site_logo'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:2048'],
            'remove_logo'       => ['nullable', 'boolean'],
            'site_favicon'      => ['nullable', 'mimes:ico,png,jpeg,jpg,svg', 'max:512'],
            'remove_favicon'    => ['nullable', 'boolean'],

            // PayPal
            'paypal_enabled'    => ['nullable', 'boolean'],
            'paypal_mode'       => ['nullable', 'in:sandbox,live'],
            'paypal_client_id'  => ['nullable', 'string', 'max:255'],
            'paypal_secret'     => ['nullable', 'string', 'max:255'],
            'paypal_webhook_id' => ['nullable', 'string', 'max:255'],

            // Purchases
            'purchase_edit_days' => ['nullable', 'integer', 'min:0', 'max:365'],

            // Sales
            'sale_edit_days'     => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'site_logo.image'    => 'The logo must be an image file.',
            'site_logo.mimes'    => 'Accepted logo types: JPEG, PNG, WebP, GIF, SVG.',
            'site_logo.max'      => 'Logo must not exceed 2 MB.',
            'site_favicon.mimes' => 'Accepted favicon types: ICO, PNG, JPEG, SVG.',
            'site_favicon.max'   => 'Favicon must not exceed 512 KB.',
        ];
    }
}
