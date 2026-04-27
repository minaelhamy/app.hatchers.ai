<?php

namespace App\Services;

use App\Models\Founder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AtlasWorkspaceService
{
    public function summary(Founder $founder, array $fallbackAtlas = []): array
    {
        $baseline = [
            'ok' => false,
            'conversations' => [],
            'recent_campaigns' => is_array($fallbackAtlas['recent_campaigns'] ?? null) ? $fallbackAtlas['recent_campaigns'] : [],
            'archived_campaigns' => is_array($fallbackAtlas['archived_campaigns'] ?? null) ? $fallbackAtlas['archived_campaigns'] : [],
            'media_outputs' => [],
            'documents' => [],
        ];

        $secret = trim((string) config('services.atlas.shared_secret'));
        $endpoint = rtrim((string) config('services.atlas.base_url'), '/') . '/hatchers/workspace/summary';

        if ($secret === '' || $endpoint === '/hatchers/workspace/summary') {
            return $baseline;
        }

        $payload = [
            'app' => 'os',
            'role' => $founder->role ?: 'founder',
            'current_page' => 'ai_studio',
            'name' => $founder->full_name,
            'username' => $founder->username,
            'email' => $founder->email,
        ];

        $json = json_encode($payload);
        if ($json === false) {
            return $baseline;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'X-Hatchers-Signature' => hash_hmac('sha256', $json, $secret),
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);
        } catch (\Throwable $exception) {
            Log::warning('Atlas workspace summary failed', [
                'founder_id' => $founder->id,
                'message' => $exception->getMessage(),
            ]);

            return $baseline;
        }

        if (!$response->successful()) {
            return $baseline;
        }

        $data = $response->json();
        if (!is_array($data)) {
            return $baseline;
        }

        return [
            'ok' => true,
            'conversations' => is_array($data['conversations'] ?? null) ? $data['conversations'] : [],
            'recent_campaigns' => is_array($data['recent_campaigns'] ?? null) ? $data['recent_campaigns'] : $baseline['recent_campaigns'],
            'archived_campaigns' => is_array($data['archived_campaigns'] ?? null) ? $data['archived_campaigns'] : $baseline['archived_campaigns'],
            'media_outputs' => is_array($data['media_outputs'] ?? null) ? $data['media_outputs'] : [],
            'documents' => is_array($data['documents'] ?? null) ? $data['documents'] : [],
        ];
    }
}
