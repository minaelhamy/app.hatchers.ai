<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Founder;
use App\Models\MentorAssignment;
use App\Models\OsAuditLog;
use App\Models\OsOperationException;
use App\Models\Subscription;

class AdminOperationsService
{
    public function build(Founder $admin): array
    {
        $mentors = Founder::query()
            ->where('role', 'mentor')
            ->with([
                'assignedFounderLinks.founder.company',
                'assignedFounderLinks.founder.weeklyState',
                'assignedFounderLinks.founder.commercialSummary',
            ])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'username'])
            ->map(function (Founder $mentor): array {
                $activeAssignments = $mentor->assignedFounderLinks
                    ->where('status', 'active')
                    ->sortByDesc('assigned_at');

                $founders = $activeAssignments->map(function (MentorAssignment $assignment): array {
                    $founder = $assignment->founder;

                    return [
                        'name' => $founder?->full_name ?: 'Founder',
                        'company_name' => $founder?->company?->company_name ?: 'Company not set yet',
                        'weekly_progress_percent' => (int) ($founder?->weeklyState?->weekly_progress_percent ?? 0),
                        'gross_revenue' => (float) ($founder?->commercialSummary?->gross_revenue ?? 0),
                    ];
                })->values();

                return [
                    'id' => $mentor->id,
                    'full_name' => $mentor->full_name,
                    'email' => $mentor->email,
                    'username' => $mentor->username,
                    'assigned_founder_count' => $founders->count(),
                    'avg_progress' => (int) round((float) ($founders->avg('weekly_progress_percent') ?? 0)),
                    'gross_revenue' => (float) $founders->sum('gross_revenue'),
                    'founders' => $founders->all(),
                ];
            });

        $founders = Founder::query()
            ->where('role', 'founder')
            ->with([
                'company',
                'subscription',
                'weeklyState',
                'commercialSummary',
                'assignedFounderLinks.mentor',
            ])
            ->orderBy('full_name')
            ->get()
            ->map(function (Founder $founder): array {
                $assignment = $founder->assignedFounderLinks
                    ->where('status', 'active')
                    ->sortByDesc('assigned_at')
                    ->first();

                return [
                    'id' => $founder->id,
                    'name' => $founder->full_name,
                    'email' => $founder->email,
                    'username' => $founder->username,
                    'phone' => (string) ($founder->phone ?? ''),
                    'country' => (string) ($founder->country ?? ''),
                    'status' => (string) ($founder->status ?? 'active'),
                    'company_name' => $founder->company?->company_name ?: 'Company not set yet',
                    'company_brief' => (string) ($founder->company?->company_brief ?? ''),
                    'business_model' => (string) ($founder->company?->business_model ?? 'hybrid'),
                    'industry' => (string) ($founder->company?->industry ?? ''),
                    'stage' => (string) ($founder->company?->stage ?? 'launching'),
                    'website_status' => (string) ($founder->company?->website_status ?? 'not_started'),
                    'plan_name' => (string) ($founder->subscription?->plan_name ?? 'Hatchers OS'),
                    'plan_code' => (string) ($founder->subscription?->plan_code ?? 'hatchers-os'),
                    'billing_status' => (string) ($founder->subscription?->billing_status ?? 'draft'),
                    'amount' => (float) ($founder->subscription?->amount ?? 0),
                    'weekly_progress_percent' => (int) ($founder->weeklyState?->weekly_progress_percent ?? 0),
                    'open_tasks' => (int) ($founder->weeklyState?->open_tasks ?? 0),
                    'gross_revenue' => (float) ($founder->commercialSummary?->gross_revenue ?? 0),
                    'assigned_mentor_id' => $assignment?->mentor?->id,
                    'assigned_mentor_name' => $assignment?->mentor?->full_name ?: '',
                    'assigned_at' => optional($assignment?->assigned_at)->toDateTimeString(),
                ];
            })
            ->all();

        return [
            'admin' => $admin,
            'mentors' => $mentors->all(),
            'founders' => $founders,
            'recent_audits' => OsAuditLog::query()
                ->with('actor')
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn (OsAuditLog $log): array => [
                    'actor_name' => $log->actor?->full_name ?: 'System',
                    'actor_role' => ucfirst((string) $log->actor_role),
                    'action' => $log->action,
                    'summary' => $log->summary,
                    'subject_type' => $log->subject_type,
                    'subject_id' => $log->subject_id,
                    'created_at' => optional($log->created_at)->toDayDateTimeString(),
                ])
                ->all(),
            'exceptions' => OsOperationException::query()
                ->with('founder')
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn (OsOperationException $exception): array => [
                    'id' => $exception->id,
                    'module' => strtoupper((string) $exception->module),
                    'operation' => $exception->operation,
                    'message' => $exception->message,
                    'status' => $exception->status,
                    'founder_name' => $exception->founder?->full_name ?: '',
                    'created_at' => optional($exception->created_at)->toDayDateTimeString(),
                    'resolved_at' => optional($exception->resolved_at)->toDayDateTimeString(),
                ])
                ->all(),
            'status_options' => ['active', 'paused', 'blocked'],
            'billing_statuses' => ['draft', 'trialing', 'active', 'paused', 'cancelled'],
            'plan_options' => [
                ['code' => 'hatchers-os', 'name' => 'Hatchers OS', 'amount' => 99],
                ['code' => 'hatchers-os-mentor', 'name' => 'Hatchers OS + Mentor', 'amount' => 600],
            ],
            'unassigned_founder_count' => collect($founders)->whereNull('assigned_mentor_id')->count(),
        ];
    }

    public function buildSystemAccess(Founder $admin): array
    {
        $mentors = Founder::query()
            ->where('role', 'mentor')
            ->withCount([
                'assignedFounderLinks as active_assignment_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('full_name')
            ->get()
            ->map(fn (Founder $mentor): array => [
                'id' => $mentor->id,
                'full_name' => $mentor->full_name,
                'email' => $mentor->email,
                'username' => $mentor->username,
                'status' => $mentor->status,
                'phone' => (string) ($mentor->phone ?? ''),
                'country' => (string) ($mentor->country ?? ''),
                'timezone' => (string) ($mentor->timezone ?? 'Africa/Cairo'),
                'permissions' => $mentor->permissionList(),
                'active_assignment_count' => (int) ($mentor->active_assignment_count ?? 0),
            ])
            ->all();

        $admins = Founder::query()
            ->where('role', 'admin')
            ->orderBy('full_name')
            ->get()
            ->map(fn (Founder $adminUser): array => [
                'id' => $adminUser->id,
                'full_name' => $adminUser->full_name,
                'email' => $adminUser->email,
                'username' => $adminUser->username,
                'status' => $adminUser->status,
                'phone' => (string) ($adminUser->phone ?? ''),
                'country' => (string) ($adminUser->country ?? ''),
                'timezone' => (string) ($adminUser->timezone ?? 'Africa/Cairo'),
                'permissions' => $adminUser->permissionList(),
            ])
            ->all();

        $founderRebalancePool = Founder::query()
            ->where('role', 'founder')
            ->with([
                'company',
                'weeklyState',
                'assignedFounderLinks' => fn ($query) => $query->where('status', 'active')->with('mentor')->latest('assigned_at'),
            ])
            ->orderBy('full_name')
            ->get()
            ->map(function (Founder $founder): array {
                $assignment = $founder->assignedFounderLinks->first();

                return [
                    'id' => $founder->id,
                    'full_name' => $founder->full_name,
                    'company_name' => $founder->company?->company_name ?: 'Company not set yet',
                    'status' => (string) ($founder->status ?? 'active'),
                    'weekly_progress_percent' => (int) ($founder->weeklyState?->weekly_progress_percent ?? 0),
                    'mentor_name' => $assignment?->mentor?->full_name ?: 'Unassigned',
                    'mentor_id' => $assignment?->mentor?->id,
                ];
            })
            ->all();

        return [
            'admin' => $admin,
            'mentors' => $mentors,
            'admins' => $admins,
            'founder_rebalance_pool' => $founderRebalancePool,
            'mentor_permission_options' => [
                'founder_portfolio',
                'mentor_notes',
                'mentor_execution_updates',
                'atlas_context',
            ],
            'admin_permission_options' => [
                'subscriber_reporting',
                'founder_operations',
                'mentor_management',
                'module_monitoring',
                'exception_resolution',
                'system_access',
            ],
            'rebalance_recommendations' => $this->buildMentorRebalanceRecommendations($mentors),
            'summary' => [
                'mentor_count' => count($mentors),
                'admin_count' => count($admins),
                'founder_pool_count' => count($founderRebalancePool),
            ],
        ];
    }

    public function buildIdentityWorkspace(Founder $admin): array
    {
        $users = Founder::query()
            ->with('company')
            ->orderBy('role')
            ->orderBy('full_name')
            ->get()
            ->map(function (Founder $user): array {
                $hoursSinceSync = $user->last_synced_at?->diffInHours(now());

                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'identity_key' => $this->buildIdentityKey($user),
                    'role' => (string) $user->role,
                    'status' => (string) ($user->status ?? 'active'),
                    'auth_source' => (string) ($user->auth_source ?? ''),
                    'auth_source_label' => $user->identitySourceLabel(),
                    'last_synced_at' => optional($user->last_synced_at)->toDayDateTimeString(),
                    'hours_since_sync' => $hoursSinceSync,
                    'sync_state' => $this->resolveIdentitySyncState($user->auth_source, $hoursSinceSync),
                    'company_name' => $user->company?->company_name ?: '',
                ];
            });

        $grouped = $users->groupBy('role');

        return [
            'admin' => $admin,
            'metrics' => [
                'total_users' => $users->count(),
                'founders' => $grouped->get('founder', collect())->count(),
                'mentors' => $grouped->get('mentor', collect())->count(),
                'admins' => $grouped->get('admin', collect())->count(),
                'os_native' => $users->where('auth_source', 'os')->count(),
                'lms_bridge' => $users->where('auth_source', 'lms_bridge')->count(),
                'integration_sync' => $users->where('auth_source', 'integration_sync')->count(),
                'stale_identities' => $users->filter(fn (array $user) => $user['sync_state']['label'] === 'Stale')->count(),
                'unknown_identity_source' => $users->filter(fn (array $user) => $user['auth_source'] === '')->count(),
            ],
            'groups' => [
                'founders' => $grouped->get('founder', collect())->values()->all(),
                'mentors' => $grouped->get('mentor', collect())->values()->all(),
                'admins' => $grouped->get('admin', collect())->values()->all(),
            ],
            'login_authority_rules' => [
                'OS checks local Hatchers Ai OS credentials first.',
                'If no local match is found, or the password does not match, the OS can fall back to the LMS bridge.',
                'A successful LMS bridge login refreshes the local OS identity and then signs the user into the OS.',
                'Founder signup creates OS-native identities directly in Hatchers Ai OS.',
            ],
        ];
    }

    public function backfillIdentityMetadata(): array
    {
        $updated = 0;
        $unchanged = 0;

        Founder::query()->orderBy('id')->get()->each(function (Founder $user) use (&$updated, &$unchanged): void {
            $nextAuthSource = $this->guessAuthSource($user);

            $changes = [];
            if (trim((string) $user->auth_source) === '' && $nextAuthSource !== '') {
                $changes['auth_source'] = $nextAuthSource;
            }

            if (!empty($changes)) {
                $user->forceFill($changes)->save();
                $updated++;
                return;
            }

            $unchanged++;
        });

        return [
            'updated' => $updated,
            'unchanged' => $unchanged,
            'message' => $updated > 0
                ? 'Identity metadata was backfilled for ' . $updated . ' OS users.'
                : 'All current OS users already had identity metadata in place.',
        ];
    }

    public function assignMentor(int $founderId, ?int $mentorId): void
    {
        MentorAssignment::query()
            ->where('founder_id', $founderId)
            ->where('status', 'active')
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

        if ($mentorId === null || $mentorId <= 0) {
            return;
        }

        MentorAssignment::create([
            'founder_id' => $founderId,
            'mentor_user_id' => $mentorId,
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }

    public function updateFounderProfile(int $founderId, array $payload): void
    {
        $founder = Founder::query()->findOrFail($founderId);

        $founder->update([
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'country' => $payload['country'] ?? null,
            'status' => $payload['status'],
        ]);

        Company::updateOrCreate(
            ['founder_id' => $founder->id],
            [
                'company_name' => $payload['company_name'],
                'company_brief' => $payload['company_brief'] ?? null,
                'business_model' => $payload['business_model'],
                'industry' => $payload['industry'] ?? null,
                'stage' => $payload['stage'],
                'website_status' => $payload['website_status'],
            ]
        );
    }

    public function updateSubscription(int $founderId, array $payload): void
    {
        Subscription::updateOrCreate(
            ['founder_id' => $founderId],
            [
                'plan_code' => $payload['plan_code'],
                'plan_name' => $payload['plan_name'],
                'billing_status' => $payload['billing_status'],
                'amount' => (float) $payload['amount'],
                'currency' => 'USD',
                'started_at' => now(),
            ]
        );
    }

    public function updateMentorProfile(int $mentorId, array $payload): void
    {
        $mentor = Founder::query()->where('role', 'mentor')->findOrFail($mentorId);
        $mentor->update([
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'country' => $payload['country'] ?? null,
            'timezone' => $payload['timezone'],
            'status' => $payload['status'],
            'permissions_json' => array_values($payload['permissions'] ?? []),
        ]);
    }

    public function updateAdminProfile(int $adminId, array $payload): void
    {
        $admin = Founder::query()->where('role', 'admin')->findOrFail($adminId);
        $admin->update([
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'country' => $payload['country'] ?? null,
            'timezone' => $payload['timezone'],
            'status' => $payload['status'],
            'permissions_json' => array_values($payload['permissions'] ?? []),
        ]);
    }

    private function buildMentorRebalanceRecommendations(array $mentors): array
    {
        if (empty($mentors)) {
            return [];
        }

        $counts = array_map(fn (array $mentor): int => (int) ($mentor['active_assignment_count'] ?? 0), $mentors);
        $average = count($counts) > 0 ? (int) round(array_sum($counts) / count($counts)) : 0;
        $overloaded = array_values(array_filter($mentors, fn (array $mentor): bool => (int) $mentor['active_assignment_count'] > max($average + 2, 4)));
        $available = array_values(array_filter($mentors, fn (array $mentor): bool => (int) $mentor['active_assignment_count'] < max($average - 1, 2)));

        $recommendations = [];
        foreach ($overloaded as $mentor) {
            $target = $available[0] ?? null;
            $recommendations[] = [
                'mentor_name' => $mentor['full_name'],
                'current_load' => (int) $mentor['active_assignment_count'],
                'recommended_target' => $target['full_name'] ?? 'No lower-load mentor available yet',
                'target_load' => $target['active_assignment_count'] ?? null,
                'message' => $target
                    ? 'Consider moving one founder from ' . $mentor['full_name'] . ' to ' . $target['full_name'] . ' to smooth mentor capacity.'
                    : 'This mentor is carrying above-average load, but no lower-load mentor is currently available.',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'mentor_name' => 'Team balance',
                'current_load' => $average,
                'recommended_target' => null,
                'target_load' => null,
                'message' => 'Mentor assignment load is currently balanced enough that no rebalance is recommended by the OS.',
            ];
        }

        return $recommendations;
    }

    private function resolveIdentitySyncState(?string $authSource, ?int $hoursSinceSync): array
    {
        if (trim((string) $authSource) === '') {
            return [
                'label' => 'Unknown',
                'tone' => 'warning',
            ];
        }

        if (in_array((string) $authSource, ['os'], true)) {
            return [
                'label' => 'OS native',
                'tone' => 'success',
            ];
        }

        if ($hoursSinceSync === null) {
            return [
                'label' => 'Unsynced',
                'tone' => 'warning',
            ];
        }

        if ($hoursSinceSync <= 72) {
            return [
                'label' => 'Healthy',
                'tone' => 'success',
            ];
        }

        return [
            'label' => 'Stale',
            'tone' => 'warning',
        ];
    }

    private function guessAuthSource(Founder $user): string
    {
        if (trim((string) $user->auth_source) !== '') {
            return (string) $user->auth_source;
        }

        if ($user->role === 'founder' && $user->subscription()->exists()) {
            return 'os';
        }

        if ($user->last_synced_at !== null) {
            return 'integration_sync';
        }

        return 'os';
    }

    private function buildIdentityKey(Founder $user): string
    {
        $role = trim((string) ($user->role ?? 'founder'));
        $username = trim((string) ($user->username ?? ''));
        $email = trim((string) ($user->email ?? ''));

        if ($username !== '') {
            return $role . ':' . strtolower($username);
        }

        return $role . ':' . strtolower($email);
    }
}
