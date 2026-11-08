<?php

namespace App\Support;

use App\Models\User;

final class AuthenticatedHomeRouteResolver
{
    public function routeName(User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin.dashboard';
        }

        if ($user->isInstructor()) {
            return 'instructor.dashboard';
        }

        if ($user->isInstructorApplicant()) {
            return 'instructor-application.dashboard';
        }

        return 'learner.dashboard';
    }
}
