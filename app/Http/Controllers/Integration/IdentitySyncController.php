<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\IdentitySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentitySyncController extends Controller
{
    public function store(Request $request, string $role, IdentitySyncService $identitySyncService): JsonResponse
    {
        $role = strtolower(trim($role));
        if (!in_array($role, ['founder', 'mentor', 'admin'], true)) {
            return response()->json(['success' => false, 'error' => 'Unsupported identity role.'], 422);
        }

        $rawBody = $request->getContent();
        if (!$identitySyncService->verifySignature($rawBody, (string) $request->header('X-Hatchers-Signature'))) {
            return response()->json(['success' => false, 'error' => 'Invalid identity signature.'], 403);
        }

        $payload = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'previous_username' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'previous_email' => ['nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'max:255'],
            'auth_source' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_brief' => ['nullable', 'string'],
            'business_model' => ['nullable', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', 'string', 'max:50'],
            'website_status' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $identitySyncService->upsert($role, $payload, $payload['password'] ?? null);

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'role' => $user->role,
            'username' => $user->username,
        ]);
    }
}
