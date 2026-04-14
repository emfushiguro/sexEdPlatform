<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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

    public function review(User $user, Module $module): bool
    {
        return $user->can('review modules');
    }

    private function ownsModule(User $user, Module $module): bool
    {
        return (int) $module->created_by === (int) $user->id;
    }
}
