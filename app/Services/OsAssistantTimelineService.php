<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\FounderConversationThread;
use Illuminate\Support\Str;

class OsAssistantTimelineService
{
    private const THREAD_KEY_PREFIX = 'atlas-assistant';

    public function timeline(Founder $founder, ?string $threadKey = null, int $limit = 12): array
    {
        $thread = $this->thread($founder, $threadKey);
        if (!$thread) {
            return [];
        }

        $meta = is_array($thread->meta_json) ? $thread->meta_json : [];
        $messages = collect(is_array($meta['messages'] ?? null) ? $meta['messages'] : [])
            ->filter(fn ($message) => is_array($message) && !empty($message['text']))
            ->take(-$limit)
            ->values()
            ->all();

        return $messages;
    }

    public function summary(Founder $founder, ?string $threadKey = null): array
    {
        $thread = $this->thread($founder, $threadKey);
        if (!$thread) {
            return [
                'thread_key' => '',
                'label' => 'New founder chat',
                'pinned_plan' => [],
            ];
        }

        $meta = is_array($thread->meta_json) ? $thread->meta_json : [];

        return [
            'thread_key' => (string) $thread->thread_key,
            'label' => (string) ($meta['label'] ?? 'Founder chat'),
            'pinned_plan' => is_array($meta['pinned_plan'] ?? null) ? $meta['pinned_plan'] : [],
        ];
    }

    public function threads(Founder $founder, int $limit = 8): array
    {
        return FounderConversationThread::query()
            ->where('founder_id', $founder->id)
            ->where('thread_key', 'like', self::THREAD_KEY_PREFIX . '%')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (FounderConversationThread $thread): array {
                $meta = is_array($thread->meta_json) ? $thread->meta_json : [];

                return [
                    'thread_key' => (string) $thread->thread_key,
                    'label' => (string) ($meta['label'] ?? 'Founder chat'),
                    'latest_message' => (string) ($thread->latest_message ?? ''),
                    'updated_at' => optional($thread->last_activity_at ?? $thread->updated_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function record(
        Founder $founder,
        ?string $threadKey,
        string $currentPage,
        string $message,
        string $reply,
        array $actions = []
    ): FounderConversationThread {
        $existingThread = $this->thread($founder, $threadKey);
        $isNewThread = !$existingThread;
        $resolvedThreadKey = $existingThread?->thread_key ?: $this->generateThreadKey();
        $thread = $existingThread ?: new FounderConversationThread([
            'founder_id' => $founder->id,
            'thread_key' => $resolvedThreadKey,
        ]);

        $meta = is_array($thread->meta_json) ? $thread->meta_json : [];
        $messages = collect(is_array($meta['messages'] ?? null) ? $meta['messages'] : []);
        $now = now();

        $messages->push([
            'type' => 'user',
            'title' => 'You',
            'text' => trim($message),
            'page' => $currentPage,
            'created_at' => $now->toIso8601String(),
        ]);

        $messages->push([
            'type' => 'atlas',
            'title' => 'Atlas Assistant',
            'text' => trim($reply),
            'page' => $currentPage,
            'actions' => collect($actions)
                ->filter(fn ($action) => is_array($action))
                ->map(fn (array $action): array => [
                    'label' => (string) ($action['cta'] ?? $action['title'] ?? $action['platform'] ?? ''),
                    'workspace_key' => (string) ($action['os_workspace_key'] ?? ''),
                    'href' => (string) ($action['os_href'] ?? ''),
                ])
                ->filter(fn (array $action): bool => $action['label'] !== '')
                ->take(3)
                ->values()
                ->all(),
            'created_at' => $now->toIso8601String(),
        ]);

        $thread->fill([
            'company_id' => $founder->company?->id,
            'source_channel' => 'atlas_assistant',
            'status' => 'open',
            'latest_message' => trim($reply),
            'last_activity_at' => $now,
            'meta_json' => array_merge($meta, [
                'workspace' => 'founder_os',
                'assistant_mode' => 'founder_mentor',
                'last_page' => $currentPage,
                'label' => $this->buildLabel($isNewThread, $meta, $message),
                'pinned_plan' => $this->extractPinnedPlan($reply, $actions),
                'messages' => $messages->take(-20)->values()->all(),
            ]),
        ]);

        $thread->save();

        return $thread;
    }

    public function startNewThread(Founder $founder): FounderConversationThread
    {
        $thread = new FounderConversationThread([
            'founder_id' => $founder->id,
            'thread_key' => $this->generateThreadKey(),
        ]);

        $thread->fill([
            'company_id' => $founder->company?->id,
            'source_channel' => 'atlas_assistant',
            'status' => 'open',
            'latest_message' => '',
            'last_activity_at' => now(),
            'meta_json' => [
                'workspace' => 'founder_os',
                'assistant_mode' => 'founder_mentor',
                'label' => 'New founder chat',
                'pinned_plan' => [],
                'messages' => [],
            ],
        ]);

        $thread->save();

        return $thread;
    }

    private function thread(Founder $founder, ?string $threadKey = null): ?FounderConversationThread
    {
        $query = FounderConversationThread::query()
            ->where('founder_id', $founder->id)
            ->where('thread_key', 'like', self::THREAD_KEY_PREFIX . '%');

        if (trim((string) $threadKey) !== '') {
            return (clone $query)
                ->where('thread_key', (string) $threadKey)
                ->first();
        }

        return $query
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->first();
    }

    private function generateThreadKey(): string
    {
        return self::THREAD_KEY_PREFIX . '-' . Str::lower(Str::random(10));
    }

    private function buildLabel(bool $isNewThread, array $meta, string $message): string
    {
        if (!$isNewThread && !empty($meta['label'])) {
            return (string) $meta['label'];
        }

        return Str::limit(trim($message), 42, '…') ?: 'Founder chat';
    }

    private function extractPinnedPlan(string $reply, array $actions = []): array
    {
        $steps = [];
        $normalized = preg_replace("/\r\n?/", "\n", trim($reply)) ?? '';

        foreach (preg_split("/\n+/", $normalized) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches) === 1 || preg_match('/^[-*]\s+(.+)$/', $line, $matches) === 1) {
                $steps[] = trim((string) ($matches[1] ?? ''));
            }
        }

        if (count($steps) < 2) {
            $steps = collect($actions)
                ->filter(fn ($action) => is_array($action))
                ->map(fn (array $action) => trim((string) ($action['cta'] ?? $action['title'] ?? $action['label'] ?? '')))
                ->filter()
                ->take(3)
                ->values()
                ->all();
        } else {
            $steps = array_slice($steps, 0, 3);
        }

        if (empty($steps)) {
            return [];
        }

        return [
            'title' => "Today's plan",
            'steps' => $steps,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
