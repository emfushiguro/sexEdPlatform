<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteMinorPasswordResetRequest;
use App\Models\MinorAccountHandoff;
use App\Services\Auth\MinorAccountHandoffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class MinorPasswordRecoveryController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.minor-password-reset', [
            'code' => (string) $request->query('code'),
        ]);
    }

    public function store(
        CompleteMinorPasswordResetRequest $request,
        MinorAccountHandoffService $handoffs,
    ): RedirectResponse {
        try {
            $handoffs->consumePasswordReset(
                (string) $request->validated('code'),
                (string) $request->validated('username'),
                (string) $request->validated('password'),
            );
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'code' => 'This recovery code is invalid or expired.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset.');
    }

    public function approve(
        Request $request,
        MinorAccountHandoff $handoff,
        MinorAccountHandoffService $handoffs,
    ): View {
        try {
            [$approvedHandoff, $code] = $handoffs->approvePasswordReset($handoff, $request->user());
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'recovery' => 'This recovery request is no longer available.',
            ]);
        }

        return view('guardian.minor-password-reset-code', [
            'handoff' => $approvedHandoff,
            'child' => $approvedHandoff->child,
            'code' => $code,
        ]);
    }
}
