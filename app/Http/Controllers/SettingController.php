<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use App\Http\Requests\SaveSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * Admin Settings Controller
 *
 * Single page with tabbed sections: General (storefront) and PayPal.
 * All settings are saved in one POST. Permissions gated to 'admin' role
 * (settings are site-wide, not granular per module).
 */
class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * Show the settings form.
     */
    public function index(): View
    {
        $all = $this->settings->all();
        return view('settings.index', compact('all'));
    }

    /**
     * Save all settings groups in one shot.
     */
    public function save(SaveSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        // Save general group
        $this->settings->save([
            'site_name'         => $data['site_name']         ?? 'Sukaina Gems',
            'site_tagline'      => $data['site_tagline']       ?? '',
            'currency_symbol'   => $data['currency_symbol']    ?? '₹',
            'currency_code'     => $data['currency_code']      ?? 'USD',
            'currency_position' => $data['currency_position']  ?? 'before',
            'contact_email'     => $data['contact_email']      ?? '',
            'contact_phone'     => $data['contact_phone']      ?? '',
            'contact_whatsapp'  => $data['contact_whatsapp']   ?? '',
            'cart_enabled'      => isset($data['cart_enabled'])     ? '1' : '0',
            'checkout_enabled'  => isset($data['checkout_enabled']) ? '1' : '0',
        ], 'general');

        // Save paypal group
        $this->settings->save([
            'paypal_enabled'    => isset($data['paypal_enabled']) ? '1' : '0',
            'paypal_mode'       => $data['paypal_mode']      ?? 'sandbox',
            'paypal_client_id'  => $data['paypal_client_id'] ?? '',
            'paypal_secret'     => $data['paypal_secret']    ?? '',
            'paypal_webhook_id' => $data['paypal_webhook_id'] ?? '',
        ], 'paypal');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully.',
            ]);
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully.');
    }

    /**
     * Quick PayPal credential test — returns access token status.
     * Credentials passed in request body (not yet saved).
     */
    public function testPaypal(Request $request): JsonResponse
    {
        $request->validate([
            'paypal_client_id' => ['required', 'string'],
            'paypal_secret'    => ['required', 'string'],
            'paypal_mode'      => ['nullable', 'in:sandbox,live'],
        ]);

        $base = $request->input('paypal_mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        try {
            $response = Http::withBasicAuth($request->paypal_client_id, $request->paypal_secret)
                ->asForm()
                ->post($base . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            if ($response->successful() && $response->json('access_token')) {
                return response()->json(['success' => true,  'message' => 'Connection successful! Credentials are valid.']);
            }

            return response()->json(['success' => false, 'error' => 'PayPal rejected the credentials. Check Client ID and Secret.']);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'Could not reach PayPal: ' . $e->getMessage()]);
        }
    }
}
