<?php

namespace App\Http\Middleware;

use App\Services\Auth\AdminMfaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMfaVerified
{
    public function __construct(private readonly AdminMfaService $mfa) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->mfa->sessionIsVerified($request)) {
            return $next($request);
        }

        $expectsJson = $request->expectsJson();
        $this->mfa->logout($request);

        if ($expectsJson) {
            return response()->json([
                'message' => 'Admin multi-factor authentication is required.',
            ], 401);
        }

        return redirect()->route('admin.login')->withErrors([
            'email' => 'Complete two-step verification to access the Admin panel.',
        ]);
    }
}
