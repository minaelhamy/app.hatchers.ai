<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\ModuleSnapshot;

class FounderDashboardService
{
    public function build(Founder $founder): array
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $subscription = $founder->subscription;
        $weeklyState = $founder->weeklyState;
        $commercialSummary = $founder->commercialSummary;
        $snapshots = $founder->moduleSnapshots->keyBy('module');
        $actions = $founder->actionPlans()
            ->orderByDesc('priority')
            ->limit(5)
            ->get();
        $moduleCards = $this->buildModuleCards($snapshots);
        $activityFeed = $this->buildActivityFeed($snapshots);
        $execution = $this->buildExecutionSummary($weeklyState, $snapshots);
        $growth = $this->buildGrowthSummary($commercialSummary, $snapshots);
        $atlas = $this->buildAtlasSummary($snapshots);

        return [
            'founder' => $founder,
            'company' => $company,
            'intelligence' => $intelligence,
            'subscription' => $subscription,
            'weekly_state' => $weeklyState,
            'commercial_summary' => $commercialSummary,
            'snapshots' => $snapshots,
            'module_cards' => $moduleCards,
            'activity_feed' => $activityFeed,
            'execution' => $execution,
            'growth' => $growth,
            'atlas' => $atlas,
            'actions' => $actions,
            'metrics' => [
                'weekly_progress_percent' => (int) ($weeklyState->weekly_progress_percent ?? 0),
                'open_tasks' => (int) ($weeklyState->open_tasks ?? 0),
                'orders_bookings' => (int) (($commercialSummary->order_count ?? 0) + ($commercialSummary->booking_count ?? 0)),
                'gross_revenue' => (float) ($commercialSummary->gross_revenue ?? 0),
                'currency' => strtoupper((string) ($commercialSummary->currency ?? 'USD')),
            ],
        ];
    }

    private function buildModuleCards($snapshots): array
    {
        $defaults = [
            'lms' => 'Mentoring and execution state has not synced yet.',
            'atlas' => 'Atlas intelligence and content status has not synced yet.',
            'bazaar' => 'Ecommerce storefront state has not synced yet.',
            'servio' => 'Service and booking state has not synced yet.',
        ];

        $cards = [];
        foreach (['lms', 'atlas', 'bazaar', 'servio'] as $module) {
            /** @var ModuleSnapshot|null $snapshot */
            $snapshot = $snapshots->get($module);
            $payload = $snapshot?->payload_json ?? [];
            $summary = $payload['summary'] ?? [];
            $keyCounts = $payload['key_counts'] ?? [];
            $statusFlags = $payload['status_flags'] ?? [];
            $recentActivity = $payload['recent_activity'] ?? [];

            $description = $defaults[$module];
            $highlights = [];
            if ($module === 'lms' && $snapshot) {
                $description = sprintf(
                    '%d tasks, %d milestones, weekly progress %d%%.',
                    (int) ($keyCounts['task_count'] ?? 0),
                    (int) ($keyCounts['milestone_count'] ?? 0),
                    (int) ($summary['weekly_progress_percent'] ?? 0)
                );
                if (!empty($summary['weekly_focus'])) {
                    $highlights[] = 'Focus: ' . $summary['weekly_focus'];
                }
                if (!empty($summary['mentor_name'])) {
                    $highlights[] = 'Mentor: ' . $summary['mentor_name'];
                }
            }
            if ($module === 'atlas' && $snapshot) {
                $description = sprintf(
                    '%d posts, %d campaigns, %d images generated.',
                    (int) ($keyCounts['generated_posts_count'] ?? 0),
                    (int) ($keyCounts['generated_campaigns_count'] ?? 0),
                    (int) ($keyCounts['generated_images_count'] ?? 0)
                );
                if (!empty($summary['primary_growth_goal'])) {
                    $highlights[] = 'Goal: ' . $summary['primary_growth_goal'];
                }
                if (!empty($summary['brand_voice'])) {
                    $highlights[] = 'Voice: ' . $summary['brand_voice'];
                }
            }
            if ($module === 'bazaar' && $snapshot) {
                $description = sprintf(
                    '%d products, %d orders, %s revenue.',
                    (int) ($keyCounts['product_count'] ?? 0),
                    (int) ($keyCounts['order_count'] ?? 0),
                    $this->formatMoney((float) ($summary['gross_revenue'] ?? 0), (string) ($summary['currency'] ?? 'USD'))
                );
                if (!empty($summary['website_title'])) {
                    $highlights[] = 'Store: ' . $summary['website_title'];
                }
                if (!empty($summary['theme_template'])) {
                    $highlights[] = 'Theme: ' . $summary['theme_template'];
                }
            }
            if ($module === 'servio' && $snapshot) {
                $description = sprintf(
                    '%d services, %d bookings, %s revenue.',
                    (int) ($keyCounts['service_count'] ?? 0),
                    (int) ($keyCounts['booking_count'] ?? 0),
                    $this->formatMoney((float) ($summary['gross_revenue'] ?? 0), (string) ($summary['currency'] ?? 'USD'))
                );
                if (!empty($summary['website_title'])) {
                    $highlights[] = 'Site: ' . $summary['website_title'];
                }
                if (!empty($summary['theme_template'])) {
                    $highlights[] = 'Theme: ' . $summary['theme_template'];
                }
            }

            $cards[] = [
                'key' => $module,
                'module' => strtoupper($module),
                'readiness_score' => (int) ($snapshot?->readiness_score ?? 0),
                'description' => $description,
                'highlights' => $highlights,
                'status_flags' => $statusFlags,
                'recent_activity' => is_array($recentActivity) ? $recentActivity : [],
                'updated_at' => $snapshot?->snapshot_updated_at?->toDateTimeString(),
            ];
        }

        return $cards;
    }

    private function buildActivityFeed($snapshots): array
    {
        $feed = [];

        foreach ($snapshots as $module => $snapshot) {
            $payload = $snapshot?->payload_json ?? [];
            $recentActivity = $payload['recent_activity'] ?? [];
            foreach ((array) $recentActivity as $entry) {
                if (!is_string($entry) || trim($entry) === '') {
                    continue;
                }

                $feed[] = [
                    'module' => strtoupper((string) $module),
                    'message' => trim($entry),
                    'updated_at' => $snapshot?->snapshot_updated_at?->toDateTimeString(),
                    'timestamp' => $snapshot?->snapshot_updated_at?->timestamp ?? 0,
                ];
            }
        }

        usort($feed, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($feed, 0, 8);
    }

    private function buildExecutionSummary($weeklyState, $snapshots): array
    {
        $lms = $snapshots->get('lms');
        $payload = $lms?->payload_json ?? [];
        $summary = $payload['summary'] ?? [];
        $counts = $payload['key_counts'] ?? [];

        return [
            'mentor_name' => (string) ($summary['mentor_name'] ?? ''),
            'weekly_focus' => (string) ($summary['weekly_focus'] ?? ($weeklyState->weekly_focus ?? '')),
            'next_meeting_at' => $weeklyState?->next_meeting_at?->toDayDateTimeString(),
            'open_tasks' => (int) ($weeklyState->open_tasks ?? 0),
            'completed_tasks' => (int) ($weeklyState->completed_tasks ?? ($counts['completed_task_count'] ?? 0)),
            'open_milestones' => (int) ($weeklyState->open_milestones ?? 0),
            'completed_milestones' => (int) ($weeklyState->completed_milestones ?? ($counts['completed_milestone_count'] ?? 0)),
        ];
    }

    private function buildGrowthSummary($commercialSummary, $snapshots): array
    {
        $bazaar = $snapshots->get('bazaar');
        $servio = $snapshots->get('servio');
        $bazaarPayload = $bazaar?->payload_json ?? [];
        $servioPayload = $servio?->payload_json ?? [];
        $bazaarSummary = $bazaarPayload['summary'] ?? [];
        $servioSummary = $servioPayload['summary'] ?? [];

        return [
            'business_model' => (string) ($commercialSummary->business_model ?? 'hybrid'),
            'product_count' => (int) ($commercialSummary->product_count ?? 0),
            'service_count' => (int) ($commercialSummary->service_count ?? 0),
            'order_count' => (int) ($commercialSummary->order_count ?? 0),
            'booking_count' => (int) ($commercialSummary->booking_count ?? 0),
            'customer_count' => (int) ($commercialSummary->customer_count ?? 0),
            'gross_revenue_formatted' => $this->formatMoney(
                (float) ($commercialSummary->gross_revenue ?? 0),
                (string) ($commercialSummary->currency ?? 'USD')
            ),
            'bazaar_title' => (string) ($bazaarSummary['website_title'] ?? ''),
            'servio_title' => (string) ($servioSummary['website_title'] ?? ''),
        ];
    }

    private function buildAtlasSummary($snapshots): array
    {
        $atlas = $snapshots->get('atlas');
        $payload = $atlas?->payload_json ?? [];
        $summary = $payload['summary'] ?? [];
        $counts = $payload['key_counts'] ?? [];

        return [
            'company_name' => (string) ($summary['company_name'] ?? ''),
            'business_model' => (string) ($summary['business_model'] ?? ''),
            'brand_voice' => (string) ($summary['brand_voice'] ?? ''),
            'primary_growth_goal' => (string) ($summary['primary_growth_goal'] ?? ''),
            'generated_posts_count' => (int) ($counts['generated_posts_count'] ?? 0),
            'generated_campaigns_count' => (int) ($counts['generated_campaigns_count'] ?? 0),
            'generated_images_count' => (int) ($counts['generated_images_count'] ?? 0),
            'recommended_actions_count' => (int) ($counts['recommended_actions_count'] ?? 0),
            'recent_campaigns' => is_array($summary['recent_campaigns'] ?? null) ? $summary['recent_campaigns'] : [],
            'archived_campaigns' => is_array($summary['archived_campaigns'] ?? null) ? $summary['archived_campaigns'] : [],
        ];
    }

    private function formatMoney(float $amount, string $currency): string
    {
        $code = strtoupper(trim($currency));
        if ($code === '') {
            $code = 'USD';
        }

        return $code . ' ' . number_format($amount, 0);
    }
}
