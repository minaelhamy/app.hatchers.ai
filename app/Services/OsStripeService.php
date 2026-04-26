<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OsStripeService
{
    public function configured(): bool
    {
        return trim((string) config('services.stripe.secret')) !== '';
    }

    public function createCheckoutSession(array $checkout): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            return [
                'success' => false,
                'message' => 'Stripe is not configured in Hatchers Ai Business OS yet.',
            ];
        }

        $currency = strtolower(trim((string) ($checkout['currency'] ?? 'usd')));
        $amount = max(0, (float) ($checkout['amount'] ?? 0));
        $productName = trim((string) ($checkout['product_name'] ?? 'Hatchers purchase'));
        $successUrl = trim((string) ($checkout['success_url'] ?? ''));
        $cancelUrl = trim((string) ($checkout['cancel_url'] ?? ''));
        $customerEmail = trim((string) ($checkout['customer_email'] ?? ''));
        $metadata = is_array($checkout['metadata'] ?? null) ? $checkout['metadata'] : [];

        if ($amount <= 0 || $successUrl === '' || $cancelUrl === '') {
            return [
                'success' => false,
                'message' => 'Stripe checkout is missing the amount or return URLs.',
            ];
        }

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl . (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'customer_email' => $customerEmail,
            'payment_method_types[0]' => 'card',
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][product_data][name]' => $productName,
            'line_items[0][price_data][unit_amount]' => (string) ((int) round($amount * 100)),
            'line_items[0][quantity]' => '1',
        ];

        foreach ($metadata as $key => $value) {
            $payload['metadata[' . Str::slug((string) $key, '_') . ']'] = (string) $value;
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->asForm()
                ->timeout(20)
                ->post('https://api.stripe.com/v1/checkout/sessions', $payload);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Stripe checkout could not be reached from Hatchers Ai Business OS.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => (string) ($response->json('error.message') ?? 'Stripe checkout could not be created.'),
            ];
        }

        return [
            'success' => true,
            'id' => (string) $response->json('id'),
            'url' => (string) $response->json('url'),
            'payment_intent' => (string) ($response->json('payment_intent') ?? ''),
            'expires_at' => !empty($response->json('expires_at')) ? now()->setTimestamp((int) $response->json('expires_at')) : null,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '' || trim($sessionId) === '') {
            return [
                'success' => false,
                'message' => 'Stripe is not configured or the checkout session id is missing.',
            ];
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->timeout(20)
                ->get('https://api.stripe.com/v1/checkout/sessions/' . $sessionId, [
                    'expand[]' => 'payment_intent',
                ]);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Stripe checkout status could not be verified from Hatchers Ai Business OS.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => (string) ($response->json('error.message') ?? 'Stripe checkout status could not be verified.'),
            ];
        }

        return [
            'success' => true,
            'status' => (string) ($response->json('status') ?? ''),
            'payment_status' => (string) ($response->json('payment_status') ?? ''),
            'payment_intent' => (string) ($response->json('payment_intent.id') ?? $response->json('payment_intent') ?? ''),
            'amount_total' => ((float) ($response->json('amount_total') ?? 0)) / 100,
            'currency' => strtoupper((string) ($response->json('currency') ?? 'USD')),
        ];
    }

    public function createConnectedAccount(array $details): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            return [
                'success' => false,
                'message' => 'Stripe is not configured in Hatchers Ai Business OS yet.',
            ];
        }

        $country = strtoupper(trim((string) ($details['country'] ?? 'US')));
        $currency = strtolower(trim((string) ($details['currency'] ?? 'usd')));
        $email = trim((string) ($details['email'] ?? ''));
        $businessName = trim((string) ($details['business_name'] ?? 'Hatchers founder'));
        $metadata = is_array($details['metadata'] ?? null) ? $details['metadata'] : [];

        $payload = [
            'type' => 'express',
            'country' => $country !== '' ? $country : 'US',
            'email' => $email,
            'capabilities[card_payments][requested]' => 'true',
            'capabilities[transfers][requested]' => 'true',
            'business_type' => 'individual',
            'business_profile[name]' => $businessName,
            'default_currency' => $currency !== '' ? $currency : 'usd',
        ];

        foreach ($metadata as $key => $value) {
            $payload['metadata[' . Str::slug((string) $key, '_') . ']'] = (string) $value;
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->asForm()
                ->timeout(20)
                ->post('https://api.stripe.com/v1/accounts', $payload);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Stripe Connect could not be reached from Hatchers Ai Business OS.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => (string) ($response->json('error.message') ?? 'Stripe Connect account could not be created.'),
            ];
        }

        return [
            'success' => true,
            'id' => (string) $response->json('id'),
            'details_submitted' => (bool) $response->json('details_submitted'),
            'charges_enabled' => (bool) $response->json('charges_enabled'),
            'payouts_enabled' => (bool) $response->json('payouts_enabled'),
        ];
    }

    public function createAccountOnboardingLink(string $accountId, string $refreshUrl, string $returnUrl): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '' || trim($accountId) === '') {
            return [
                'success' => false,
                'message' => 'Stripe Connect is not configured or the connected account is missing.',
            ];
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->asForm()
                ->timeout(20)
                ->post('https://api.stripe.com/v1/account_links', [
                    'account' => $accountId,
                    'refresh_url' => $refreshUrl,
                    'return_url' => $returnUrl,
                    'type' => 'account_onboarding',
                ]);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Stripe onboarding link could not be created from Hatchers Ai Business OS.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => (string) ($response->json('error.message') ?? 'Stripe onboarding link could not be created.'),
            ];
        }

        return [
            'success' => true,
            'url' => (string) $response->json('url'),
            'expires_at' => !empty($response->json('expires_at')) ? now()->setTimestamp((int) $response->json('expires_at')) : null,
        ];
    }

    public function retrieveConnectedAccount(string $accountId): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '' || trim($accountId) === '') {
            return [
                'success' => false,
                'message' => 'Stripe Connect is not configured or the connected account id is missing.',
            ];
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->timeout(20)
                ->get('https://api.stripe.com/v1/accounts/' . $accountId);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Stripe Connect account status could not be verified.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => (string) ($response->json('error.message') ?? 'Stripe Connect account status could not be verified.'),
            ];
        }

        return [
            'success' => true,
            'id' => (string) $response->json('id'),
            'details_submitted' => (bool) $response->json('details_submitted'),
            'charges_enabled' => (bool) $response->json('charges_enabled'),
            'payouts_enabled' => (bool) $response->json('payouts_enabled'),
            'default_currency' => strtoupper((string) ($response->json('default_currency') ?? 'USD')),
            'country' => strtoupper((string) ($response->json('country') ?? 'US')),
        ];
    }

    public function createTransfer(string $accountId, float $amount, string $currency, array $metadata = []): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '' || trim($accountId) === '' || $amount <= 0) {
            return [
                'success' => false,
                'message' => 'Stripe transfer is missing the connected account or amount.',
            ];
        }

        $payload = [
            'amount' => (string) ((int) round($amount * 100)),
            'currency' => strtolower(trim($currency)) !== '' ? strtolower(trim($currency)) : 'usd',
            'destination' => $accountId,
        ];

        foreach ($metadata as $key => $value) {
            $payload['metadata[' . Str::slug((string) $key, '_') . ']'] = (string) $value;
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->asForm()
                ->timeout(20)
                ->post('https://api.stripe.com/v1/transfers', $payload);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Stripe transfer could not be created from Hatchers Ai Business OS.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => (string) ($response->json('error.message') ?? 'Stripe transfer could not be created.'),
            ];
        }

        return [
            'success' => true,
            'id' => (string) $response->json('id'),
            'amount' => ((float) ($response->json('amount') ?? 0)) / 100,
            'currency' => strtoupper((string) ($response->json('currency') ?? 'USD')),
        ];
    }

    public function createRefund(string $paymentIntentId, float $amount, string $currency, array $metadata = []): array
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '' || trim($paymentIntentId) === '' || $amount <= 0) {
            return [
                'success' => false,
                'message' => 'Stripe refund is missing the payment intent or amount.',
            ];
        }

        $payload = [
            'payment_intent' => $paymentIntentId,
            'amount' => (string) ((int) round($amount * 100)),
        ];

        if (trim($currency) !== '') {
            $payload['metadata[currency]'] = strtoupper(trim($currency));
        }

        foreach ($metadata as $key => $value) {
            $payload['metadata[' . Str::slug((string) $key, '_') . ']'] = (string) $value;
        }

        try {
            $response = Http::withBasicAuth($secret, '')
                ->asForm()
                ->timeout(20)
                ->post('https://api.stripe.com/v1/refunds', $payload);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Stripe refund could not be created from Hatchers Ai Business OS.',
            ];
        }

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => (string) ($response->json('error.message') ?? 'Stripe refund could not be created.'),
            ];
        }

        return [
            'success' => true,
            'id' => (string) $response->json('id'),
            'amount' => ((float) ($response->json('amount') ?? 0)) / 100,
            'currency' => strtoupper((string) ($response->json('currency') ?? 'USD')),
            'status' => (string) ($response->json('status') ?? ''),
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader): array
    {
        $secret = trim((string) config('services.stripe.webhook_secret'));
        if ($secret === '' || trim($payload) === '' || trim($signatureHeader) === '') {
            return [
                'success' => false,
                'message' => 'Stripe webhook verification is not configured correctly.',
            ];
        }

        $parts = collect(explode(',', $signatureHeader))
            ->map(function (string $part): array {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

                return ['key' => trim($key), 'value' => trim($value)];
            });

        $timestamp = (string) ($parts->firstWhere('key', 't')['value'] ?? '');
        $signature = (string) ($parts->firstWhere('key', 'v1')['value'] ?? '');

        if ($timestamp === '' || $signature === '') {
            return [
                'success' => false,
                'message' => 'Stripe webhook signature is missing required values.',
            ];
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($expected, $signature)) {
            return [
                'success' => false,
                'message' => 'Stripe webhook signature verification failed.',
            ];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Stripe webhook payload could not be decoded.',
            ];
        }

        return [
            'success' => true,
            'event' => $decoded,
        ];
    }
}
