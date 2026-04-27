<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\FounderConversationThread;

class OsAssistantTimelineService
{
    private const THREAD_KEY = 'atlas-assistant';

    public function timeline(Founder $founder, int $limit = 12): array
    {
        $thread = $this->thread($founder);
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

    public function record(
        Founder $founder,
        string $currentPage,
        string $message,
        string $reply,
        array $actions = []
    ): FounderConversationThread {
        $thread = $this->thread($founder) ?: new FounderConversationThread([
            'founder_id' => $founder->id,
            'thread_key' => self::THREAD_KEY,
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
                'messages' => $messages->take(-20)->values()->all(),
            ]),
        ]);

        $thread->save();

        return $thread;
    }

    private function thread(Founder $founder): ?FounderConversationThread
    {
        return FounderConversationThread::query()
            ->where('founder_id', $founder->id)
            ->where('thread_key', self::THREAD_KEY)
            ->first();
    }
}
