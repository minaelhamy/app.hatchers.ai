<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Founder;
use App\Models\MentorAssignment;
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
            'status_options' => ['active', 'paused', 'blocked'],
            'billing_statuses' => ['draft', 'trialing', 'active', 'paused', 'cancelled'],
            'plan_options' => [
                ['code' => 'hatchers-os', 'name' => 'Hatchers OS', 'amount' => 99],
                ['code' => 'hatchers-os-mentor', 'name' => 'Hatchers OS + Mentor', 'amount' => 600],
            ],
            'unassigned_founder_count' => collect($founders)->whereNull('assigned_mentor_id')->count(),
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
}
