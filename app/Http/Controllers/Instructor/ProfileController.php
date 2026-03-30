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

        $validated = $request->validated();
        
        if ($request->hasFile('profile_photo')) {
            if ($profile->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->profile_photo_path);
            }
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('avatars', 'public');
        }
        
        unset($validated['profile_photo']);

        $validated['expertise_tags'] = $validated['expertise_tags'] ?? [];
        $validated['certifications'] = $validated['certifications'] ?? [];
        $validated['credentials'] = $validated['credentials'] ?? [];

        $profile->update($validated);

        return redirect()->route('instructor.profile.show')
            ->with('success', 'Instructor profile updated successfully.');
    }
}
