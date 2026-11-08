<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureDirectInstructorApplicationsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('instructor_application.enabled'), 404);

        return $next($request);
    }
}
