<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\ModuleSnapshot;
use Illuminate\Support\Carbon;

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
        $syncStatus = $this->buildSyncStatus($moduleCards);
        $workspace = $this->buildWorkspace($founder, $company, $weeklyState, $actions, $activityFeed, $execution, $atlas, $growth, $syncStatus);

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
            'sync_status' => $syncStatus,
            'execution' => $execution,
            'growth' => $growth,
            'atlas' => $atlas,
            'actions' => $actions,
            'workspace' => $workspace,
            'metrics' => [
                'weekly_progress_percent' => (int) ($weeklyState->weekly_progress_percent ?? 0),
                'open_tasks' => (int) ($weeklyState->open_tasks ?? 0),
                'orders_bookings' => (int) (($commercialSummary->order_count ?? 0) + ($commercialSummary->booking_count ?? 0)),
                'gross_revenue' => (float) ($commercialSummary->gross_revenue ?? 0),
                'currency' => strtoupper((string) ($commercialSummary->currency ?? 'USD')),
            ],
        ];
    }

    private function buildWorkspace(
        Founder $founder,
        $company,
        $weeklyState,
        $actions,
        array $activityFeed,
        array $execution,
        array $atlas,
        array $growth,
        array $syncStatus
    ): array {
        $today = now();
        $firstName = trim((string) preg_replace('/\s+.*/', '', (string) $founder->full_name));
        $mentorName = $execution['mentor_name'] ?: 'your weekly OS rhythm';
        $mentorLinked = trim((string) $execution['mentor_name']) !== '';
        $hasMentorPlan = str_contains(strtolower((string) ($founder->subscription?->plan_code ?? '')), 'mentor')
            || str_contains(strtolower((string) ($founder->subscription?->plan_name ?? '')), 'mentor');
        $mentorSession = [
            'section_label' => $mentorLinked || $hasMentorPlan ? 'Mentoring' : 'Execution Rhythm',
            'date_label' => $mentorLinked
                ? strtoupper($today->format('l, M j')) . ' · 1:00PM'
                : strtoupper($today->format('l, M j')),
            'title' => $mentorLinked || $hasMentorPlan ? '1 on 1 session (30 mins)' : 'Self-guided weekly check-in',
            'subtitle' => $mentorLinked
                ? 'with ' . $mentorName
                : 'Use your tasks, learning plan, marketing, and website workspaces to move the week forward.',
            'badge' => $mentorLinked
                ? ($execution['next_meeting_at'] ? 'Join Session' : 'Session in 1d')
                : 'Free plan',
            'badge_tone' => $mentorLinked && $execution['next_meeting_at'] ? 'success' : 'neutral',
            'drawer_description' => $mentorLinked
                ? 'Join your mentor session from Hatchers Ai OS and keep your weekly execution aligned.'
                : 'This founder workspace is currently self-guided. Use Hatchers Ai OS to drive progress through tasks, lessons, marketing, commerce, and website workflows.',
        ];

        $learningTitle = $actions->first()?->title ?: ($execution['weekly_focus'] ?: 'This week\'s founder sprint');
        $learningDescription = $company?->company_brief ?: 'Keep moving through your weekly build plan inside Hatchers Ai OS.';
        $primaryAction = $actions->first();
        $primaryLessonCompleted = $primaryAction && ($primaryAction->completed_at !== null || in_array((string) $primaryAction->status, ['completed', 'complete', 'done'], true));
        $learningItem = [
            'id' => $primaryAction?->id,
            'title' => $learningTitle,
            'subtitle' => mb_strimwidth((string) $learningDescription, 0, 72, '...'),
            'badge' => $primaryLessonCompleted ? 'Completed' : ($execution['open_milestones'] > 0 ? 'Lesson in 3d 2h' : 'Open lesson'),
            'completed' => $primaryLessonCompleted,
            'status_label' => $primaryLessonCompleted ? 'Reopen lesson' : 'Complete lesson',
            'detail_type' => 'lesson',
            'detail_heading' => $learningTitle,
            'detail_due' => $primaryLessonCompleted ? 'Completed' : ($execution['next_meeting_at'] ?: 'This week'),
            'detail_owner' => $mentorLinked ? ('Mentor · ' . $mentorName) : 'Hatchers Ai OS',
            'detail_description' => $learningDescription,
            'mentor_name' => $mentorLinked ? $mentorName : '',
            'comments' => $this->buildDrawerComments($founder, $activityFeed, 'lesson'),
        ];

        $taskCards = [];
        foreach ($actions->take(3) as $index => $action) {
            $isCompleted = $action->completed_at !== null || in_array((string) $action->status, ['completed', 'complete', 'done'], true);
            $taskCards[] = [
                'id' => $action->id,
                'label' => 'Milestone name',
                'due' => $isCompleted ? 'Completed' : ($index === 0 ? 'Due in 3 days' : ($index === 1 ? 'Due in 5 days' : 'Queued this week')),
                'title' => $action->title,
                'description' => $action->description,
                'cta' => $isCompleted ? '' : ($index === 0 ? 'Build with AI' : 'Write with AI'),
                'completed' => $isCompleted,
                'mentor_name' => $mentorLinked ? $mentorName : '',
                'mentor_context' => $mentorLinked
                    ? 'Aligned with your current mentor execution rhythm.'
                    : 'OS-guided task from your current weekly founder plan.',
                'status_label' => $isCompleted ? 'Reopen task' : 'Complete task',
                'detail_type' => 'task',
                'detail_heading' => $action->title,
                'detail_due' => $isCompleted ? 'Completed' : ($index === 0 ? strtoupper($today->copy()->addDays(3)->format('D, M j')) . ' - 1:00 PM' : strtoupper($today->copy()->addDays(5)->format('D, M j'))),
                'detail_owner' => $mentorLinked ? ('Mentor linked · ' . $mentorName) : 'Milestone name',
                'detail_description' => $action->description,
                'comments' => $this->buildDrawerComments($founder, $activityFeed, 'task'),
            ];
        }

        if (count($taskCards) < 3) {
            $taskCards[] = [
                'id' => null,
                'label' => 'Milestone name',
                'due' => 'Completed',
                'title' => 'List your first 10 potential customers',
                'description' => 'Identify specific people you can reach out to for customer discovery conversations.',
                'cta' => '',
                'completed' => true,
                'mentor_name' => $mentorLinked ? $mentorName : '',
                'mentor_context' => $mentorLinked
                    ? 'Previously completed during mentor-guided execution.'
                    : 'Previously completed in your weekly founder workflow.',
                'status_label' => '',
                'detail_type' => 'task',
                'detail_heading' => 'List your first 10 potential customers',
                'detail_due' => 'Completed',
                'detail_owner' => $mentorLinked ? ('Mentor linked · ' . $mentorName) : 'Milestone name',
                'detail_description' => 'This task has already been completed and kept here as part of your weekly momentum.',
                'comments' => $this->buildDrawerComments($founder, $activityFeed, 'task'),
            ];
        }

        $notifications = $this->buildNotifications($founder, $activityFeed, $execution);

        return [
            'first_name' => $firstName !== '' ? $firstName : 'Founder',
            'mentor_session' => $mentorSession,
            'learning_item' => $learningItem,
            'learning_plan_entries' => $this->buildLearningPlanEntries($learningItem, $actions, $founder, $activityFeed),
            'task_cards' => array_slice($taskCards, 0, 3),
            'task_center_entries' => $taskCards,
            'notifications' => $notifications,
            'notification_groups' => $this->groupNotifications($notifications),
            'unread_notification_count' => count($notifications),
            'calendar' => $this->buildCalendar($today),
            'ai_tools' => [
                ['title' => 'Landing Pages'],
                ['title' => 'Forms'],
                ['title' => 'Social Media'],
                ['title' => 'CRM'],
                ['title' => 'Payments & Bookings'],
                ['title' => 'SEO'],
                ['title' => 'Messaging Automation'],
            ],
            'quick_prompt' => $atlas['primary_growth_goal'] ?: 'Ask AI anything about your project...',
            'next_best_actions' => $this->buildNextBestActions(
                $company,
                $execution,
                $growth,
                $atlas,
                $syncStatus,
                $actions
            ),
            'activity_feed_groups' => $this->buildActivityFeedGroups($activityFeed),
        ];
    }

    private function buildLearningPlanEntries(array $learningItem, $actions, Founder $founder, array $activityFeed): array
    {
        $entries = [$learningItem];

        foreach ($actions->slice(1, 2)->values() as $index => $action) {
            $isCompleted = $action->completed_at !== null || in_array((string) $action->status, ['completed', 'complete', 'done'], true);
            $entries[] = [
                'id' => $action->id,
                'title' => $action->title,
                'subtitle' => mb_strimwidth((string) $action->description, 0, 72, '...'),
                'badge' => $isCompleted ? 'Completed' : ($index === 0 ? 'Lesson in 5d 1h' : 'Queued this week'),
                'completed' => $isCompleted,
                'status_label' => $isCompleted ? 'Reopen lesson' : 'Complete lesson',
                'detail_type' => 'lesson',
                'detail_heading' => $action->title,
                'detail_due' => $isCompleted ? 'Completed' : ($index === 0 ? 'Later this week' : 'Next up'),
                'detail_owner' => !empty($learningItem['mentor_name']) ? ('Mentor · ' . $learningItem['mentor_name']) : 'Hatchers Ai OS',
                'detail_description' => $action->description,
                'mentor_name' => $learningItem['mentor_name'] ?? '',
                'comments' => $this->buildDrawerComments($founder, $activityFeed, 'lesson'),
            ];
        }

        return $entries;
    }

    private function buildNotifications(Founder $founder, array $activityFeed, array $execution): array
    {
        $notifications = [];

        if ($execution['next_meeting_at']) {
            $notifications[] = [
                'title' => 'You have an upcoming mentoring session.',
                'meta' => $execution['next_meeting_at'],
                'kind' => 'mentor',
                'age_label' => '21h',
                'is_new' => true,
            ];
        }

        if ($execution['open_tasks'] > 0) {
            $notifications[] = [
                'title' => 'There are ' . $execution['open_tasks'] . ' open tasks for this week.',
                'meta' => 'Needs attention',
                'kind' => 'task',
                'age_label' => '1 day',
                'is_new' => false,
            ];
        }

        foreach (array_slice($activityFeed, 0, 4) as $index => $item) {
            $notifications[] = [
                'title' => $item['message'],
                'meta' => $item['updated_at'] ?: 'Recently',
                'kind' => strtolower((string) ($item['module'] ?? 'update')),
                'age_label' => $index === 0 ? '1 day' : ($index === 1 ? '2 days' : 'Earlier'),
                'is_new' => false,
            ];
        }

        return array_slice($notifications, 0, 4);
    }

    private function groupNotifications(array $notifications): array
    {
        $new = array_values(array_filter($notifications, fn (array $item) => !empty($item['is_new'])));
        $earlier = array_values(array_filter($notifications, fn (array $item) => empty($item['is_new'])));

        return [
            'new' => $new,
            'earlier' => $earlier,
        ];
    }

    private function buildCalendar($today): array
    {
        $start = $today->copy()->startOfMonth()->startOfWeek();
        $end = $today->copy()->endOfMonth()->endOfWeek();
        $days = [];

        while ($start <= $end) {
            $days[] = [
                'day' => (int) $start->format('j'),
                'in_month' => $start->month === $today->month,
                'is_today' => $start->isSameDay($today),
            ];
            $start->addDay();
        }

        return [
            'month_label' => strtoupper($today->format('F Y')),
            'days' => $days,
        ];
    }

    private function buildDrawerComments(Founder $founder, array $activityFeed, string $type): array
    {
        $messages = array_values(array_filter(array_map(
            fn (array $item) => trim((string) ($item['message'] ?? '')),
            array_slice($activityFeed, 0, 2)
        )));

        if (empty($messages)) {
            $messages = $type === 'lesson'
                ? ['Use this lesson to align your next business move with your weekly focus.']
                : ['Track progress here and use AI when you need help moving faster.'];
        }

        return array_map(fn (string $message) => [
            'author' => $founder->full_name,
            'message' => $message,
        ], $messages);
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

    private function buildSyncStatus(array $moduleCards): array
    {
        $statuses = [];
        $issues = [];

        foreach ($moduleCards as $card) {
            $updatedAt = !empty($card['updated_at']) ? Carbon::parse($card['updated_at']) : null;
            $hoursSince = $updatedAt?->diffInHours(now());

            if ($updatedAt === null) {
                $label = 'Setting up';
                $tone = 'warning';
                $reason = 'This module has not synced founder data yet.';
            } elseif ($hoursSince !== null && $hoursSince <= 24) {
                $label = 'Healthy';
                $tone = 'success';
                $reason = 'Recently synced into the OS.';
            } elseif ($hoursSince !== null && $hoursSince <= 72) {
                $label = 'Stale';
                $tone = 'warning';
                $reason = 'Synced before, but needs refresh attention.';
            } else {
                $label = 'Offline';
                $tone = 'danger';
                $reason = 'This module is too old to trust operationally.';
            }

            $statuses[] = [
                'module' => $card['module'],
                'status' => $label,
                'tone' => $tone,
                'reason' => $reason,
                'updated_at' => $card['updated_at'],
            ];

            if ($label !== 'Healthy') {
                $issues[] = [
                    'module' => $card['module'],
                    'message' => $reason,
                ];
            }
        }

        return [
            'modules' => $statuses,
            'issues' => $issues,
            'healthy_count' => collect($statuses)->where('status', 'Healthy')->count(),
        ];
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

    private function buildActivityFeedGroups(array $activityFeed): array
    {
        $grouped = collect($activityFeed)
            ->groupBy('module')
            ->map(fn ($items, $module): array => [
                'module' => $module,
                'items' => collect($items)
                    ->map(fn (array $item): array => [
                        'message' => $item['message'],
                        'updated_at' => $item['updated_at'] ?: 'Recently',
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return $grouped;
    }

    private function buildNextBestActions(
        $company,
        array $execution,
        array $growth,
        array $atlas,
        array $syncStatus,
        $actions
    ): array {
        $items = [];

        if (!empty($syncStatus['issues'])) {
            $items[] = [
                'title' => 'Review module trust before acting',
                'description' => 'One or more backend engines are stale or offline. Check the OS activity center before you rely on those records.',
                'label' => 'Open Activity',
                'href' => route('founder.activity') . '#sync-issues',
            ];
        }

        if ((int) ($execution['open_tasks'] ?? 0) > 0) {
            $primaryTask = $actions->first();
            $items[] = [
                'title' => 'Complete your highest-priority founder task',
                'description' => $primaryTask?->title ?: 'Keep momentum moving by completing the next execution task in your queue.',
                'label' => 'Open Tasks',
                'href' => route('founder.tasks'),
            ];
        }

        if ((int) ($atlas['generated_campaigns_count'] ?? 0) === 0) {
            $items[] = [
                'title' => 'Start your next campaign from the OS',
                'description' => $atlas['primary_growth_goal']
                    ? 'Use your growth goal to launch a campaign without leaving Hatchers Ai OS.'
                    : 'Create your first campaign and begin building a visible pipeline from the OS.',
                'label' => 'Open Marketing',
                'href' => route('founder.marketing'),
            ];
        }

        if (((int) ($growth['product_count'] ?? 0) + (int) ($growth['service_count'] ?? 0)) === 0) {
            $items[] = [
                'title' => 'Publish your first offer',
                'description' => 'Set up a starter product or service so your storefront has something real to sell or book.',
                'label' => 'Open Commerce',
                'href' => route('founder.commerce'),
            ];
        }

        if (trim((string) ($company?->company_brief ?? '')) === '') {
            $items[] = [
                'title' => 'Tighten your company profile',
                'description' => 'Your brand brief is still thin. Adding it will improve Atlas outputs and mentor context.',
                'label' => 'Open Settings',
                'href' => route('founder.settings'),
            ];
        }

        if (empty($items)) {
            $items[] = [
                'title' => 'Review your cross-tool momentum',
                'description' => 'Your dashboard is in a healthy state. Use the activity center to review what changed across LMS, Atlas, Bazaar, and Servio.',
                'label' => 'Open Activity',
                'href' => route('founder.activity'),
            ];
        }

        return array_slice($items, 0, 4);
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
