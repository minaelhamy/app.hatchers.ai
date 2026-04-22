<?php

namespace App\Services;

use App\Models\Founder;
use GuzzleHttp\Client;
use Throwable;

class FounderModuleSyncService
{
    public function syncFounder(Founder $founder, string $target): array
    {
        $targets = $target === 'all' ? ['atlas', 'bazaar', 'servio'] : [$target];
        $results = [];

        foreach ($targets as $module) {
            $results[$module] = $this->syncToModule($founder, $module);
        }

        $errors = array_filter($results, fn (array $result) => empty($result['ok']));

        return [
            'ok' => empty($errors),
            'results' => $results,
            'message' => empty($errors)
                ? 'Founder synced successfully to ' . implode(', ', array_map('strtoupper', array_keys($results))) . '.'
                : 'Some sync targets failed: ' . implode(' | ', array_map(
                    fn (string $module) => strtoupper($module) . ': ' . ($results[$module]['error'] ?? 'sync failed'),
                    array_keys($errors)
                )),
        ];
    }

    private function syncToModule(Founder $founder, string $module): array
    {
        $sharedSecret = trim((string) config('services.os.shared_secret'));
        if ($sharedSecret === '') {
            return ['ok' => false, 'error' => 'WEBSITE_PLATFORM_SHARED_SECRET is not configured.'];
        }

        $url = $this->syncEndpoint($module);
        if ($url === null) {
            return ['ok' => false, 'error' => 'Unsupported sync module.'];
        }

        $company = $founder->company;
        $payload = [
            'name' => $founder->full_name,
            'email' => $founder->email,
            'phone' => $founder->phone,
            'username' => $founder->username,
            'company_name' => $company?->company_name,
            'company_brief' => $company?->company_brief,
            'business_model' => $company?->business_model,
            'industry' => $company?->industry,
            'stage' => $company?->stage,
            'status' => $founder->status,
        ];

        $json = json_encode($payload);
        if ($json === false) {
            return ['ok' => false, 'error' => 'Could not encode sync payload.'];
        }

        try {
            $client = new Client([
                'timeout' => 15,
                'http_errors' => false,
                'verify' => true,
            ]);

            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Hatchers-Signature' => hash_hmac('sha256', $json, $sharedSecret),
                ],
                'body' => $json,
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || !is_array($data) || empty($data['success'])) {
                return [
                    'ok' => false,
                    'error' => is_array($data) && !empty($data['error']) ? (string) $data['error'] : 'Unexpected response from ' . strtoupper($module) . '.',
                ];
            }

            return ['ok' => true];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'error' => strtoupper($module) . ' is temporarily unavailable.',
            ];
        }
    }

    private function syncEndpoint(string $module): ?string
    {
        return match ($module) {
            'atlas' => rtrim((string) config('modules.atlas.base_url'), '/') . '/hatchers/founder-sync',
            'bazaar' => rtrim((string) config('modules.bazaar.base_url'), '/') . '/api/hatchers/founder-sync',
            'servio' => rtrim((string) config('modules.servio.base_url'), '/') . '/api/hatchers/founder-sync',
            default => null,
        };
    }
}
