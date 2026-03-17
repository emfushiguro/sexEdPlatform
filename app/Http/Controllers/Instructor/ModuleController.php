<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');

        if ($status === 'archived') {
            $query = Module::onlyTrashed()->where('created_by', Auth::id());
        } else {
            $query = Module::where('created_by', Auth::id());
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            }
        }

        $modules = $query
            ->withCount([
                'lessons',
                'quizzes',
                'enrollments as enrolled_count' => fn ($q) => $q->where('status', 'approved'),
            ])
            ->latest()
            ->paginate(12);

        $pendingCount = \App\Models\ModuleEnrollment::where('status', 'pending')
            ->whereHas('module', fn ($q) => $q->where('created_by', Auth::id()))
            ->count();

        return view('instructor.modules.index', compact('modules', 'pendingCount', 'status'));
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

        $module = Module::create($validated);

        $message = $validated['is_published']
            ? 'Module created and published successfully!'
            : 'Module saved as draft. Add your first lesson below.';

        return redirect()->route('instructor.modules.show', $module)
            ->with('success', $message);
    }

    public function show(Module $module)
    {
        abort_unless((int) $module->created_by === (int) Auth::id(), 403);

        $module->load([
            'lessons' => fn ($q) => $q->orderBy('order'),
            'quizzes',
            'enrollments' => fn ($query) => $query
                ->latest()
                ->with('user:id,name,first_name,last_name,email'),
        ]);

        $pendingEnrollmentsCount = $module->enrollments->where('status', 'pending')->count();

        return view('instructor.modules.show', compact('module', 'pendingEnrollmentsCount'));
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

    public function activate(Module $module)
    {
        abort_unless((int) $module->created_by === (int) Auth::id(), 403);
        $module->update(['is_published' => true]);
        return back()->with('success', 'Module activated successfully.');
    }

    public function deactivate(Module $module)
    {
        abort_unless((int) $module->created_by === (int) Auth::id(), 403);
        $module->update(['is_published' => false]);
        return back()->with('success', 'Module deactivated successfully.');
    }

    public function restore($id)
    {
        $module = Module::withTrashed()->findOrFail($id);
        abort_unless((int) $module->created_by === (int) Auth::id(), 403);
        $module->restore();
        return back()->with('success', 'Module restored successfully.');
    }
}
