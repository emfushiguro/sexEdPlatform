<?php

namespace App\Http\Middleware;

use App\Models\UserSuspension;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSuspensionStatus
{
    /**
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($request->routeIs('moderation.suspension-status')
            || $request->routeIs('moderation.appeals.*')
            || $request->routeIs('logout')) {
            return $next($request);
        }

        $activeSuspension = UserSuspension::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('starts_at')
            ->first();

        if (!$activeSuspension) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Account access is restricted due to an active suspension.',
                'suspension_status_route' => route('moderation.suspension-status'),
            ], 423);
        }

        return redirect()->route('moderation.suspension-status');
    }
}
