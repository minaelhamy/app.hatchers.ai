<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\Company;
use GuzzleHttp\Client;
use Throwable;

class FounderModuleSyncService
{
    public function syncFounder(Founder $founder, string $target): array
    {
        $targets = $this->syncTargets($target);
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

    public function retryModuleAcrossFounders(string $target): array
    {
        $targets = $this->syncTargets($target);
        $founders = Founder::query()
            ->where('role', 'founder')
            ->with('company')
            ->get();

        $summary = [];
        $overallFailures = [];

        foreach ($targets as $module) {
            $successCount = 0;
            $failureCount = 0;
            $lastError = null;

            foreach ($founders as $founder) {
                $result = $this->syncToModule($founder, $module);
                if (!empty($result['ok'])) {
                    $successCount++;
                    continue;
                }

                $failureCount++;
                $lastError = $result['error'] ?? 'sync failed';
            }

            $summary[$module] = [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'last_error' => $lastError,
            ];

            if ($failureCount > 0) {
                $overallFailures[] = strtoupper($module) . ': ' . $failureCount . ' failed'
                    . ($lastError ? ' (' . $lastError . ')' : '');
            }
        }

        return [
            'ok' => empty($overallFailures),
            'summary' => $summary,
            'message' => empty($overallFailures)
                ? 'Module retry completed successfully for ' . implode(', ', array_map('strtoupper', array_keys($summary))) . '.'
                : 'Retry finished with issues: ' . implode(' | ', $overallFailures),
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
                $body = trim((string) $response->getBody());
                $normalizedBody = preg_replace('/\s+/', ' ', $body);
                $bodySnippet = is_string($normalizedBody) ? mb_substr($normalizedBody, 0, 240) : '';

                return [
                    'ok' => false,
                    'error' => is_array($data) && !empty($data['error'])
                        ? (string) $data['error']
                        : 'Unexpected response from ' . strtoupper($module) . ($bodySnippet !== '' ? ': ' . $bodySnippet : '.'),
                ];
            }

            $this->persistWebsiteTargetFromSync($founder, $module, $data);

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
            'lms' => rtrim((string) config('modules.lms.base_url'), '/') . '/hatchers/founder-sync',
            'atlas' => rtrim((string) config('modules.atlas.base_url'), '/') . '/hatchers/founder-sync',
            'bazaar' => rtrim((string) config('modules.bazaar.base_url'), '/') . '/api/hatchers/founder-sync',
            'servio' => rtrim((string) config('modules.servio.base_url'), '/') . '/api/hatchers/founder-sync',
            default => null,
        };
    }

    private function syncTargets(string $target): array
    {
        return $target === 'all' ? ['lms', 'atlas', 'bazaar', 'servio'] : [$target];
    }

    private function persistWebsiteTargetFromSync(Founder $founder, string $module, array $data): void
    {
        if (!in_array($module, ['bazaar', 'servio'], true)) {
            return;
        }

        $company = $founder->company;
        if (!$company instanceof Company) {
            return;
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            return;
        }

        $baseUrl = rtrim((string) config('modules.' . $module . '.base_url'), '/');
        if ($baseUrl === '') {
            return;
        }

        if ((string) ($company->website_engine ?? '') === $module || blank($company->engine_public_url)) {
            $company->engine_public_url = $baseUrl . '/' . ltrim($slug, '/');
            $company->save();
        }
    }
}
