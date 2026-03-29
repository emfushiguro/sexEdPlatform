<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateInstructorProfileRequest;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();
        $user->loadMissing(['learnerProfile', 'profile']);

        $profile = InstructorProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['bio' => '']
        );

        $moduleIds = Module::query()
            ->where('created_by', $user->id)
            ->pluck('id');

        $overview = [
            'modules_created' => $moduleIds->count(),
            'total_learners_enrolled' => ModuleEnrollment::query()
                ->whereIn('module_id', $moduleIds)
                ->where('status', EnrollmentStatus::Approved)
                ->distinct('user_id')
                ->count('user_id'),
            'total_quizzes_created' => Quiz::query()
                ->whereIn('module_id', $moduleIds)
                ->orWhereHas('lesson', fn ($query) => $query->whereIn('module_id', $moduleIds))
                ->count(),
            'average_rating' => 'Not yet available',
        ];

        return view('instructor.profile.show', [
            'user' => $user,
            'learnerProfile' => $user->learnerProfile,
            'profile' => $profile,
            'overview' => $overview,
        ]);
    }

    public function edit(): View
    {
        $user = Auth::user();
        $profile = InstructorProfile::firstOrCreate(['user_id' => $user->id], ['bio' => '']);

        $this->authorize('update', $profile);

        return view('instructor.profile.edit', [
            'profile' => $profile,
            'user' => $user,
        ]);
    }

    public function update(UpdateInstructorProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = InstructorProfile::firstOrCreate(['user_id' => $user->id], ['bio' => '']);

        $this->authorize('update', $profile);

        $profile->update($request->validated());

        return redirect()->route('instructor.profile.show')
            ->with('success', 'Instructor profile updated successfully.');
    }
}
