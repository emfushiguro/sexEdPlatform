<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdultRecoveryPasswordChanged;
use App\Services\Auth\AdultRecoveryCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AdultRecoveryCodeController extends Controller
{
    public function settings(Request $request, AdultRecoveryCodeService $codes)
    {
        abort_unless($codes->eligible($request->user()), 403);
        return response()->view('auth.adult-recovery-settings')->header('Cache-Control', 'no-store');
    }

    public function generate(Request $request, AdultRecoveryCodeService $codes)
    {
        abort_unless($codes->eligible($request->user()), 403);
        $request->validate(['password' => ['required', 'current_password']]);
        return response()->view('auth.adult-recovery-settings', ['recoveryCodes' => $codes->generate($request->user())])->header('Cache-Control', 'no-store');
    }

    public function create()
    {
        return response()->view('auth.adult-recovery-reset')->header('Cache-Control', 'no-store');
    }

    public function reset(Request $request, AdultRecoveryCodeService $codes)
    {
        $data = $request->validate(['username' => ['required','string','max:30'], 'code' => ['required','string','max:40'], 'password' => ['required','confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()]]);
        $user = User::query()->whereHas('learnerProfile', fn ($q) => $q->whereRaw('LOWER(username) = ?', [strtolower(trim($data['username']))]))->first();
        try {
            if (! $user || $user->status !== User::STATUS_ACTIVE) {
                throw new \InvalidArgumentException();
            }
            $codes->reset($user, $data['code'], $data['password']);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['code' => 'The username or recovery code is invalid or unavailable.']);
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['code' => 'Recovery is temporarily unavailable. Please try again later.']);
        }
        try { $user->notify(new AdultRecoveryPasswordChanged()); } catch (Throwable $exception) { report($exception); }
        return redirect()->route('login')->with('status', 'Your password was changed. Sign in with your new password.');
    }
}
