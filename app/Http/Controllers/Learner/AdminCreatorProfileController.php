<?php

namespace App\Http\Controllers\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCreatorProfileController extends Controller
{
    public function show(Request $request, User $admin): View
    {
        abort_unless($admin->hasRole('admin') || (string) $admin->role === 'admin', 404);

        $admin->loadMissing('adminCreatorProfile');
        $profile = $admin->adminCreatorProfile;

        $publishedModulesQuery = Module::query()
            ->where('created_by', $admin->id)
            ->where('is_published', true);

        $modulesPublished = (clone $publishedModulesQuery)->count();

        $latestUpdatedModule = (clone $publishedModulesQuery)
            ->latest('updated_at')
            ->first();

        $learnersReached = ModuleEnrollment::query()
            ->where('status', EnrollmentStatus::Approved)
            ->whereHas('module', static function ($query) use ($admin): void {
                $query->where('created_by', $admin->id);
            })
            ->distinct('user_id')
            ->count('user_id');

        return view('learner.admin-creators.show', [
            'adminUser' => $admin,
            'profile' => $profile,
            'modulesPublished' => $modulesPublished,
            'learnersReached' => $learnersReached,
            'latestUpdatedModule' => $latestUpdatedModule,
        ]);
    }
}
