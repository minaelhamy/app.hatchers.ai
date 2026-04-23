<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\MentorAssignment;
use App\Models\ModuleSnapshot;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardService
{
    public function build(Founder $admin): array
    {
        $founderCount = Founder::query()->where('role', 'founder')->count();
        $mentorCount = Founder::query()->where('role', 'mentor')->count();
        $adminCount = Founder::query()->where('role', 'admin')->count();
        $subscriberCount = Subscription::query()->count();
        $activeSubscriberCount = Subscription::query()
            ->whereIn('billing_status', ['active', 'draft', 'trialing'])
            ->count();
        $mentorAssignmentCount = MentorAssignment::query()->where('status', 'active')->count();
        $liveWebsiteCount = \App\Models\Company::query()->where('website_status', 'live')->count();
        $grossRevenue = (float) \App\Models\CommercialSummary::query()->sum('gross_revenue');

        $moduleHealth = collect(['lms', 'atlas', 'bazaar', 'servio'])->map(function (string $module) use ($founderCount): array {
            $query = ModuleSnapshot::query()->where('module', $module);
            $latestSnapshot = (clone $query)->latest('snapshot_updated_at')->first();
            $syncedFounders = (int) (clone $query)->distinct('founder_id')->count('founder_id');
            $coveragePercent = $founderCount > 0
                ? (int) round(($syncedFounders / $founderCount) * 100)
                : 0;
            $lastSyncedAt = $latestSnapshot?->snapshot_updated_at;
            $hoursSinceSync = $lastSyncedAt?->diffInHours(now());
            $status = $this->resolveModuleStatus($syncedFounders, $hoursSinceSync);

            return [
                'module' => strtoupper($module),
                'status' => $status['label'],
                'status_tone' => $status['tone'],
                'status_reason' => $status['reason'],
                'synced_founders' => $syncedFounders,
                'missing_founders' => max($founderCount - $syncedFounders, 0),
                'coverage_percent' => $coveragePercent,
                'avg_readiness' => (int) round((float) ((clone $query)->avg('readiness_score') ?? 0)),
                'last_synced_at' => optional($lastSyncedAt)->toDateTimeString(),
                'hours_since_sync' => $hoursSinceSync,
            ];
        })->all();

        $healthyModules = collect($moduleHealth)->where('status', 'Healthy')->count();
        $staleModules = collect($moduleHealth)->where('status', 'Stale')->count();
        $offlineModules = collect($moduleHealth)->where('status', 'Offline')->count();

        $recentSubscribers = Founder::query()
            ->where('role', 'founder')
            ->with(['company', 'subscription', 'weeklyState', 'commercialSummary'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Founder $founder): array {
                return [
                    'name' => $founder->full_name,
                    'email' => $founder->email,
                    'company_name' => $founder->company?->company_name ?: 'Company not set yet',
                    'business_model' => ucfirst((string) ($founder->company?->business_model ?? 'hybrid')),
                    'plan_name' => $founder->subscription?->plan_name ?: 'No plan yet',
                    'billing_status' => ucfirst((string) ($founder->subscription?->billing_status ?? 'unknown')),
                    'weekly_progress_percent' => (int) ($founder->weeklyState?->weekly_progress_percent ?? 0),
                    'gross_revenue' => (float) ($founder->commercialSummary?->gross_revenue ?? 0),
                    'created_at' => optional($founder->created_at)->toDateTimeString(),
                ];
            })
            ->all();

        $roleLaunches = [
            [
                'label' => 'Open LMS Admin',
                'url' => rtrim((string) config('modules.lms.base_url'), '/') . '/admin',
            ],
            [
                'label' => 'Open Atlas Admin',
                'url' => rtrim((string) config('modules.atlas.base_url'), '/') . '/admin',
            ],
            [
                'label' => 'Open Bazaar Admin',
                'url' => rtrim((string) config('modules.bazaar.base_url'), '/') . '/admin/dashboard',
            ],
            [
                'label' => 'Open Servio Admin',
                'url' => rtrim((string) config('modules.servio.base_url'), '/') . '/admin/dashboard',
            ],
        ];

        return [
            'admin' => $admin,
            'metrics' => [
                'founders' => $founderCount,
                'mentors' => $mentorCount,
                'admins' => $adminCount,
                'subscribers' => $subscriberCount,
                'active_subscribers' => $activeSubscriberCount,
                'active_mentor_assignments' => $mentorAssignmentCount,
                'live_websites' => $liveWebsiteCount,
                'gross_revenue' => $grossRevenue,
                'healthy_modules' => $healthyModules,
                'stale_modules' => $staleModules,
                'offline_modules' => $offlineModules,
            ],
            'module_health' => $moduleHealth,
            'recent_subscribers' => $recentSubscribers,
            'role_launches' => $roleLaunches,
        ];
    }

    public function buildSubscriberReport(Founder $admin, array $filters = []): array
    {
        $baseQuery = Founder::query()
            ->where('role', 'founder')
            ->with([
                'company',
                'subscription',
                'weeklyState',
                'commercialSummary',
                'assignedFounderLinks.mentor',
            ]);

        $filteredQuery = (clone $baseQuery);
        $this->applySubscriberFilters($filteredQuery, $filters);
        $founders = $filteredQuery->orderBy('full_name')->get();

        $subscribers = $founders->map(function (Founder $founder): array {
            $assignment = $founder->assignedFounderLinks
                ->where('status', 'active')
                ->sortByDesc('assigned_at')
                ->first();

            return [
                'id' => $founder->id,
                'name' => $founder->full_name,
                'email' => $founder->email,
                'status' => (string) ($founder->status ?? 'active'),
                'company_name' => $founder->company?->company_name ?: 'Company not set yet',
                'business_model' => (string) ($founder->company?->business_model ?? 'hybrid'),
                'plan_code' => (string) ($founder->subscription?->plan_code ?? ''),
                'plan_name' => (string) ($founder->subscription?->plan_name ?? 'Hatchers OS'),
                'billing_status' => (string) ($founder->subscription?->billing_status ?? 'draft'),
                'weekly_progress_percent' => (int) ($founder->weeklyState?->weekly_progress_percent ?? 0),
                'open_tasks' => (int) ($founder->weeklyState?->open_tasks ?? 0),
                'gross_revenue' => (float) ($founder->commercialSummary?->gross_revenue ?? 0),
                'orders' => (int) ($founder->commercialSummary?->order_count ?? 0),
                'bookings' => (int) ($founder->commercialSummary?->booking_count ?? 0),
                'website_status' => (string) ($founder->company?->website_status ?? 'not_started'),
                'mentor_name' => $assignment?->mentor?->full_name ?: 'Unassigned',
                'created_at' => optional($founder->created_at)->toDateTimeString(),
            ];
        });

        $allFounders = $baseQuery->get();
        $newLast7Days = $allFounders->filter(fn (Founder $founder) => optional($founder->created_at)?->gte(now()->subDays(7)))->count();
        $newLast30Days = $allFounders->filter(fn (Founder $founder) => optional($founder->created_at)?->gte(now()->subDays(30)))->count();

        return [
            'admin' => $admin,
            'filters' => [
                'search' => trim((string) ($filters['search'] ?? '')),
                'plan_code' => (string) ($filters['plan_code'] ?? ''),
                'billing_status' => (string) ($filters['billing_status'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'business_model' => (string) ($filters['business_model'] ?? ''),
            ],
            'filter_options' => [
                'plan_codes' => Subscription::query()
                    ->whereNotNull('plan_code')
                    ->where('plan_code', '!=', '')
                    ->distinct()
                    ->orderBy('plan_code')
                    ->pluck('plan_code')
                    ->all(),
                'billing_statuses' => Subscription::query()
                    ->whereNotNull('billing_status')
                    ->where('billing_status', '!=', '')
                    ->distinct()
                    ->orderBy('billing_status')
                    ->pluck('billing_status')
                    ->all(),
                'statuses' => ['active', 'paused', 'blocked'],
                'business_models' => ['product', 'service', 'hybrid'],
            ],
            'metrics' => [
                'filtered_subscribers' => $subscribers->count(),
                'active_subscribers' => $subscribers->where('billing_status', 'active')->count(),
                'trialing_subscribers' => $subscribers->where('billing_status', 'trialing')->count(),
                'blocked_founders' => $subscribers->where('status', 'blocked')->count(),
                'live_websites' => $subscribers->where('website_status', 'live')->count(),
                'mentor_coverage' => $subscribers->filter(fn (array $row) => $row['mentor_name'] !== 'Unassigned')->count(),
                'avg_weekly_progress' => (int) round((float) ($subscribers->avg('weekly_progress_percent') ?? 0)),
                'gross_revenue' => (float) $subscribers->sum('gross_revenue'),
                'new_last_7_days' => $newLast7Days,
                'new_last_30_days' => $newLast30Days,
            ],
            'health' => [
                'on_track' => $subscribers->filter(fn (array $row) => $row['weekly_progress_percent'] >= 60 && $row['status'] === 'active')->count(),
                'watchlist' => $subscribers->filter(fn (array $row) => $row['weekly_progress_percent'] >= 25 && $row['weekly_progress_percent'] < 60)->count(),
                'at_risk' => $subscribers->filter(fn (array $row) => $row['weekly_progress_percent'] < 25 || $row['status'] !== 'active')->count(),
            ],
            'subscribers' => $subscribers->values()->all(),
        ];
    }

    public function buildModuleMonitoring(Founder $admin): array
    {
        $overview = $this->build($admin);

        return [
            'admin' => $admin,
            'module_health' => $overview['module_health'],
            'metrics' => $overview['metrics'],
            'recent_subscribers' => $overview['recent_subscribers'],
            'exceptions' => \App\Models\OsOperationException::query()
                ->with('founder')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (\App\Models\OsOperationException $exception): array => [
                    'id' => $exception->id,
                    'module' => strtoupper((string) $exception->module),
                    'operation' => $exception->operation,
                    'status' => $exception->status,
                    'message' => $exception->message,
                    'founder_name' => $exception->founder?->full_name ?: '',
                    'created_at' => optional($exception->created_at)->toDayDateTimeString(),
                ])
                ->all(),
            'recent_audits' => \App\Models\OsAuditLog::query()
                ->with('actor')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (\App\Models\OsAuditLog $log): array => [
                    'actor_name' => $log->actor?->full_name ?: 'System',
                    'summary' => $log->summary,
                    'created_at' => optional($log->created_at)->toDayDateTimeString(),
                ])
                ->all(),
        ];
    }

    public function buildSupportWorkspace(Founder $admin): array
    {
        $subscriberReport = $this->buildSubscriberReport($admin);
        $moduleMonitoring = $this->buildModuleMonitoring($admin);

        $urgentSubscribers = collect($subscriberReport['subscribers'])
            ->filter(fn (array $subscriber): bool => $subscriber['status'] !== 'active'
                || $subscriber['weekly_progress_percent'] < 25
                || $subscriber['billing_status'] === 'past_due')
            ->sortBy([
                fn (array $subscriber) => $subscriber['status'] === 'blocked' ? 0 : 1,
                fn (array $subscriber) => $subscriber['weekly_progress_percent'],
            ])
            ->take(12)
            ->values()
            ->all();

        $staleModules = collect($moduleMonitoring['module_health'])
            ->filter(fn (array $module): bool => in_array($module['status'], ['Stale', 'Offline'], true))
            ->values()
            ->all();

        $openExceptions = collect($moduleMonitoring['exceptions'])
            ->filter(fn (array $exception): bool => $exception['status'] !== 'resolved')
            ->values()
            ->all();

        return [
            'admin' => $admin,
            'metrics' => [
                'urgent_founders' => count($urgentSubscribers),
                'open_exceptions' => count($openExceptions),
                'stale_modules' => count($staleModules),
                'watchlist_founders' => $subscriberReport['health']['watchlist'],
            ],
            'urgent_subscribers' => $urgentSubscribers,
            'stale_modules' => $staleModules,
            'exceptions' => $openExceptions,
            'recent_audits' => $moduleMonitoring['recent_audits'],
        ];
    }

    private function resolveModuleStatus(int $syncedFounders, ?int $hoursSinceSync): array
    {
        if ($syncedFounders === 0 || $hoursSinceSync === null) {
            return [
                'label' => 'Offline',
                'tone' => 'danger',
                'reason' => 'No module snapshots have reached Hatchers Ai OS yet.',
            ];
        }

        if ($hoursSinceSync <= 24) {
            return [
                'label' => 'Healthy',
                'tone' => 'success',
                'reason' => 'Recent module snapshots are flowing into the OS.',
            ];
        }

        if ($hoursSinceSync <= 72) {
            return [
                'label' => 'Stale',
                'tone' => 'warning',
                'reason' => 'Module snapshots exist, but they are aging and need refresh attention.',
            ];
        }

        return [
            'label' => 'Offline',
            'tone' => 'danger',
            'reason' => 'This module has not synced recently enough to be trusted operationally.',
        ];
    }

    private function applySubscriberFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('company', fn (Builder $company) => $company->where('company_name', 'like', '%' . $search . '%'));
            });
        }

        if (!empty($filters['plan_code'])) {
            $query->whereHas('subscription', fn (Builder $subscription) => $subscription->where('plan_code', $filters['plan_code']));
        }

        if (!empty($filters['billing_status'])) {
            $query->whereHas('subscription', fn (Builder $subscription) => $subscription->where('billing_status', $filters['billing_status']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['business_model'])) {
            $query->whereHas('company', fn (Builder $company) => $company->where('business_model', $filters['business_model']));
        }
    }
}
