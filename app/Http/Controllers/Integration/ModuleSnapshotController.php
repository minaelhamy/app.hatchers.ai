<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\Founder;
use App\Services\ModuleSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleSnapshotController extends Controller
{
    public function store(Request $request, string $module, ModuleSnapshotService $snapshotService): JsonResponse
    {
        $module = strtolower(trim($module));
        if (!in_array($module, ['lms', 'atlas', 'bazaar', 'servio'], true)) {
            return response()->json(['success' => false, 'error' => 'Unsupported module.'], 422);
        }

        $rawBody = $request->getContent();
        if (!$snapshotService->verifySignature($rawBody, (string) $request->header('X-Hatchers-Signature'))) {
            return response()->json(['success' => false, 'error' => 'Invalid snapshot signature.'], 403);
        }

        $payload = $request->validate([
            'founder_id' => ['nullable', 'integer'],
            'email' => ['nullable', 'email'],
            'username' => ['nullable', 'string'],
            'updated_at' => ['nullable', 'date'],
            'readiness_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'current_page' => ['nullable', 'string'],
            'key_counts' => ['nullable', 'array'],
            'status_flags' => ['nullable', 'array'],
            'recent_activity' => ['nullable', 'array'],
            'summary' => ['nullable', 'array'],
        ]);

        $founder = $this->resolveFounder($payload);
        if (!$founder) {
            return response()->json(['success' => false, 'error' => 'Founder not found.'], 404);
        }

        $snapshot = $snapshotService->store($founder, $module, $payload);

        return response()->json([
            'success' => true,
            'founder_id' => $founder->id,
            'module' => $snapshot->module,
            'updated_at' => $snapshot->snapshot_updated_at,
        ]);
    }

    private function resolveFounder(array $payload): ?Founder
    {
        if (!empty($payload['founder_id'])) {
            return Founder::find((int) $payload['founder_id']);
        }

        if (!empty($payload['email'])) {
            $founder = Founder::where('email', $payload['email'])->first();
            if ($founder) {
                return $founder;
            }
        }

        if (!empty($payload['username'])) {
            return Founder::where('username', $payload['username'])->first();
        }

        return null;
    }
}
