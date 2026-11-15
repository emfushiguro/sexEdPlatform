<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\MinorAccountHandoffService;
use App\Support\AccountEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, MinorAccountHandoffService $minorHandoffs): RedirectResponse
    {
        $login = strtolower(trim((string) $request->input('login')));
        $request->merge(['login' => $login]);

        $request->validate([
            'login' => ['required', 'string', 'max:255'],
        ]);

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            try {
                Password::sendResetLink(['email' => AccountEmail::normalize($login)]);
            } catch (\Throwable $exception) {
                Log::warning('Password reset email dispatch failed.', [
                    'exception' => $exception::class,
                ]);
            }
        } else {
            $teenager = User::query()
                ->whereHas('learnerProfile', fn (Builder $query) => $query->whereRaw('LOWER(username) = ?', [$login]))
                ->first();

            $minorHandoffs->requestPasswordReset($teenager);
        }

        return back()
            ->withInput($request->only('login'))
            ->with('status', 'If an eligible account exists, recovery instructions will be sent to the appropriate email address or guardian.');
    }
}
