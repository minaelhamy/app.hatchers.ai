<?php

namespace App\Services;

use App\Models\CommercialSummary;
use App\Models\Founder;
use App\Models\FounderWeeklyState;
use App\Models\ModuleSnapshot;

class ModuleSnapshotService
{
    public function verifySignature(string $rawBody, string $providedSignature): bool
    {
        $secret = trim((string) config('services.os.shared_secret'));
        if ($secret === '' || $providedSignature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $providedSignature);
    }

    public function store(Founder $founder, string $module, array $payload): ModuleSnapshot
    {
        $snapshot = ModuleSnapshot::updateOrCreate(
            [
                'founder_id' => $founder->id,
                'module' => $module,
            ],
            [
                'snapshot_version' => 'v1',
                'readiness_score' => (int) ($payload['readiness_score'] ?? 0),
                'payload_json' => [
                    'current_page' => $payload['current_page'] ?? null,
                    'key_counts' => $payload['key_counts'] ?? [],
                    'status_flags' => $payload['status_flags'] ?? [],
                    'recent_activity' => $payload['recent_activity'] ?? [],
                    'summary' => $payload['summary'] ?? [],
                ],
                'snapshot_updated_at' => $payload['updated_at'] ?? now(),
            ]
        );

        $this->updateDerivedSummaries($founder, $module, $payload);

        return $snapshot;
    }

    private function updateDerivedSummaries(Founder $founder, string $module, array $payload): void
    {
        $keyCounts = $payload['key_counts'] ?? [];
        $summary = $payload['summary'] ?? [];

        if ($module === 'lms') {
            FounderWeeklyState::updateOrCreate(
                ['founder_id' => $founder->id],
                [
                    'open_tasks' => max(0, ((int) ($keyCounts['task_count'] ?? 0)) - ((int) ($keyCounts['completed_task_count'] ?? 0))),
                    'completed_tasks' => (int) ($keyCounts['completed_task_count'] ?? 0),
                    'open_milestones' => max(0, ((int) ($keyCounts['milestone_count'] ?? 0)) - ((int) ($keyCounts['completed_milestone_count'] ?? 0))),
                    'completed_milestones' => (int) ($keyCounts['completed_milestone_count'] ?? 0),
                    'next_meeting_at' => $summary['next_meeting_at'] ?? null,
                    'weekly_focus' => $summary['weekly_focus'] ?? null,
                    'weekly_progress_percent' => (int) ($summary['weekly_progress_percent'] ?? 0),
                    'state_updated_at' => $payload['updated_at'] ?? now(),
                ]
            );
        }

        if (in_array($module, ['bazaar', 'servio'], true)) {
            $commercial = CommercialSummary::firstOrNew(['founder_id' => $founder->id]);
            $commercial->business_model = $module === 'bazaar' ? 'product' : 'service';

            if ($module === 'bazaar') {
                $commercial->product_count = (int) ($keyCounts['product_count'] ?? $commercial->product_count ?? 0);
                $commercial->order_count = (int) ($keyCounts['order_count'] ?? $commercial->order_count ?? 0);
                $commercial->customer_count = (int) ($keyCounts['customer_count'] ?? $commercial->customer_count ?? 0);
            }

            if ($module === 'servio') {
                $commercial->service_count = (int) ($keyCounts['service_count'] ?? $commercial->service_count ?? 0);
                $commercial->booking_count = (int) ($keyCounts['booking_count'] ?? $commercial->booking_count ?? 0);
                $commercial->customer_count = (int) ($keyCounts['customer_count'] ?? $commercial->customer_count ?? 0);
            }

            if (isset($summary['gross_revenue'])) {
                $commercial->gross_revenue = (float) $summary['gross_revenue'];
            }

            if (!empty($summary['currency'])) {
                $commercial->currency = (string) $summary['currency'];
            }

            $commercial->summary_updated_at = $payload['updated_at'] ?? now();
            $commercial->save();
        }
    }
}
