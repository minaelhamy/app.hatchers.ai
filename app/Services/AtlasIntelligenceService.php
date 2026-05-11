<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Founder;
use App\Models\FounderActionPlan;
use App\Models\FounderConversationThread;
use App\Models\ModuleSnapshot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AtlasIntelligenceService
{
    public function syncFounderOnboarding(Founder $founder, Company $company, array $payload): void
    {
        $company->loadMissing(['verticalBlueprint', 'businessBrief', 'icpProfiles']);
        $brief = $company->businessBrief;
        $icp = $company->icpProfiles()->latest()->first();
        $body = [
            'app' => 'os',
            'role' => 'founder',
            'username' => $founder->username,
            'email' => $founder->email,
            'name' => $founder->full_name,
            'company_brief' => $company->company_brief,
            'company' => [
                'company_name' => $company->company_name,
                'business_model' => $company->business_model,
                'industry' => $company->industry,
                'vertical' => (string) ($company->verticalBlueprint?->name ?? ''),
                'primary_city' => (string) ($company->primary_city ?? ''),
                'service_radius' => (string) ($company->service_radius ?? ''),
            ],
            'operations' => [
                'onboarding_completed' => true,
                'source' => 'app.hatchers.ai',
            ],
            'snapshot' => [
                'business_model' => $company->business_model,
                'stage' => $company->stage,
                'website_status' => $company->website_status,
                'launch_stage' => (string) ($company->launch_stage ?? ''),
                'website_generation_status' => (string) ($company->website_generation_status ?? ''),
                'business_summary' => (string) ($brief?->business_summary ?? ''),
                'problem_solved' => (string) ($brief?->problem_solved ?? ''),
                'primary_icp_name' => (string) ($icp?->primary_icp_name ?? ''),
            ],
            'sync_summary' => 'Founder completed OS onboarding.',
        ];

        $this->sendIntelligenceSync($founder, $body, 'Atlas onboarding sync failed');
    }

    public function syncFounderMutation(Founder $founder, array $payload): void
    {
        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $weeklyState = $founder->weeklyState;
        $commercialSummary = $founder->commercialSummary;

        $body = [
            'app' => 'os',
            'role' => trim((string) ($payload['role'] ?? 'founder')),
            'username' => $founder->username,
            'email' => $founder->email,
            'name' => $founder->full_name,
            'company_brief' => (string) ($company?->company_brief ?? ''),
            'company' => array_filter([
                'company_name' => (string) ($company?->company_name ?? ''),
                'business_model' => (string) ($company?->business_model ?? ''),
                'industry' => (string) ($company?->industry ?? ''),
                'company_description' => (string) ($company?->company_brief ?? ''),
                'target_audience' => (string) ($intelligence?->target_audience ?? ''),
                'ideal_customer_profile' => (string) ($intelligence?->ideal_customer_profile ?? ''),
                'brand_voice' => (string) ($intelligence?->brand_voice ?? ''),
                'differentiators' => (string) ($intelligence?->differentiators ?? ''),
                'content_goals' => (string) ($intelligence?->content_goals ?? ''),
                'core_offer' => (string) ($intelligence?->core_offer ?? ''),
                'primary_growth_goal' => (string) ($intelligence?->primary_growth_goal ?? ''),
                'known_blockers' => (string) ($intelligence?->known_blockers ?? ''),
            ], static fn ($value) => $value !== ''),
            'operations' => [
                'execution' => [
                    'weekly_focus' => (string) ($weeklyState?->weekly_focus ?? ''),
                    'open_tasks' => (int) ($weeklyState?->open_tasks ?? 0),
                    'completed_tasks' => (int) ($weeklyState?->completed_tasks ?? 0),
                    'open_milestones' => (int) ($weeklyState?->open_milestones ?? 0),
                    'completed_milestones' => (int) ($weeklyState?->completed_milestones ?? 0),
                ],
                'commercial' => [
                    'business_model' => (string) ($commercialSummary?->business_model ?? ''),
                    'product_count' => (int) ($commercialSummary?->product_count ?? 0),
                    'service_count' => (int) ($commercialSummary?->service_count ?? 0),
                    'order_count' => (int) ($commercialSummary?->order_count ?? 0),
                    'booking_count' => (int) ($commercialSummary?->booking_count ?? 0),
                    'customer_count' => (int) ($commercialSummary?->customer_count ?? 0),
                    'gross_revenue' => (float) ($commercialSummary?->gross_revenue ?? 0),
                    'currency' => (string) ($commercialSummary?->currency ?? 'USD'),
                ],
            ],
            'snapshot' => [
                'source' => 'app.hatchers.ai',
                'action' => (string) ($payload['action'] ?? 'os_update'),
                'field' => (string) ($payload['field'] ?? ''),
                'value' => (string) ($payload['value'] ?? ''),
            ],
            'sync_summary' => (string) ($payload['sync_summary'] ?? 'Founder context was updated from Hatchers OS.'),
        ];

        if (!empty($payload['payload']) && is_array($payload['payload'])) {
            $body['snapshot'] = array_merge($body['snapshot'], $payload['payload']);
        }

        $this->sendIntelligenceSync($founder, $body, 'Atlas mutation sync failed');
    }

    public function chatFromOs(Founder $founder, string $message, string $currentPage = 'os_dashboard', ?string $threadKey = null): array
    {
        $secret = trim((string) config('services.atlas.shared_secret'));
        $endpoint = rtrim((string) config('services.atlas.base_url'), '/') . '/hatchers/assistant/chat';

        if ($secret === '' || $endpoint === '/hatchers/assistant/chat') {
            return ['ok' => false, 'error' => 'Atlas assistant is not configured.'];
        }

        $company = $founder->company;
        $intelligence = $company?->intelligence;
        $subscription = $founder->subscription;
        $weeklyState = $founder->weeklyState;
        $commercialSummary = $founder->commercialSummary;
        $snapshots = $founder->moduleSnapshots->keyBy('module');
        $orders = (int) ($commercialSummary?->order_count ?? 0);
        $bookings = (int) ($commercialSummary?->booking_count ?? 0);
        $openTasks = (int) ($weeklyState?->open_tasks ?? 0);
        $completedTasks = (int) ($weeklyState?->completed_tasks ?? 0);
        $progress = (int) ($weeklyState?->weekly_progress_percent ?? 0);
        $revenue = (float) ($commercialSummary?->gross_revenue ?? 0);
        $currency = (string) ($commercialSummary?->currency ?? 'USD');
        $mentorEntitled = (bool) (
            (($founder->mentor_entitled_until && $founder->mentor_entitled_until->isFuture()) || $founder->mentor_entitled_until?->isToday()) ||
            (($subscription?->plan_code ?? null) === 'hatchers-os-mentor') ||
            str_contains(strtolower((string) ($subscription?->plan_name ?? '')), 'mentor')
        );
        $conversationMemory = $this->conversationMemory($founder, $threadKey);
        $launchPlanSummary = $this->launchPlanSummary($founder);
        $recentTasks = FounderActionPlan::query()
            ->where('founder_id', $founder->id)
            ->where('context', 'task')
            ->orderBy('available_on')
            ->orderByDesc('priority')
            ->limit(6)
            ->get()
            ->map(fn (FounderActionPlan $task): array => [
                'title' => (string) $task->title,
                'description' => (string) $task->description,
                'status' => (string) ($task->status ?? 'pending'),
                'platform' => (string) ($task->platform ?? ''),
                'milestone' => (string) data_get($task->metadata_json, 'milestone', ''),
            ])
            ->values()
            ->all();

        $payload = [
            'app' => 'os',
            'role' => $founder->role ?: 'founder',
            'current_page' => $currentPage,
            'message' => $message,
            'assistant_mode' => 'founder_mentor',
            'name' => $founder->full_name,
            'username' => $founder->username,
            'email' => $founder->email,
            'company_brief' => (string) ($company?->company_brief ?? ''),
            'mentor_brief' => [
                'positioning' => 'Act as the founder mentor inside Hatchers OS. Give clear, direct-response advice grounded in the founder’s real OS data.',
                'methodology' => [
                    'Blend Sell Like Crazy style direct-response discipline with Alex Hormozi style value creation, offer clarity, proof, and risk reversal.',
                    'Use Sabri Suby style principles in paraphrased form only: lead quality, hooks, urgency, risk reversal, clear next steps, and persistent follow-up.',
                    'Use Alex Hormozi style principles in paraphrased form only: make the offer easier to say yes to, increase perceived value, lower friction, and tie advice to concrete outcomes.',
                    'Bias toward the next revenue action, not generic motivation.',
                    'When the founder is stuck, turn the reply into the next three concrete actions inside Hatchers OS.',
                    'When discussing plans or tasks, critique them like a mentor: what is strong, what is weak, what should happen next, and what should be cut.',
                ],
                'mentor_subscription_behavior' => [
                    'ai_mentor_entitled' => $mentorEntitled,
                    'instruction' => $mentorEntitled
                        ? 'This founder is entitled to the AI mentor experience. Act like the primary mentor inside Hatchers OS with proactive guidance, accountability, and clear next actions.'
                        : 'Support this founder helpfully, but do not assume they are on the mentor-guided plan unless the context shows it.',
                ],
                'system_knowledge' => [
                    'You are expected to understand Hatchers OS, LMS, Atlas, Bazaar, and Servio as one connected founder system.',
                    'Hatchers OS is the primary founder workspace and subscription authority.',
                    'Atlas handles company intelligence, campaigns, content generation, and specialist agents.',
                    'LMS handles learning plans, milestones, and execution support.',
                    'Bazaar handles product selling, product stores, and orders.',
                    'Servio handles service selling, bookings, services, staff, and working hours.',
                    'Founders should not be told to purchase separate plans inside those tools.',
                    'Answer founder product questions clearly, including where to go, what each tool does, and what step should happen next.',
                    'You can recommend that Hatchers build the founder website after onboarding is complete, but only after confirming the founder is ready.',
                ],
                'response_style' => [
                    'Lead with the direct answer, then the brief explanation.',
                    'Use short sections, bullets, and numbered steps instead of long blocks.',
                    'Default to headings like Situation, What matters most, Next actions, or Fix this first when useful.',
                    'For reviews, prefer Strengths, Weaknesses, and Next move.',
                    'For blockers, prefer Likely issue, Why it matters, and Next actions.',
                    'Keep most replies concise unless the founder explicitly asks for more depth.',
                    'Be conversational and mentor-like. Ask a useful follow-up question when it helps move the founder forward.',
                    'Offer concrete ideas, angles, or options instead of only describing what you did.',
                    'If a request is ambiguous, ask one clarifying question before assuming the action.',
                    'After advising on a plan or task, usually suggest the next best move in plain language.',
                ],
            ],
            'conversation_memory' => $conversationMemory,
            'launch_plan' => $launchPlanSummary,
            'recent_tasks' => $recentTasks,
            'company' => [
                'company_name' => (string) ($company?->company_name ?? ''),
                'business_model' => (string) ($company?->business_model ?? ''),
                'industry' => (string) ($company?->industry ?? ''),
                'company_description' => (string) ($company?->company_brief ?? ''),
                'target_audience' => (string) ($intelligence?->target_audience ?? ''),
                'ideal_customer_profile' => (string) ($intelligence?->ideal_customer_profile ?? ''),
                'brand_voice' => (string) ($intelligence?->brand_voice ?? ''),
                'core_offer' => (string) ($intelligence?->core_offer ?? ''),
                'primary_growth_goal' => (string) ($intelligence?->primary_growth_goal ?? ''),
                'known_blockers' => (string) ($intelligence?->known_blockers ?? ''),
            ],
            'operations' => [
                'subscription' => [
                    'plan_name' => (string) ($subscription?->plan_name ?? ''),
                    'billing_status' => (string) ($subscription?->billing_status ?? ''),
                ],
                'execution' => [
                    'weekly_focus' => (string) ($weeklyState?->weekly_focus ?? ''),
                    'open_tasks' => $openTasks,
                    'completed_tasks' => $completedTasks,
                    'open_milestones' => (int) ($weeklyState?->open_milestones ?? 0),
                    'completed_milestones' => (int) ($weeklyState?->completed_milestones ?? 0),
                ],
                'commercial' => [
                    'business_model' => (string) ($commercialSummary?->business_model ?? ''),
                    'product_count' => (int) ($commercialSummary?->product_count ?? 0),
                    'service_count' => (int) ($commercialSummary?->service_count ?? 0),
                    'order_count' => $orders,
                    'booking_count' => $bookings,
                    'customer_count' => (int) ($commercialSummary?->customer_count ?? 0),
                    'gross_revenue' => $revenue,
                    'currency' => $currency,
                ],
            ],
            'snapshot' => [
                'workspace' => 'Hatchers OS unified founder workspace',
                'weekly_progress_percent' => $progress,
                'next_meeting_at' => optional($weeklyState?->next_meeting_at)?->toIso8601String(),
                'founder_status' => [
                    'current_focus' => (string) ($weeklyState?->weekly_focus ?? ''),
                    'orders_and_bookings' => $orders + $bookings,
                    'revenue' => strtoupper($currency) . ' ' . number_format($revenue, 0),
                    'execution_summary' => sprintf(
                        '%d open tasks, %d completed tasks, weekly progress %d%%.',
                        $openTasks,
                        $completedTasks,
                        $progress
                    ),
                ],
                'module_snapshot' => $this->condenseSnapshots($snapshots),
            ],
            'sync_summary' => 'Founder is chatting from the unified Hatchers OS dashboard and wants practical coaching based on live company, task, learning, order, and booking data.',
        ];

        $json = json_encode($payload);
        if ($json === false) {
            return ['ok' => false, 'error' => 'Failed to encode assistant payload.'];
        }

        $signature = hash_hmac('sha256', $json, $secret);

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'X-Hatchers-Signature' => $signature,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);
        } catch (\Throwable $exception) {
            Log::warning('Atlas OS chat failed', [
                'founder_id' => $founder->id,
                'message' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Atlas is temporarily unavailable.'];
        }

        if (!$response->successful()) {
            return [
                'ok' => false,
                'error' => (string) ($response->json('error') ?: 'Atlas could not respond right now.'),
            ];
        }

        return [
            'ok' => true,
            'reply' => (string) $response->json('reply', ''),
            'actions' => $response->json('actions', []),
        ];
    }

    private function condenseSnapshots($snapshots): array
    {
        $summary = [];

        foreach ($snapshots as $module => $snapshot) {
            if (!$snapshot instanceof ModuleSnapshot) {
                continue;
            }

            $payload = $snapshot->payload_json ?? [];
            $summary[$module] = [
                'readiness_score' => (int) $snapshot->readiness_score,
                'current_page' => $payload['current_page'] ?? null,
                'key_counts' => $payload['key_counts'] ?? [],
                'summary' => $payload['summary'] ?? [],
            ];
        }

        return $summary;
    }

    private function conversationMemory(Founder $founder, ?string $threadKey = null): array
    {
        $query = FounderConversationThread::query()
            ->where('founder_id', $founder->id)
            ->where('source_channel', 'atlas_assistant');

        if (trim((string) $threadKey) !== '') {
            $query->where('thread_key', (string) $threadKey);
        }

        $thread = $query
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->first();

        if (!$thread) {
            return [
                'thread_key' => '',
                'label' => 'New founder chat',
                'messages' => [],
            ];
        }

        $meta = is_array($thread->meta_json) ? $thread->meta_json : [];
        $messages = collect(is_array($meta['messages'] ?? null) ? $meta['messages'] : [])
            ->take(-8)
            ->map(fn (array $message): array => [
                'type' => (string) ($message['type'] ?? ''),
                'text' => (string) ($message['text'] ?? ''),
                'page' => (string) ($message['page'] ?? ''),
                'created_at' => (string) ($message['created_at'] ?? ''),
            ])
            ->values()
            ->all();

        return [
            'thread_key' => (string) $thread->thread_key,
            'label' => (string) ($meta['label'] ?? 'Founder chat'),
            'messages' => $messages,
        ];
    }

    private function launchPlanSummary(Founder $founder): array
    {
        $launchSystem = $founder->launchSystems()->latest('id')->first();
        $strategy = is_array($launchSystem?->launch_strategy_json) ? $launchSystem->launch_strategy_json : [];

        return [
            'title' => (string) ($strategy['title'] ?? ''),
            'summary' => (string) ($strategy['summary'] ?? ''),
            'weekly_focus' => (string) ($strategy['weekly_focus'] ?? ''),
            'north_star_metrics' => array_values((array) ($strategy['north_star_metrics'] ?? [])),
            'milestones' => collect((array) ($strategy['milestones'] ?? []))
                ->take(4)
                ->values()
                ->all(),
        ];
    }

    private function sendIntelligenceSync(Founder $founder, array $body, string $logMessage): void
    {
        $secret = trim((string) config('services.atlas.shared_secret'));
        $endpoint = rtrim((string) config('services.atlas.base_url'), '/') . '/hatchers/intelligence/sync';

        if ($secret === '' || $endpoint === '/hatchers/intelligence/sync') {
            return;
        }

        $json = json_encode($body);
        if ($json === false) {
            return;
        }

        $signature = hash_hmac('sha256', $json, $secret);

        try {
            Http::timeout(12)
                ->withHeaders([
                    'X-Hatchers-Signature' => $signature,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $body);
        } catch (\Throwable $exception) {
            Log::warning($logMessage, [
                'founder_id' => $founder->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
