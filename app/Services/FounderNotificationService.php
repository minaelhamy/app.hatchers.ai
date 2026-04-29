<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\FounderNotification;

class FounderNotificationService
{
    public function create(Founder $founder, array $payload): FounderNotification
    {
        return FounderNotification::create([
            'founder_id' => $founder->id,
            'kind' => (string) ($payload['kind'] ?? 'general'),
            'title' => (string) ($payload['title'] ?? 'Update'),
            'meta' => filled($payload['meta'] ?? null) ? (string) $payload['meta'] : null,
            'app_key' => filled($payload['app_key'] ?? null) ? (string) $payload['app_key'] : null,
            'link_url' => filled($payload['link_url'] ?? null) ? (string) $payload['link_url'] : null,
            'is_read' => (bool) ($payload['is_read'] ?? false),
            'data_json' => is_array($payload['data_json'] ?? null) ? $payload['data_json'] : null,
        ]);
    }

    public function websiteBuildStarted(Founder $founder, string $engineLabel): FounderNotification
    {
        return $this->create($founder, [
            'kind' => 'website_building',
            'title' => 'Building your website now.',
            'meta' => 'Hatchers is preparing your first ' . strtoupper($engineLabel) . ' website draft.',
            'app_key' => 'build-website',
            'data_json' => [
                'engine' => strtolower($engineLabel),
                'status' => 'building',
            ],
        ]);
    }

    public function websiteReady(Founder $founder, string $engineAppKey, string $websiteUrl): FounderNotification
    {
        return $this->create($founder, [
            'kind' => 'website_ready',
            'title' => 'Your website is ready.',
            'meta' => trim('Live at ' . preg_replace('#^https?://#', '', $websiteUrl) . ' · Edit it in ' . $this->engineLabelFromAppKey($engineAppKey) . '.'),
            'app_key' => $engineAppKey,
            'link_url' => $websiteUrl,
            'data_json' => [
                'website_url' => $websiteUrl,
                'engine_app_key' => $engineAppKey,
                'status' => 'ready',
            ],
        ]);
    }

    public function websiteBuildFailed(Founder $founder, string $message = ''): FounderNotification
    {
        $meta = trim($message);
        if ($meta === '') {
            $meta = 'We could not finish the first website build yet. Open Build My Website to try again after the latest fixes.';
        }

        return $this->create($founder, [
            'kind' => 'website_build_failed',
            'title' => 'We could not finish your website yet.',
            'meta' => $meta,
            'app_key' => 'build-website',
            'data_json' => [
                'status' => 'failed',
            ],
        ]);
    }

    private function engineLabelFromAppKey(string $engineAppKey): string
    {
        return match ($engineAppKey) {
            'bazaar-engine' => 'Bazaar',
            'servio-engine' => 'Servio',
            default => 'the website workspace',
        };
    }
}
