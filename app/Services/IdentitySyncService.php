<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Founder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

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

        $username = $this->normalizeUsername((string) ($payload['username'] ?? $user->username ?? ''));
        $email = $this->normalizeEmail((string) ($payload['email'] ?? $user->email ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? $payload['name'] ?? $user->full_name ?? $username));

        if ($username === '' || $email === '') {
            throw new RuntimeException('The incoming identity payload is missing a usable username or email.');
        }

        $user->role = $role;
        $user->status = !empty($payload['status']) ? (string) $payload['status'] : 'active';
        $user->auth_source = trim((string) ($payload['auth_source'] ?? '')) ?: 'integration_sync';
        $user->username = $username;
        $user->email = $email;
        $user->full_name = $fullName;
        $user->phone = trim((string) ($payload['phone'] ?? $user->phone ?? ''));
        $user->country = $this->normalizeCountry((string) ($payload['country'] ?? $user->country ?? ''));
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

    private function normalizeUsername(string $value): string
    {
        $value = trim($value);

        if ($value !== '') {
            return $value;
        }

        return '';
    }

    private function normalizeEmail(string $value): string
    {
        return trim($value);
    }

    private function normalizeCountry(string $value): ?string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return null;
        }

        return Str::limit($value, 2, '');
    }
}
