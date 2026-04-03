<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorProfileController extends Controller
{
    public function show(Request $request, User $instructor): View
    {
        abort_unless($instructor->role === 'instructor', 404);

        $instructor->loadMissing(['instructorProfile']);

        return view('learner.instructors.show', [
            'instructor' => $instructor,
            'profile' => $instructor->instructorProfile,
        ]);
    }
}
