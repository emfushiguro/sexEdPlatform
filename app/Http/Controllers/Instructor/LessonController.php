<?php

namespace App\Http\Controllers\Instructor;

use App\Helpers\VideoEmbedHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $moduleGroups = Module::where('created_by', auth()->id())
            ->with(['lessons' => fn($q) => $q->orderBy('order')])
            ->withCount('lessons')
            ->orderByDesc('created_at')
            ->get();

        return view('instructor.lessons.index', compact('moduleGroups'));
    }

    public function create()
    {
        $modules = Module::all();
        return view('instructor.lessons.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        // Auto-increment order
        $validated['order'] = Lesson::where('module_id', $validated['module_id'])->max('order') + 1;
        
        // Duration will be auto-calculated from topics (start with 0)
        $validated['duration'] = 0;
        
        // Default create flow to active unless explicitly set.
        $validated['is_published'] = $request->has('is_published')
            ? $request->boolean('is_published')
            : true;
        
        // Default content type (container lesson)
        $validated['content_type'] = 'text';

        $lesson = Lesson::create($validated);

        return redirect()->route('instructor.lessons.show', $lesson)

        
            ->with('success', 'Lesson created successfully! Now add topics to this lesson.');
    }

    public function show(Lesson $lesson)
    {
        $lesson->load(['module', 'topics' => fn($q) => $q->orderBy('order'), 'quizzes.questions']);

        $enrolledCount = $lesson->module
            ? $lesson->module->enrollments()->where('status', 'approved')->count()
            : 0;

        $completedCount = \App\Models\UserProgress::where('lesson_id', $lesson->id)
            ->where('completed', true)
            ->count();

        $completionRate = $enrolledCount > 0
            ? round(($completedCount / $enrolledCount) * 100)
            : 0;

        return view('instructor.lessons.show', compact('lesson', 'enrolledCount', 'completedCount', 'completionRate'));
    }

    public function edit(Lesson $lesson)
    {
        $modules = Module::all();
        return view('instructor.lessons.edit', compact('lesson', 'modules'));
    }

    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        if ($request->has('is_published')) {
            $validated['is_published'] = $request->boolean('is_published');
        } else {
            unset($validated['is_published']);
        }

        // Duration is auto-calculated from topics (recalculate it)
        $lesson->duration = $lesson->topics()->sum('duration');
        
        $lesson->update($validated);

        // Update module duration as well
        $module = $lesson->module;
        $module->duration_minutes = $module->lessons()->sum('duration');
        $module->save();

        return redirect()->route('instructor.lessons.show', $lesson)
            ->with('success', 'Lesson updated successfully!');
    }

    public function destroy(Lesson $lesson)
    {
        $moduleId = $lesson->module_id;
        $lesson->delete();

        return redirect()->route('instructor.modules.show', $moduleId)
            ->with('success', 'Lesson deleted successfully!');
    }

    public function move(Request $request, Lesson $lesson)
    {
        $direction = $request->input('direction');
        $currentOrder = $lesson->order;

        if ($direction === 'up' && $currentOrder > 1) {
            $swapLesson = Lesson::where('module_id', $lesson->module_id)
                ->where('order', $currentOrder - 1)
                ->first();

            if ($swapLesson) {
                $lesson->update(['order' => $currentOrder - 1]);
                $swapLesson->update(['order' => $currentOrder]);
            }
        } elseif ($direction === 'down') {
            $maxOrder = Lesson::where('module_id', $lesson->module_id)->max('order');

            if ($currentOrder < $maxOrder) {
                $swapLesson = Lesson::where('module_id', $lesson->module_id)
                    ->where('order', $currentOrder + 1)
                    ->first();

                if ($swapLesson) {
                    $lesson->update(['order' => $currentOrder + 1]);
                    $swapLesson->update(['order' => $currentOrder]);
                }
            }
        }

        return redirect()->back()->with('success', 'Lesson order updated!');
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:lessons,id']);

        foreach ($request->order as $index => $id) {
            Lesson::where('id', $id)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
