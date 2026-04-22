<?php

namespace App\Services;

use GuzzleHttp\Client;
use Throwable;

class LmsIdentityBridgeService
{
    public function authenticate(string $login, string $password): array
    {
        $baseUrl = rtrim((string) config('services.lms.base_url'), '/');
        $sharedSecret = trim((string) config('services.os.shared_secret'));
        if ($baseUrl === '' || $sharedSecret === '') {
            return ['ok' => false, 'error' => 'LMS bridge is not configured.'];
        }

        $payload = [
            'login' => $login,
            'password' => $password,
            'source' => 'app.hatchers.ai',
        ];
        $json = json_encode($payload);
        if ($json === false) {
            return ['ok' => false, 'error' => 'Could not encode LMS auth payload.'];
        }

        try {
            $client = new Client([
                'timeout' => 12,
                'http_errors' => false,
                'verify' => true,
            ]);

            $response = $client->post($baseUrl . '/hatchers/auth', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Hatchers-Signature' => hash_hmac('sha256', $json, $sharedSecret),
                ],
                'body' => $json,
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($status < 200 || $status >= 300 || !is_array($data) || empty($data['success'])) {
                return [
                    'ok' => false,
                    'error' => is_array($data) && !empty($data['error']) ? (string) $data['error'] : 'LMS rejected the credentials.',
                ];
            }

            return [
                'ok' => true,
                'profile' => is_array($data['profile'] ?? null) ? $data['profile'] : [],
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'error' => 'LMS bridge is temporarily unavailable.',
            ];
        }
    }
}
