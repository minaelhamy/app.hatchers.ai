<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\MentorAssignment;

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
}
