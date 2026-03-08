<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::withCount(['lessons', 'quizzes'])->latest()->paginate(15);
        return view('instructor.modules.index', compact('modules'));
    }

    public function create()
    {
        return view('instructor.modules.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'age_bracket' => 'required|in:kids,teens,adults',
            'enrollment_mode' => 'required|in:auto,manual',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('modules', 'public');
        }

        // Set age range based on bracket
        $ageBrackets = [
            'kids' => ['min_age' => 5, 'max_age' => 12],
            'teens' => ['min_age' => 13, 'max_age' => 17],
            'adults' => ['min_age' => 18, 'max_age' => 100],
        ];
        
        $validated['min_age'] = $ageBrackets[$validated['age_bracket']]['min_age'];
        $validated['max_age'] = $ageBrackets[$validated['age_bracket']]['max_age'];
        unset($validated['age_bracket']);

        // Set publishing status based on button clicked
        $validated['is_published'] = $request->input('action') === 'publish';
        
        // Duration will be auto-calculated from lessons
        $validated['duration_minutes'] = 0;

        $validated['order'] = $validated['order'] ?? Module::max('order') + 1;
        $validated['created_by'] = Auth::id();

        Module::create($validated);

        $message = $validated['is_published'] 
            ? 'Module created and published successfully!' 
            : 'Module saved as draft successfully!';

        return redirect()->route('instructor.modules.index')
            ->with('success', $message);
    }

    public function show(Module $module)
    {
        $module->load(['lessons', 'quizzes']);
        return view('instructor.modules.show', compact('module'));
    }

    public function edit(Module $module)
    {
        return view('instructor.modules.edit', compact('module'));
    }

    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'age_bracket' => 'required|in:kids,teens,adults',
            'enrollment_mode' => 'required|in:auto,manual',
            'order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('modules', 'public');
        }

        // Set age range based on bracket
        $ageBrackets = [
            'kids' => ['min_age' => 5, 'max_age' => 12],
            'teens' => ['min_age' => 13, 'max_age' => 17],
            'adults' => ['min_age' => 18, 'max_age' => 100],
        ];
        
        $validated['min_age'] = $ageBrackets[$validated['age_bracket']]['min_age'];
        $validated['max_age'] = $ageBrackets[$validated['age_bracket']]['max_age'];
        unset($validated['age_bracket']);

        // Set publishing status
        $validated['is_published'] = $request->has('is_published');
        
        // Duration is auto-calculated, but update it now
        $module->duration_minutes = $module->lessons()->sum('duration');
        $module->save();

        $module->update($validated);

        return redirect()->route('instructor.modules.index')
            ->with('success', 'Module updated successfully!');
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return redirect()->route('instructor.modules.index')
            ->with('success', 'Module deleted successfully!');
    }
}
