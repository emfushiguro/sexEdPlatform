<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActivateMinorAccountRequest;
use App\Models\MinorAccountHandoff;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\TeenagerActivationReadyNotification;
use App\Services\Auth\MinorAccountHandoffService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class MinorAccountActivationController extends Controller
{
    public function show(Request $request, MinorAccountHandoffService $handoffs): View
    {
        $code = (string) $request->query('code');
        if ($code === '') {
            return view('auth.teenager-activation-code');
        }
        $handoff = $handoffs->findUsable($code, MinorAccountHandoff::PURPOSE_ACTIVATION);
        if ($handoff === null) {
            throw ValidationException::withMessages(['code' => 'This activation code is invalid or expired.']);
        }

        return view('auth.teenager-activation', [
            'code' => $code,
            'expiresAt' => $handoff->expires_at,
            'child' => $handoff->child,
            'profile' => $handoff->child?->learnerProfile,
            'adultAuthority' => $handoff->child && ! $handoff->child->requiresGuardian() && $handoff->relationship
                ? $handoffs->approvedAdultAuthority($handoff->relationship) : null,
        ]);
    }

    public function store(ActivateMinorAccountRequest $request, MinorAccountHandoffService $handoffs): RedirectResponse
    {
        try {
            $child = $handoffs->consumeActivation(
                (string) $request->validated('code'),
                (string) $request->validated('username'),
                (string) $request->validated('password'),
                (bool) $request->validated('adult_consent', false),
                (string) $request->validated('consent_method', 'self'),
            );
        } catch (Throwable) {
            throw ValidationException::withMessages(['code' => 'This activation code is invalid or expired.']);
        }

        $request->session()->regenerate();
        Auth::login($child);

        if ($child->email !== null) {
            event(new Registered($child));
        }

        return redirect()->route($child->requiresGuardian() ? 'learner.identity.create' : 'password.recovery-codes.settings');
    }

    public function reissue(Request $request, User $child, MinorAccountHandoffService $handoffs): View
    {
        $guardian = $request->user();
        $relationship = ParentChildAccount::query()
            ->where('parent_user_id', $guardian->id)
            ->where('child_user_id', $child->id)
            ->firstOrFail();

        try {
            [$handoff, $code] = $handoffs->issueActivation($relationship);
        } catch (Throwable) {
            throw ValidationException::withMessages(['activation' => 'Activation is not currently available for this dependent.']);
        }

        try {
            $guardian->notify(new TeenagerActivationReadyNotification($child->fresh(), $handoff, $code));
        } catch (Throwable $exception) {
            report($exception);
        }

        return view('guardian.teenager-activation-code', compact('child', 'handoff', 'code'));
    }
}
