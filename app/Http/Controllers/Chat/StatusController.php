<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:online,busy,do_not_disturb,offline,active,inactive'],
        ]);

        $normalizedInput = strtolower(trim((string) $validated['status']));

        $storedStatus = match ($normalizedInput) {
            'online', 'active' => 'active',
            'do_not_disturb', 'inactive' => 'inactive',
            'busy' => 'busy',
            default => 'offline',
        };

        $responseStatus = match ($storedStatus) {
            'active' => 'online',
            'inactive' => 'do_not_disturb',
            default => $storedStatus,
        };

        $user = $request->user();
        $user->forceFill([
            'chat_status' => $storedStatus,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $responseStatus,
            ]);
        }

        return back()->with('status', 'chat-status-updated');
    }
}
