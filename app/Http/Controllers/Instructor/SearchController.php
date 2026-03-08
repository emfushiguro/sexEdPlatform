<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = substr(strip_tags($request->input('q', '')), 0, 100);

        if (strlen($q) < 2) {
            return response()->json(['modules' => [], 'lessons' => [], 'learners' => []]);
        }

        $instructorId = Auth::id();

        $modules = Module::where('created_by', $instructorId)
            ->where('title', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'title'])
            ->map(fn($m) => ['id' => $m->id, 'title' => $m->title, 'url' => route('instructor.modules.edit', $m)]);

        $lessons = Lesson::whereHas('module', fn($mq) => $mq->where('created_by', $instructorId))
            ->where('title', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'title'])
            ->map(fn($l) => ['id' => $l->id, 'title' => $l->title, 'url' => route('instructor.lessons.edit', $l)]);

        $learners = User::role('learner')
            ->whereHas('moduleEnrollments.module', fn($mq) => $mq->where('created_by', $instructorId))
            ->where(fn($uq) => $uq->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"))
            ->limit(5)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn($u) => ['id' => $u->id, 'name' => trim($u->first_name . ' ' . $u->last_name), 'url' => route('instructor.users.index')]);

        return response()->json(compact('modules', 'lessons', 'learners'));
    }
}
