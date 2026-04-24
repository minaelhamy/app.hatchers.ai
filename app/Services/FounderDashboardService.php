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
        $commerceAlerts = $this->buildCommerceAlerts($snapshots);
        $commerceOperations = $this->buildCommerceOperations($snapshots);
        $automationSummary = $this->buildAutomationSummary($founder, $commerceOperations);
        $workspace = $this->buildWorkspace($founder, $company, $weeklyState, $actions, $activityFeed, $execution, $atlas, $growth, $syncStatus, $commerceAlerts, $commerceOperations, $automationSummary);

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
            'commerce_alerts' => $commerceAlerts,
            'commerce_operations' => $commerceOperations,
            'automation_summary' => $automationSummary,
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
        array $syncStatus,
        array $commerceAlerts,
        array $commerceOperations,
        array $automationSummary
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
                $actions,
                $commerceAlerts,
                $commerceOperations,
                $automationSummary
            ),
            'activity_feed_groups' => $this->buildActivityFeedGroups($activityFeed),
            'commerce_operations' => $commerceOperations,
            'automation_summary' => $automationSummary,
        ];
    }

    private function buildAutomationSummary(Founder $founder, array $commerceOperations): array
    {
        $rules = $founder->automationRules()
            ->where('status', 'active')
            ->latest()
            ->get();

        $templates = [];
        foreach ($rules as $rule) {
            $meta = is_array($rule->metadata_json) ? $rule->metadata_json : [];
            $templates[] = [
                'name' => (string) $rule->name,
                'trigger_type' => (string) $rule->trigger_type,
                'module_scope' => (string) $rule->module_scope,
                'delivery' => (string) ($meta['delivery'] ?? 'email'),
                'template_key' => (string) ($meta['template_key'] ?? ''),
            ];
        }

        $mapped = [];
        foreach ($templates as $template) {
            $queueCount = match ($template['trigger_type']) {
                'order_unpaid' => (int) ($commerceOperations['unpaid_orders'] ?? 0),
                'booking_unscheduled' => (int) ($commerceOperations['unscheduled_bookings'] ?? 0),
                'booking_unassigned' => (int) ($commerceOperations['needs_staff_assignment'] ?? 0),
                default => 0,
            };

            $href = match ($template['trigger_type']) {
                'order_unpaid' => route('founder.commerce.orders', ['status' => 'all', 'queue' => 'unpaid']),
                'booking_unscheduled' => route('founder.commerce.bookings', ['status' => 'all', 'queue' => 'unscheduled']),
                'booking_unassigned' => route('founder.commerce.bookings', ['status' => 'all', 'queue' => 'needs_staff']),
                default => route('founder.automations'),
            };

            $mapped[] = [
                'name' => $template['name'],
                'module_scope' => $template['module_scope'],
                'delivery' => $template['delivery'],
                'queue_count' => $queueCount,
                'status_label' => $queueCount > 0 ? 'Watching ' . $queueCount . ' record' . ($queueCount === 1 ? '' : 's') : 'Ready',
                'href' => $href,
                'cta_label' => $queueCount > 0 ? 'Open queue' : 'Open automations',
            ];
        }

        return [
            'active_count' => count($mapped),
            'items' => $mapped,
            'has_unpaid_order_rule' => collect($mapped)->contains(fn (array $item) => $item['module_scope'] === 'bazaar' && str_contains(strtolower($item['name']), 'unpaid order')),
            'has_unscheduled_booking_rule' => collect($mapped)->contains(fn (array $item) => $item['module_scope'] === 'servio' && str_contains(strtolower($item['name']), 'unscheduled booking')),
            'has_provider_assignment_rule' => collect($mapped)->contains(fn (array $item) => $item['module_scope'] === 'servio' && str_contains(strtolower($item['name']), 'provider assignment')),
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
        $actions,
        array $commerceAlerts,
        array $commerceOperations,
        array $automationSummary
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

        if (!empty($commerceAlerts)) {
            $items[] = [
                'title' => 'Resolve commerce alerts',
                'description' => 'You have ' . count($commerceAlerts) . ' stock or availability alerts that need attention in Commerce.',
                'label' => 'Open Commerce',
                'href' => route('founder.commerce'),
            ];
        }

        if ((int) ($commerceOperations['pending_orders'] ?? 0) > 0) {
            $items[] = [
                'title' => 'Handle pending orders',
                'description' => 'You have ' . (int) $commerceOperations['pending_orders'] . ' Bazaar orders waiting for fulfillment updates.',
                'label' => 'Open Orders',
                'href' => route('founder.commerce.orders'),
            ];
        }

        if (
            (int) ($commerceOperations['unpaid_orders'] ?? 0) > 0
            && empty($automationSummary['has_unpaid_order_rule'])
        ) {
            $items[] = [
                'title' => 'Set an unpaid order reminder rule',
                'description' => 'You have unpaid orders in the queue, but no saved OS reminder rule to follow them up automatically.',
                'label' => 'Open Automations',
                'href' => route('founder.automations'),
            ];
        }

        if ((int) ($commerceOperations['ready_to_ship_orders'] ?? 0) > 0) {
            $items[] = [
                'title' => 'Dispatch ready-to-ship orders',
                'description' => 'You have ' . (int) $commerceOperations['ready_to_ship_orders'] . ' paid orders in processing that are ready for fulfillment follow-up.',
                'label' => 'Open Orders',
                'href' => route('founder.commerce.orders'),
            ];
        }

        if ((int) ($commerceOperations['pending_bookings'] ?? 0) > 0) {
            $items[] = [
                'title' => 'Review pending bookings',
                'description' => 'You have ' . (int) $commerceOperations['pending_bookings'] . ' Servio bookings waiting for scheduling or confirmation.',
                'label' => 'Open Bookings',
                'href' => route('founder.commerce.bookings'),
            ];
        }

        if (
            (int) ($commerceOperations['unscheduled_bookings'] ?? 0) > 0
            && empty($automationSummary['has_unscheduled_booking_rule'])
        ) {
            $items[] = [
                'title' => 'Set an unscheduled booking reminder',
                'description' => 'You have bookings waiting for a schedule, but no OS reminder rule is watching them yet.',
                'label' => 'Open Automations',
                'href' => route('founder.automations'),
            ];
        }

        if ((int) ($commerceOperations['needs_staff_assignment'] ?? 0) > 0) {
            $items[] = [
                'title' => 'Assign staff to upcoming bookings',
                'description' => 'You have ' . (int) $commerceOperations['needs_staff_assignment'] . ' active bookings without a staff assignment.',
                'label' => 'Open Bookings',
                'href' => route('founder.commerce.bookings'),
            ];
        }

        if (
            (int) ($commerceOperations['needs_staff_assignment'] ?? 0) > 0
            && empty($automationSummary['has_provider_assignment_rule'])
        ) {
            $items[] = [
                'title' => 'Set a provider assignment reminder',
                'description' => 'You have active bookings without staff assigned, and no OS reminder rule is watching that gap.',
                'label' => 'Open Automations',
                'href' => route('founder.automations'),
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

    private function buildCommerceOperations($snapshots): array
    {
        $bazaarPayload = $snapshots->get('bazaar')?->payload_json ?? [];
        $servioPayload = $snapshots->get('servio')?->payload_json ?? [];
        $recentOrders = collect($bazaarPayload['recent_orders'] ?? [])->filter(fn ($item) => is_array($item));
        $recentBookings = collect($servioPayload['recent_bookings'] ?? [])->filter(fn ($item) => is_array($item));

        $pendingOrders = $recentOrders
            ->filter(fn (array $order) => in_array((string) ($order['status'] ?? 'pending'), ['pending', 'processing'], true))
            ->values();
        $unpaidOrders = $recentOrders
            ->filter(fn (array $order) => (string) ($order['payment_status'] ?? 'unpaid') !== 'paid')
            ->values();
        $readyToShipOrders = $recentOrders
            ->filter(fn (array $order) => (string) ($order['status'] ?? '') === 'processing' && (string) ($order['payment_status'] ?? '') === 'paid')
            ->values();
        $pendingBookings = $recentBookings
            ->filter(fn (array $booking) => in_array((string) ($booking['status'] ?? 'pending'), ['pending', 'processing'], true))
            ->values();
        $unscheduledBookings = $recentBookings
            ->filter(fn (array $booking) => trim((string) ($booking['booking_date'] ?? '')) === '' || trim((string) ($booking['booking_time'] ?? '')) === '')
            ->values();
        $needsStaffAssignment = $recentBookings
            ->filter(fn (array $booking) => in_array((string) ($booking['status'] ?? 'pending'), ['pending', 'processing'], true) && trim((string) ($booking['staff_id'] ?? '')) === '')
            ->values();

        $queue = [];

        foreach ($pendingOrders->take(2) as $order) {
            $queue[] = [
                'type' => 'order',
                'title' => 'Order ' . (string) ($order['order_number'] ?? ''),
                'description' => trim((string) ($order['customer_name'] ?? 'Customer')) . ' · ' . ucfirst((string) ($order['status'] ?? 'pending')),
                'label' => ((string) ($order['payment_status'] ?? 'unpaid') === 'paid' && (string) ($order['status'] ?? '') === 'processing') ? 'Ready to ship' : 'Open Orders',
                'href' => (string) ($order['payment_status'] ?? 'unpaid') === 'unpaid'
                    ? route('founder.commerce.orders', ['status' => 'all', 'queue' => 'unpaid'])
                    : (((string) ($order['payment_status'] ?? '') === 'paid' && (string) ($order['status'] ?? '') === 'processing')
                        ? route('founder.commerce.orders', ['status' => 'all', 'queue' => 'ready_to_ship'])
                        : route('founder.commerce.orders', ['status' => 'pending', 'queue' => 'pending'])),
            ];
        }

        foreach ($pendingBookings->take(2) as $booking) {
            $bookingHasSchedule = trim((string) ($booking['booking_date'] ?? '')) !== '' && trim((string) ($booking['booking_time'] ?? '')) !== '';
            $bookingNeedsStaff = trim((string) ($booking['staff_id'] ?? '')) === '';
            $queue[] = [
                'type' => 'booking',
                'title' => 'Booking ' . (string) ($booking['booking_number'] ?? ''),
                'description' => trim((string) ($booking['customer_name'] ?? 'Customer')) . ' · ' . ucfirst((string) ($booking['status'] ?? 'pending')),
                'label' => !$bookingHasSchedule ? 'Schedule booking' : ($bookingNeedsStaff ? 'Assign staff' : 'Open Bookings'),
                'href' => !$bookingHasSchedule
                    ? route('founder.commerce.bookings', ['status' => 'all', 'queue' => 'unscheduled'])
                    : ($bookingNeedsStaff
                        ? route('founder.commerce.bookings', ['status' => 'all', 'queue' => 'needs_staff'])
                        : route('founder.commerce.bookings', ['status' => 'pending', 'queue' => 'pending'])),
            ];
        }

        return [
            'pending_orders' => $pendingOrders->count(),
            'unpaid_orders' => $unpaidOrders->count(),
            'ready_to_ship_orders' => $readyToShipOrders->count(),
            'pending_bookings' => $pendingBookings->count(),
            'unscheduled_bookings' => $unscheduledBookings->count(),
            'needs_staff_assignment' => $needsStaffAssignment->count(),
            'queue' => array_slice($queue, 0, 4),
        ];
    }

    private function buildCommerceAlerts($snapshots): array
    {
        $alerts = [];
        $bazaarPayload = $snapshots->get('bazaar')?->payload_json ?? [];
        $servioPayload = $snapshots->get('servio')?->payload_json ?? [];

        foreach ((array) ($bazaarPayload['recent_products'] ?? []) as $product) {
            if (!is_array($product)) {
                continue;
            }

            $qty = (int) ($product['qty'] ?? 0);
            $lowQty = (int) ($product['low_qty'] ?? 0);
            $stockManaged = (int) ($product['stock_management'] ?? 2) === 1;

            if ($stockManaged && $qty <= 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'Out of stock',
                    'description' => trim((string) ($product['title'] ?? 'Product')) . ' is at zero stock in Bazaar.',
                    'label' => 'Open Commerce',
                    'href' => route('founder.commerce'),
                ];
                continue;
            }

            if ($stockManaged && $lowQty > 0 && $qty <= $lowQty) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Low stock alert',
                    'description' => trim((string) ($product['title'] ?? 'Product')) . ' is at ' . $qty . ' units and has reached its low-stock threshold.',
                    'label' => 'Open Commerce',
                    'href' => route('founder.commerce'),
                ];
            }
        }

        foreach ((array) ($servioPayload['recent_services'] ?? []) as $service) {
            if (!is_array($service)) {
                continue;
            }

            $days = array_values(array_filter((array) ($service['availability_days'] ?? [])));
            $status = (string) ($service['status'] ?? 'active');

            if ($status !== 'active') {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Service inactive',
                    'description' => trim((string) ($service['title'] ?? 'Service')) . ' is currently inactive in Servio.',
                    'label' => 'Open Commerce',
                    'href' => route('founder.commerce'),
                ];
                continue;
            }

            if (count($days) === 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'No availability set',
                    'description' => trim((string) ($service['title'] ?? 'Service')) . ' does not currently have open availability days.',
                    'label' => 'Open Commerce',
                    'href' => route('founder.commerce'),
                ];
                continue;
            }

            if (count($days) <= 2) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Limited availability',
                    'description' => trim((string) ($service['title'] ?? 'Service')) . ' is only open on ' . implode(', ', $days) . '.',
                    'label' => 'Open Commerce',
                    'href' => route('founder.commerce'),
                ];
            }
        }

        return array_slice($alerts, 0, 4);
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
