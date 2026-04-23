<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\MentorAssignment;
use Illuminate\Support\Collection;

class MentorDashboardService
{
    public function build(Founder $mentor): array
    {
        $assignments = MentorAssignment::query()
            ->where('mentor_user_id', $mentor->id)
            ->where('status', 'active')
            ->with([
                'founder.company',
                'founder.subscription',
                'founder.weeklyState',
                'founder.commercialSummary',
                'founder.moduleSnapshots',
            ])
            ->latest('assigned_at')
            ->get();

        $founders = $assignments->map(function (MentorAssignment $assignment): array {
            $founder = $assignment->founder;
            $snapshots = $founder?->moduleSnapshots?->keyBy('module');
            $atlas = $snapshots?->get('atlas');
            $atlasSummary = $atlas?->payload_json['summary'] ?? [];

            return [
                'id' => $founder?->id,
                'name' => $founder?->full_name ?: 'Founder',
                'email' => $founder?->email ?: '',
                'company_name' => $founder?->company?->company_name ?: 'Company not named yet',
                'business_model' => ucfirst((string) ($founder?->company?->business_model ?? 'hybrid')),
                'weekly_focus' => (string) ($founder?->weeklyState?->weekly_focus ?: 'Weekly focus not synced yet'),
                'weekly_progress_percent' => (int) ($founder?->weeklyState?->weekly_progress_percent ?? 0),
                'open_tasks' => (int) ($founder?->weeklyState?->open_tasks ?? 0),
                'open_milestones' => (int) ($founder?->weeklyState?->open_milestones ?? 0),
                'gross_revenue' => (float) ($founder?->commercialSummary?->gross_revenue ?? 0),
                'next_meeting_at' => optional($founder?->weeklyState?->next_meeting_at)->toDayDateTimeString(),
                'primary_growth_goal' => (string) ($atlasSummary['primary_growth_goal'] ?? ''),
                'assigned_at' => optional($assignment->assigned_at)->toDateTimeString(),
            ];
        })->values();

        $openTasks = $founders->sum('open_tasks');
        $openMilestones = $founders->sum('open_milestones');
        $avgProgress = (int) round((float) ($founders->avg('weekly_progress_percent') ?? 0));
        $grossRevenue = (float) $founders->sum('gross_revenue');

        return [
            'mentor' => $mentor,
            'metrics' => [
                'assigned_founders' => $founders->count(),
                'open_tasks' => $openTasks,
                'open_milestones' => $openMilestones,
                'avg_progress' => $avgProgress,
                'gross_revenue' => $grossRevenue,
            ],
            'founders' => $founders->all(),
            'launches' => [
                [
                    'label' => 'Open LMS Mentor Workspace',
                    'url' => rtrim((string) config('modules.lms.base_url'), '/') . '/mentor',
                ],
                [
                    'label' => 'Open Atlas',
                    'url' => rtrim((string) config('modules.atlas.base_url'), '/'),
                ],
            ],
        ];
    }

