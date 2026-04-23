<?php

namespace App\Services;

use App\Models\Founder;
use App\Models\OsAuditLog;
use App\Models\OsOperationException;

class OsOperationsLogService
{
    public function recordAudit(
        Founder $actor,
        string $action,
        string $subjectType,
        ?int $subjectId,
        string $summary,
        array $metadata = []
    ): void {
        OsAuditLog::create([
            'actor_user_id' => $actor->id,
            'actor_role' => (string) ($actor->role ?: 'founder'),
            'action' => trim($action),
            'subject_type' => trim($subjectType),
            'subject_id' => $subjectId,
            'summary' => trim($summary),
            'metadata_json' => $metadata,
        ]);
    }

    public function recordException(
        string $module,
        string $operation,
        string $message,
        ?int $founderId = null,
        array $payload = []
    ): void {
        OsOperationException::create([
            'module' => strtolower(trim($module)),
            'operation' => trim($operation),
            'founder_id' => $founderId,
            'message' => trim($message),
            'status' => 'open',
            'payload_json' => $payload,
        ]);
    }

    public function resolveException(OsOperationException $exception): void
    {
        $exception->forceFill([
            'status' => 'resolved',
            'resolved_at' => now(),
        ])->save();
    }
}
