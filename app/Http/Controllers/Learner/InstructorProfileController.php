<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Instructor\PublicInstructorProfileService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorProfileController extends Controller
{
    public function show(Request $request, User $instructor, PublicInstructorProfileService $profiles): View
    {
        abort_unless($instructor->can('access instructor panel'), 404);

        return view('learner.instructors.show', $profiles->data($instructor));
    }
}