    public function buildFounderDetail(Founder $mentor, Founder $founder): array
    {
        $assignment = MentorAssignment::query()
            ->where('mentor_user_id', $mentor->id)
            ->where('founder_id', $founder->id)
            ->where('status', 'active')
            ->latest('assigned_at')
            ->first();

        if (!$assignment) {
            abort(403);
        }

        $founder->loadMissing([
            'company',
            'subscription',
            'weeklyState',
            'commercialSummary',
            'moduleSnapshots',
            'actionPlans' => fn ($query) => $query->latest('priority')->limit(6),
        ]);

        $snapshots = $founder->moduleSnapshots->keyBy('module');
        $atlasSummary = $snapshots->get('atlas')?->payload_json['summary'] ?? [];
        $recentAtlasActivity = collect($snapshots->get('atlas')?->payload_json['recent_activity'] ?? [])->take(4)->values();
        $recentLmsActivity = collect($snapshots->get('lms')?->payload_json['recent_activity'] ?? [])->take(4)->values();
        $recentCommerceActivity = collect($snapshots->get('bazaar')?->payload_json['recent_activity'] ?? [])
            ->merge($snapshots->get('servio')?->payload_json['recent_activity'] ?? [])
            ->take(4)
            ->values();

        $actionPlans = $founder->actionPlans->map(function ($action): array {
            return [
                'id' => $action->id,
                'title' => $action->title,
                'description' => (string) $action->description,
                'platform' => strtoupper((string) $action->platform),
                'status' => (string) $action->status,
                'completed' => $action->completed_at !== null || in_array((string) $action->status, ['completed', 'complete', 'done'], true),
            ];
        })->values();

        return [
            'mentor' => $mentor,
            'founder' => [
                'id' => $founder->id,
                'name' => $founder->full_name,
                'email' => $founder->email,
                'company_name' => $founder->company?->company_name ?: 'Company not set yet',
                'company_brief' => (string) ($founder->company?->company_brief ?? ''),
                'business_model' => ucfirst((string) ($founder->company?->business_model ?? 'hybrid')),
                'plan_name' => (string) ($founder->subscription?->plan_name ?? 'Hatchers OS'),
                'billing_status' => (string) ($founder->subscription?->billing_status ?? 'draft'),
                'weekly_focus' => (string) ($founder->weeklyState?->weekly_focus ?? 'Weekly focus not synced yet'),
                'weekly_progress_percent' => (int) ($founder->weeklyState?->weekly_progress_percent ?? 0),
                'open_tasks' => (int) ($founder->weeklyState?->open_tasks ?? 0),
                'open_milestones' => (int) ($founder->weeklyState?->open_milestones ?? 0),
                'next_meeting_at' => optional($founder->weeklyState?->next_meeting_at)->toDayDateTimeString(),
                'gross_revenue' => (float) ($founder->commercialSummary?->gross_revenue ?? 0),
                'product_count' => (int) ($founder->commercialSummary?->product_count ?? 0),
                'service_count' => (int) ($founder->commercialSummary?->service_count ?? 0),
                'order_count' => (int) ($founder->commercialSummary?->order_count ?? 0),
                'booking_count' => (int) ($founder->commercialSummary?->booking_count ?? 0),
                'primary_growth_goal' => (string) ($atlasSummary['primary_growth_goal'] ?? ''),
                'brand_voice' => (string) ($atlasSummary['brand_voice'] ?? ''),
                'known_blockers' => (string) ($atlasSummary['known_blockers'] ?? ''),
                'assigned_at' => optional($assignment->assigned_at)->toDayDateTimeString(),
                'notes' => (string) ($assignment->notes ?? ''),
            ],
            'action_plans' => $actionPlans->all(),
            'meeting_prep' => $this->buildMeetingPrep($founder, $atlasSummary, $actionPlans),
            'activity' => [
                'atlas' => $this->normalizeActivity($recentAtlasActivity),
                'lms' => $this->normalizeActivity($recentLmsActivity),
                'commerce' => $this->normalizeActivity($recentCommerceActivity),
            ],
        ];
    }

    private function normalizeActivity(Collection $activity): array
    {
        return $activity
            ->map(fn ($item): array => [
                'message' => is_array($item) ? (string) ($item['message'] ?? json_encode($item)) : (string) $item,
            ])
            ->all();
    }

    private function buildMeetingPrep(Founder $founder, array $atlasSummary, Collection $actionPlans): array
    {
        $weeklyState = $founder->weeklyState;
        $commercial = $founder->commercialSummary;
        $topActions = $actionPlans
            ->take(3)
            ->map(fn (array $action): string => $action['title'])
            ->filter()
            ->values()
            ->all();

        $agenda = array_values(array_filter([
            $weeklyState?->weekly_focus ? 'Reconfirm weekly focus: ' . $weeklyState->weekly_focus : null,
            (int) ($weeklyState?->open_tasks ?? 0) > 0 ? 'Unblock ' . (int) $weeklyState->open_tasks . ' open task(s).' : null,
            (int) ($weeklyState?->open_milestones ?? 0) > 0 ? 'Review ' . (int) $weeklyState->open_milestones . ' open milestone(s).' : null,
            !empty($atlasSummary['known_blockers']) ? 'Address Atlas blocker: ' . $atlasSummary['known_blockers'] : null,
            ((int) ($commercial?->order_count ?? 0) + (int) ($commercial?->booking_count ?? 0)) === 0
                ? 'Discuss the next commercial conversion step.'
                : 'Review recent revenue and conversion signals.',
        ]));

        $executionSummary = array_values(array_filter([
            'Weekly progress: ' . (int) ($weeklyState?->weekly_progress_percent ?? 0) . '%',
            'Completed tasks: ' . (int) ($weeklyState?->completed_tasks ?? 0),
            'Open tasks: ' . (int) ($weeklyState?->open_tasks ?? 0),
            'Open milestones: ' . (int) ($weeklyState?->open_milestones ?? 0),
            'Next meeting: ' . (optional($weeklyState?->next_meeting_at)->toDayDateTimeString() ?: 'Not scheduled'),
        ]));

        $followUps = array_values(array_filter([
            !empty($topActions) ? 'Confirm ownership of: ' . implode(', ', array_slice($topActions, 0, 2)) : null,
            !empty($atlasSummary['primary_growth_goal']) ? 'Tie the next week of execution back to the growth goal: ' . $atlasSummary['primary_growth_goal'] : null,
            trim((string) ($founder->company?->company_brief ?? '')) === '' ? 'Ask the founder to tighten their company brief in OS settings.' : null,
        ]));

        return [
            'agenda' => $agenda,
            'execution_summary' => $executionSummary,
            'follow_ups' => $followUps,
        ];
    }
}
