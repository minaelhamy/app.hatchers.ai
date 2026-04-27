<?php

namespace App\Services;

use App\Models\Founder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class WebsiteProvisioningService
{
    public function availableThemes(string $engine): array
    {
        $engine = strtolower(trim($engine));
        $basePath = base_path('..');

        if ($engine === 'bazaar') {
            $themePath = $basePath . '/Bazaar/resources/views/front';
            $assetBase = rtrim((string) config('modules.bazaar.base_url'), '/')
                . '/storage/app/public/admin-assets/images/theme/';
            $prefix = 'template-';
        } elseif ($engine === 'servio') {
            $themePath = $basePath . '/Servio/resources/views/front';
            $assetBase = rtrim((string) config('modules.servio.base_url'), '/')
                . '/storage/app/public/admin-assets/images/theme/';
            $prefix = 'theme-';
        } else {
            return [];
        }

        if (!File::isDirectory($themePath)) {
            return [];
        }

        $themeDirectories = File::directories($themePath);

        $themes = [];
        foreach ($themeDirectories as $directory) {
            $name = basename($directory);
            if (!preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $name, $matches)) {
                continue;
            }

            $id = (string) ((int) $matches[1]);
            $themes[] = [
                'id' => $id,
                'label' => 'Theme ' . $id,
                'preview_url' => $this->resolveThemePreviewUrl($basePath, $engine, $id, $assetBase),
            ];
        }

        usort($themes, fn ($a, $b) => (int) $a['id'] <=> (int) $b['id']);

        if (empty($themes)) {
            return $this->fallbackThemes($engine);
        }

        return $themes;
    }

    public function applyWebsiteSetup(Founder $founder, array $input): array
    {
        $engine = $this->normalizeEngine((string) ($input['website_engine'] ?? ''));
        if ($engine === '') {
            return [
                'ok' => false,
                'error' => 'Hatchers OS could not determine the right website engine.',
            ];
        }

        if (!$this->bridgeConfigured($engine)) {
            return [
                'ok' => true,
                'engine' => $engine,
                'public_url' => $this->fallbackPublicUrl($founder, (string) ($input['website_path'] ?? '')),
                'data' => [
                    'public_url' => $this->fallbackPublicUrl($founder, (string) ($input['website_path'] ?? '')),
                ],
                'bridge_status' => 'pending',
            ];
        }

        $payload = [
            'category' => 'website',
            'operation' => 'update',
            'username' => $founder->username,
            'email' => $founder->email,
            'website_title' => trim((string) ($input['website_title'] ?? '')),
            'theme_template' => trim((string) ($input['theme_template'] ?? '')),
            'website_mode' => trim((string) ($input['website_mode'] ?? '')),
            'website_path' => trim((string) ($input['website_path'] ?? '')),
        ];

        $response = $this->postToEngine($engine, $payload);
        if (!$response['ok']) {
            return $response;
        }

        return [
            'ok' => true,
            'engine' => $engine,
            'public_url' => (string) ($response['data']['public_url'] ?? ''),
            'data' => $response['data'] ?? [],
        ];
    }

    public function publishWebsite(Founder $founder, string $engine): array
    {
        $engine = $this->normalizeEngine($engine);
        if ($engine === '') {
            return [
                'ok' => false,
                'error' => 'A valid website engine is required before publishing.',
            ];
        }

        if (!$this->bridgeConfigured($engine)) {
            return [
                'ok' => true,
                'engine' => $engine,
                'public_url' => $this->fallbackPublicUrl($founder, (string) ($founder->company?->website_path ?? '')),
                'data' => [
                    'public_url' => $this->fallbackPublicUrl($founder, (string) ($founder->company?->website_path ?? '')),
                ],
                'bridge_status' => 'pending',
            ];
        }

        $response = $this->postToEngine($engine, [
            'category' => 'website',
            'operation' => 'publish',
            'username' => $founder->username,
            'email' => $founder->email,
        ]);

        if (!$response['ok']) {
            return $response;
        }

        return [
            'ok' => true,
            'engine' => $engine,
            'public_url' => (string) ($response['data']['public_url'] ?? ''),
            'data' => $response['data'] ?? [],
        ];
    }

    public function createStarterRecord(Founder $founder, array $input): array
    {
        $engine = $this->normalizeEngine((string) ($input['website_engine'] ?? ''));
        $mode = strtolower(trim((string) ($input['starter_mode'] ?? '')));
        $title = trim((string) ($input['starter_title'] ?? ''));
        $description = trim((string) ($input['starter_description'] ?? ''));
        $price = (string) ($input['starter_price'] ?? '0');

        if ($engine === '' || $title === '' || !in_array($mode, ['product', 'service'], true)) {
            return [
                'ok' => false,
                'error' => 'Starter content needs a valid engine, type, and title.',
            ];
        }

        $category = $mode === 'product' ? 'product' : 'service';

        return $this->postToEngine($engine, [
            'category' => $category,
            'operation' => 'create',
            'username' => $founder->username,
            'email' => $founder->email,
            'title' => $title,
            'description' => $description,
            'price' => $price,
        ]);
    }

    public function connectCustomDomain(Founder $founder, array $input): array
    {
        $engine = $this->normalizeEngine((string) ($input['website_engine'] ?? ''));
        $customDomain = $this->normalizeDomain((string) ($input['custom_domain'] ?? ''));

        if ($engine === '' || $customDomain === '') {
            return [
                'ok' => false,
                'error' => 'A valid website engine and custom domain are required.',
            ];
        }

        $result = $this->postToEngine($engine, [
            'category' => 'website',
            'operation' => 'update',
            'username' => $founder->username,
            'email' => $founder->email,
            'custom_domain' => $customDomain,
        ]);

        if (!$result['ok']) {
            return $result;
        }

        return [
            'ok' => true,
            'data' => $result['data'],
            'domain' => $customDomain,
            'dns_target' => $this->dnsTargetForEngine($engine),
        ];
    }

    private function postToEngine(string $engine, array $payload): array
    {
        $sharedSecret = trim((string) env('WEBSITE_PLATFORM_SHARED_SECRET', ''));
        $baseUrl = rtrim((string) config('modules.' . $engine . '.base_url'), '/');
        if ($sharedSecret === '' || $baseUrl === '') {
            return [
                'ok' => false,
                'error' => 'The Hatchers OS website bridge is not fully configured.',
            ];
        }

        $json = json_encode($payload);
        if ($json === false) {
            return [
                'ok' => false,
                'error' => 'The website setup payload could not be encoded.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Hatchers-Signature' => hash_hmac('sha256', $json, $sharedSecret),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($baseUrl . '/api/hatchers/action', $payload);

            $data = $response->json();
            if (!$response->successful() || !is_array($data) || empty($data['success'])) {
                return [
                    'ok' => false,
                    'error' => (string) ($data['error'] ?? 'The website engine could not complete the request.'),
                ];
            }

            return [
                'ok' => true,
                'data' => $data,
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'error' => 'The website engine could not be reached right now.',
            ];
        }
    }

    private function normalizeEngine(string $engine): string
    {
        $engine = strtolower(trim($engine));

        return in_array($engine, ['bazaar', 'servio'], true) ? $engine : '';
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? '';
        $domain = trim($domain, '/');

        return filter_var('https://' . $domain, FILTER_VALIDATE_URL) ? $domain : '';
    }

    private function dnsTargetForEngine(string $engine): string
    {
        $baseUrl = rtrim((string) config('modules.' . $engine . '.base_url'), '/');
        $host = parse_url($baseUrl, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $baseUrl;
    }

    private function bridgeConfigured(string $engine): bool
    {
        $sharedSecret = trim((string) env('WEBSITE_PLATFORM_SHARED_SECRET', ''));
        $baseUrl = rtrim((string) config('modules.' . $engine . '.base_url'), '/');

        return $sharedSecret !== '' && $baseUrl !== '';
    }

    private function fallbackPublicUrl(Founder $founder, string $path = ''): string
    {
        $host = trim((string) ($founder->company?->custom_domain ?? ''));
        $host = trim(preg_replace('#^https?://#', '', $host) ?? $host, '/');
        if ($host === '') {
            $host = 'app.hatchers.ai';
        }

        $normalizedPath = trim(strtolower($path), '/');
        if ($normalizedPath === '') {
            $normalizedPath = trim(strtolower((string) ($founder->company?->website_path ?? '')), '/');
        }
        if ($normalizedPath === '') {
            $normalizedPath = str((string) ($founder->company?->company_name ?: $founder->full_name))->slug('-')->value() ?: 'your-business';
        }

        return 'https://' . $host . '/' . $normalizedPath;
    }

    private function resolveThemePreviewUrl(string $basePath, string $engine, string $id, string $assetBase): string
    {
        $directory = $basePath . '/' . ($engine === 'bazaar' ? 'Bazaar' : 'Servio') . '/storage/app/public/admin-assets/images/theme/';
        foreach (['png', 'jpeg', 'jpg', 'webp'] as $extension) {
            $filename = 'theme-' . $id . '.' . $extension;
            if (File::exists($directory . $filename)) {
                return $assetBase . $filename;
            }
        }

        return '';
    }

    private function fallbackThemes(string $engine): array
    {
        $prefix = $engine === 'servio' ? 'Service' : 'Store';

        return [
            ['id' => '1', 'label' => $prefix . ' Theme 1', 'preview_url' => ''],
            ['id' => '2', 'label' => $prefix . ' Theme 2', 'preview_url' => ''],
            ['id' => '3', 'label' => $prefix . ' Theme 3', 'preview_url' => ''],
        ];
    }
}
