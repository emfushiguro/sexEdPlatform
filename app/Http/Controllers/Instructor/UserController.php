<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of learners enrolled in instructor-owned modules.
     */
    public function index(Request $request)
    {
        $instructorId = Auth::id();

        $users = User::role('learner')
            ->whereHas('moduleEnrollments.module', function ($query) use ($instructorId) {
                $query->where('created_by', $instructorId);
            })
            ->with([
                'moduleEnrollments' => function ($query) use ($instructorId) {
                    $query->whereHas('module', function ($moduleQuery) use ($instructorId) {
                        $moduleQuery->where('created_by', $instructorId);
                    })->with('module:id,title,created_by');
                },
                'activityLogs' => fn ($query) => $query->latest('created_at')->limit(1),
            ])
            ->withCount([
                'moduleEnrollments as instructor_modules_enrolled_count' => function ($query) use ($instructorId) {
                    $query->whereHas('module', function ($moduleQuery) use ($instructorId) {
                        $moduleQuery->where('created_by', $instructorId);
                    });
                },
            ])
            ->latest()
            ->get();

        return view('instructor.users.index', compact('users'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $instructorId = Auth::id();
        $hasInstructorEnrollment = $user->moduleEnrollments()
            ->whereHas('module', fn ($query) => $query->where('created_by', $instructorId))
            ->exists();

        abort_unless($hasInstructorEnrollment, 403);

        $user->load([
            'subscription',
            'gamification',
            'moduleEnrollments' => fn ($query) => $query
                ->whereHas('module', fn ($moduleQuery) => $moduleQuery->where('created_by', $instructorId))
                ->with('module'),
            'quizAttempts',
            'certificates.module',
        ]);

        return view('instructor.users.show', compact('user'));
    }
}
