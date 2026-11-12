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
        $handoff = $handoffs->findUsable($code, MinorAccountHandoff::PURPOSE_ACTIVATION);
        if ($handoff === null) {
            throw ValidationException::withMessages(['code' => 'This activation code is invalid or expired.']);
        }

        return view('auth.teenager-activation', [
            'code' => $code,
            'child' => $handoff->child,
            'profile' => $handoff->child?->learnerProfile,
        ]);
    }

    public function store(ActivateMinorAccountRequest $request, MinorAccountHandoffService $handoffs): RedirectResponse
    {
        try {
            $child = $handoffs->consumeActivation(
                (string) $request->validated('code'),
                (string) $request->validated('username'),
                (string) $request->validated('password'),
            );
        } catch (Throwable) {
            throw ValidationException::withMessages(['code' => 'This activation code is invalid or expired.']);
        }

        $request->session()->regenerate();
        Auth::login($child);

        if ($child->email !== null) {
            event(new Registered($child));
        }

        return redirect()->route('learner.identity.create');
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
            throw ValidationException::withMessages(['activation' => 'Activation is not currently available for this Teenager.']);
        }

        $guardian->notify(new TeenagerActivationReadyNotification($child->fresh(), $handoff, $code));

        return view('guardian.teenager-activation-code', compact('child', 'handoff', 'code'));
    }
}
