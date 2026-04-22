<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Founder;
use Illuminate\Support\Facades\Hash;

class IdentitySyncService
{
    public function verifySignature(string $rawBody, string $providedSignature): bool
    {
        $secret = trim((string) config('services.os.shared_secret'));
        if ($secret === '' || $providedSignature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $providedSignature);
    }

    public function upsert(string $role, array $payload, ?string $plainPassword = null): Founder
    {
        $user = $this->resolveUser($payload) ?: new Founder();

        $user->role = $role;
        $user->status = !empty($payload['status']) ? (string) $payload['status'] : 'active';
        $user->username = trim((string) ($payload['username'] ?? $user->username ?? ''));
        $user->email = trim((string) ($payload['email'] ?? $user->email ?? ''));
        $user->full_name = trim((string) ($payload['full_name'] ?? $payload['name'] ?? $user->full_name ?? $user->username));
        $user->phone = trim((string) ($payload['phone'] ?? $user->phone ?? ''));
        $user->country = trim((string) ($payload['country'] ?? $user->country ?? '')) ?: null;
        $user->timezone = trim((string) ($payload['timezone'] ?? $user->timezone ?? 'Africa/Cairo')) ?: 'Africa/Cairo';
        $user->last_synced_at = now();

        if ($plainPassword !== null && $plainPassword !== '') {
            $user->password = Hash::make($plainPassword);
        } elseif (!$user->exists && empty($user->password)) {
            $user->password = Hash::make(bin2hex(random_bytes(16)));
        }

        $user->save();

        if ($role === 'founder') {
            $this->syncFounderCompany($user, $payload);
        }

        return $user;
    }

    private function resolveUser(array $payload): ?Founder
    {
        foreach (['email', 'previous_email'] as $field) {
            $value = trim((string) ($payload[$field] ?? ''));
            if ($value !== '') {
                $user = Founder::query()->where('email', $value)->first();
                if ($user) {
                    return $user;
                }
            }
        }

        foreach (['username', 'previous_username'] as $field) {
            $value = trim((string) ($payload[$field] ?? ''));
            if ($value !== '') {
                $user = Founder::query()->where('username', $value)->first();
                if ($user) {
                    return $user;
                }
            }
        }

        return null;
    }

    private function syncFounderCompany(Founder $founder, array $payload): void
    {
        $companyName = trim((string) ($payload['company_name'] ?? $payload['name'] ?? $founder->full_name));
        $businessModel = trim((string) ($payload['business_model'] ?? '')) ?: 'hybrid';

        Company::updateOrCreate(
            ['founder_id' => $founder->id],
            [
                'company_name' => $companyName,
                'business_model' => $businessModel,
                'industry' => trim((string) ($payload['industry'] ?? '')) ?: null,
                'stage' => trim((string) ($payload['stage'] ?? '')) ?: 'launching',
                'company_brief' => trim((string) ($payload['company_brief'] ?? '')) ?: null,
                'website_status' => trim((string) ($payload['website_status'] ?? '')) ?: 'not_started',
            ]
        );
    }
}
