<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class QuizManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::with(['module', 'lesson']);

        if ($request->filled('module_id')) {
            $query->where('module_id', $request->module_id);
        }

        $quizzes = $query->latest()->paginate(15);
        $modules = Module::all();

        return view('admin.quizzes.index', compact('quizzes', 'modules'));
    }

    public function create(Request $request)
    {
        $modules = Module::with('lessons')->get();
        $lessonId = $request->query('lesson_id');
        
        return view('admin.quizzes.create', compact('modules', 'lessonId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
        ]);

        // Ensure at least one of module_id or lesson_id is provided
        if (!$validated['module_id'] && !$validated['lesson_id']) {
            return back()->withErrors(['module_id' => 'Please select either a module or a lesson.']);
        }

        $validated['slug'] = Str::slug($validated['title']);

        Quiz::create($validated);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz created successfully! Now add questions to it.');
    }

    public function show(Quiz $quiz)
    {
        $quiz->load(['questions.options', 'module', 'lesson']);
        return view('admin.quizzes.show', compact('quiz'));
    }

    public function edit(Quiz $quiz)
    {
        $modules = Module::with('lessons')->get();
        return view('admin.quizzes.edit', compact('quiz', 'modules'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'passing_score' => 'required|integer|min:0|max:100',
            'time_limit' => 'nullable|integer|min:1',
        ]);

        if (!$validated['module_id'] && !$validated['lesson_id']) {
            return back()->withErrors(['module_id' => 'Please select either a module or a lesson.']);
        }

        $validated['slug'] = Str::slug($validated['title']);

        $quiz->update($validated);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz updated successfully!');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz deleted successfully!');
    }

    // Question management
    public function addQuestion(Quiz $quiz)
    {
        return view('admin.quizzes.add-question', compact('quiz'));
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,multiple_select',
            'points' => 'required|integer|min:1',
            'options' => 'required_if:question_type,multiple_choice,true_false,multiple_select|array|min:2',
            'options.*' => 'required|string',
            'correct_options' => 'required_if:question_type,multiple_choice,true_false,multiple_select|array|min:1',
            'correct_options.*' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            $question = $quiz->questions()->create([
                'question_text' => $validated['question_text'],
                'question_type' => $validated['question_type'],
                'points' => $validated['points'],
                'order' => $quiz->questions()->max('order') + 1,
            ]);

            // Add options
            if (isset($validated['options'])) {
                foreach ($validated['options'] as $index => $optionText) {
                    $question->options()->create([
                        'option_text' => $optionText,
                        'is_correct' => in_array($index, $validated['correct_options']),
                        'order' => $index,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.quizzes.show', $quiz)
                ->with('success', 'Question added successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to add question: ' . $e->getMessage());
        }
    }
}
