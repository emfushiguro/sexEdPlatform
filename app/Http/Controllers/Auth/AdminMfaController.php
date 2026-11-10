<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyAdminMfaRequest;
use App\Services\Auth\AdminMfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminMfaController extends Controller
{
    public function show(Request $request, AdminMfaService $mfa): View|RedirectResponse
    {
        $challenge = $mfa->currentChallenge($request);
        if ($challenge === null) {
            return redirect()->route('admin.login');
        }

        return view('auth.admin-mfa', [
            'maskedEmail' => $mfa->maskEmail((string) $challenge->user->email),
            'codeExpiresAt' => $challenge->code_expires_at->getTimestamp(),
            'resendAvailableAt' => $challenge->resend_available_at->getTimestamp(),
            'deliveryFailed' => $challenge->delivery_failed_at !== null,
        ]);
    }

    public function verify(
        VerifyAdminMfaRequest $request,
        AdminMfaService $mfa,
    ): RedirectResponse {
        if ($mfa->currentChallenge($request) === null) {
            return redirect()->route('admin.login');
        }

        try {
            $user = $mfa->verify($request, (string) $request->validated('code'));
        } catch (ValidationException $exception) {
            if (! $request->session()->has(AdminMfaService::PENDING_CHALLENGE_KEY)) {
                return redirect()->route('admin.login')->withErrors($exception->errors());
            }

            throw $exception;
        }

        $mfa->authenticate($request, $user);

        return redirect()->to($this->intendedAdminUrl($request))
            ->with('success', 'Two-step verification complete.');
    }

    public function resend(Request $request, AdminMfaService $mfa): RedirectResponse
    {
        if ($mfa->currentChallenge($request) === null) {
            return redirect()->route('admin.login');
        }

        try {
            $challenge = $mfa->resend($request);
        } catch (ValidationException $exception) {
            if (! $request->session()->has(AdminMfaService::PENDING_CHALLENGE_KEY)) {
                return redirect()->route('admin.login')->withErrors($exception->errors());
            }

            throw $exception;
        }

        return back()->with(
            $challenge->delivery_failed_at === null ? 'status' : 'warning',
            $challenge->delivery_failed_at === null
                ? 'A new verification code was sent.'
                : 'We could not send the verification code. Try again when resend is available.',
        );
    }

    public function cancel(Request $request, AdminMfaService $mfa): RedirectResponse
    {
        $mfa->cancel($request);

        return redirect()->route('admin.login');
    }

    private function intendedAdminUrl(Request $request): string
    {
        $intended = (string) $request->session()->pull('url.intended', '');
        $adminRoot = url('/admin');

        if ($intended === $adminRoot || str_starts_with($intended, $adminRoot.'/')) {
            return $intended;
        }

        if ($intended === '/admin' || str_starts_with($intended, '/admin/')) {
            return url($intended);
        }

        return route('admin.dashboard');
    }
}
