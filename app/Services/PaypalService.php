<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Shared PayPal REST client helpers — API base resolution, OAuth token
 * fetch, and inbound webhook signature verification.
 *
 * Extracted out of CheckoutController so PaypalWebhookController can reuse
 * the exact same credential/token handling rather than a second copy.
 */
class PaypalService
{
    public function __construct(private readonly SettingService $settings) {}

    public function apiBase(): string
    {
        return $this->settings->get('paypal_mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function getAccessToken(): string
    {
        $clientId = $this->settings->get('paypal_client_id', '');
        $secret = $this->settings->get('paypal_secret', '');

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($this->apiBase().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            throw new \RuntimeException('Could not obtain PayPal access token.');
        }

        return $response->json('access_token');
    }

    /**
     * Verify an inbound webhook call against PayPal's own servers before
     * trusting anything in its body — without this, anyone who discovers
     * the webhook URL could POST a fake "payment completed" event and get
     * a free Sale created.
     *
     * $headers must carry the five PAYPAL-* transmission headers PayPal
     * sends with every webhook request (lowercase keys, see
     * PaypalWebhookController::handle()). $rawEventBody is the exact raw
     * request body PayPal sent, decoded to an array — passed through
     * verbatim as `webhook_event`, since the signature covers the exact
     * bytes PayPal transmitted.
     *
     * https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature_post
     */
    public function verifyWebhookSignature(array $headers, array $rawEventBody): bool
    {
        $webhookId = $this->settings->get('paypal_webhook_id', '');

        if (empty($webhookId)) {
            logger()->error('PayPal webhook: no paypal_webhook_id configured in Settings — cannot verify.');

            return false;
        }

        foreach (['transmission_id', 'transmission_time', 'cert_url', 'auth_algo', 'transmission_sig'] as $key) {
            if (empty($headers[$key])) {
                logger()->warning('PayPal webhook: missing required transmission header.', ['missing' => $key]);

                return false;
            }
        }

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiBase().'/v1/notifications/verify-webhook-signature', [
                    'transmission_id' => $headers['transmission_id'],
                    'transmission_time' => $headers['transmission_time'],
                    'cert_url' => $headers['cert_url'],
                    'auth_algo' => $headers['auth_algo'],
                    'transmission_sig' => $headers['transmission_sig'],
                    'webhook_id' => $webhookId,
                    'webhook_event' => $rawEventBody,
                ]);

            return $response->successful() && $response->json('verification_status') === 'SUCCESS';

        } catch (\Throwable $e) {
            logger()->error('PayPal webhook: signature verification call failed.', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
