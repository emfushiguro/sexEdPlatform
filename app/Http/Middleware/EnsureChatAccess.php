<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChatAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->canAccessChat()
            && User::query()->whereKey($user->id)->where('status', User::STATUS_ACTIVE)->exists(), 403);

        return $next($request);
    }
}
