<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;
use App\Services\Instructor\InstructorPlanCapabilityService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view modules');
    }

    public function view(User $user, Module $module): bool
    {
        return $user->can('view modules')
            && ($this->ownsModule($user, $module)
                || $user->can('review modules')
                || $user->can('publish modules'));
    }

    public function create(User $user): bool
    {
        return $user->can('create modules');
    }

    public function createWithinPlanLimit(User $user): Response
    {
        if ($user->hasRole('admin') || !$user->isInstructor()) {
            return Response::allow();
        }

        /** @var InstructorPlanCapabilityService $capabilityService */
        $capabilityService = app(InstructorPlanCapabilityService::class);

        if ($capabilityService->canCreateModule($user)) {
            return Response::allow();
        }

        return Response::deny($capabilityService->reachedModuleLimitMessage($user));
    }

    public function update(User $user, Module $module): bool
    {
        return $user->can('edit modules')
            && ($this->ownsModule($user, $module)
                || $user->can('review modules')
                || $user->can('publish modules'));
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->can('delete modules')
            && ($this->ownsModule($user, $module)
                || $user->can('review modules')
                || $user->can('publish modules'));
    }

    public function submit(User $user, Module $module): bool
    {
        return $user->can('submit modules') && $this->ownsModule($user, $module);
    }

    public function resubmit(User $user, Module $module): bool
    {
        return $user->can('resubmit modules') && $this->ownsModule($user, $module);
    }

    public function withdraw(User $user, Module $module): bool
    {
        return $user->can('withdraw module submissions') && $this->ownsModule($user, $module);
    }

    public function publish(User $user, Module $module): bool
    {
        return $user->can('publish modules');
    }

    public function publishPaid(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (!$user->isInstructor()) {
            return false;
        }

        /** @var InstructorPlanCapabilityService $capabilityService */
        $capabilityService = app(InstructorPlanCapabilityService::class);

        if (!$capabilityService->isStrictRolloutMode()) {
            return true;
        }

        return $capabilityService->canPublishPaidModules($user)
            && $capabilityService->canReceivePaidEnrollments($user);
    }

    public function viewEarnings(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (!$user->isInstructor()) {
            return false;
        }

        /** @var InstructorPlanCapabilityService $capabilityService */
        $capabilityService = app(InstructorPlanCapabilityService::class);

        if (!$capabilityService->isStrictRolloutMode()) {
            return true;
        }

        return $capabilityService->canViewEarnings($user);
    }

    public function review(User $user, Module $module): bool
    {
        return $user->can('review modules');
    }

    private function ownsModule(User $user, Module $module): bool
    {
        return (int) $module->created_by === (int) $user->id;
    }
}
