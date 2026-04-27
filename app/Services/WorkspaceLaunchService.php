<?php

namespace App\Services;

use App\Models\Founder;

class WorkspaceLaunchService
{
    public function buildLaunchUrl(Founder $user, string $module): ?string
    {
        return $this->buildLaunchUrlForTarget($user, $module, $this->targetFor($user, $module));
    }

    public function buildLaunchUrlForTarget(Founder $user, string $module, string $target): ?string
    {
        $module = strtolower(trim($module));
        if (!in_array($module, ['lms', 'atlas', 'bazaar', 'servio'], true)) {
            return null;
        }

        $baseUrl = rtrim((string) config('modules.' . $module . '.base_url'), '/');
        $sharedSecret = trim((string) config('services.os.shared_secret'));
        if ($baseUrl === '' || $sharedSecret === '') {
            return null;
        }

        $payload = [
            'username' => (string) $user->username,
            'email' => (string) $user->email,
            'role' => (string) $user->role,
            'target' => $target,
            'expires' => (string) (time() + 300),
        ];

        $signature = hash_hmac('sha256', $this->signaturePayload($payload), $sharedSecret);

        return $baseUrl . '/hatchers/launch?' . http_build_query($payload + ['signature' => $signature]);
    }

    public function launchCards(Founder $user): array
    {
        $cards = [];
        foreach (['lms', 'atlas', 'bazaar', 'servio'] as $module) {
            $url = $this->buildLaunchUrl($user, $module);
            if ($url === null) {
                continue;
            }

            if ($user->isMentor() && in_array($module, ['bazaar', 'servio'], true)) {
                continue;
            }

            $cards[] = [
                'module' => strtoupper($module),
                'label' => config('modules.' . $module . '.name'),
                'description' => config('modules.' . $module . '.role'),
                'url' => $url,
            ];
        }

        return $cards;
    }

    private function targetFor(Founder $user, string $module): string
    {
        if ($module === 'lms') {
            if ($user->isAdmin()) {
                return '/hatchersadmin/profiles';
            }

            return '/launchplan/index';
        }

        if ($module === 'atlas') {
            return $user->isAdmin() ? '/admin' : '/dashboard';
        }

        return '/admin/dashboard';
    }

    private function signaturePayload(array $payload): string
    {
        return implode('|', [
            (string) ($payload['username'] ?? ''),
            (string) ($payload['email'] ?? ''),
            (string) ($payload['role'] ?? ''),
            (string) ($payload['target'] ?? ''),
            (string) ($payload['expires'] ?? ''),
        ]);
    }
}
