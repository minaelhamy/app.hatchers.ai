<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\MentorAssignment;
use App\Models\ModuleSnapshot;
use App\Models\Subscription;

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

        $moduleHealth = collect(['lms', 'atlas', 'bazaar', 'servio'])->map(function (string $module): array {
            $query = ModuleSnapshot::query()->where('module', $module);

            return [
                'module' => strtoupper($module),
                'synced_founders' => (clone $query)->distinct('founder_id')->count('founder_id'),
                'avg_readiness' => (int) round((float) ((clone $query)->avg('readiness_score') ?? 0)),
                'last_synced_at' => optional((clone $query)->latest('snapshot_updated_at')->first()?->snapshot_updated_at)->toDateTimeString(),
            ];
        })->all();

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
            ],
            'module_health' => $moduleHealth,
            'recent_subscribers' => $recentSubscribers,
            'role_launches' => $roleLaunches,
        ];
    }
}
