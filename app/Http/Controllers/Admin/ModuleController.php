<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::withCount(['lessons', 'quizzes'])->latest()->paginate(15);
        return view('admin.modules.index', compact('modules'));
    }

    public function create()
    {
        return view('admin.modules.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'age_bracket' => 'required|in:kids,teens,adults',
            'duration_minutes' => 'required|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'publish_type' => 'required|in:draft,publish_now,schedule',
            'publish_at' => 'nullable|required_if:publish_type,schedule|date|after:now',
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

        // Handle publishing status
        if ($validated['publish_type'] === 'publish_now') {
            $validated['is_published'] = true;
            $validated['publish_status'] = 'published';
            $validated['publish_at'] = now();
        } elseif ($validated['publish_type'] === 'schedule') {
            $validated['is_published'] = false;
            $validated['publish_status'] = 'scheduled';
        } else {
            $validated['is_published'] = false;
            $validated['publish_status'] = 'draft';
            $validated['publish_at'] = null;
        }
        unset($validated['publish_type']);

        $validated['order'] = $validated['order'] ?? Module::max('order') + 1;

        Module::create($validated);

        return redirect()->route('admin.modules.index')
            ->with('success', 'Module created successfully!');
    }

    public function show(Module $module)
    {
        $module->load(['lessons', 'quizzes']);
        return view('admin.modules.show', compact('module'));
    }

    public function edit(Module $module)
    {
        return view('admin.modules.edit', compact('module'));
    }

    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'age_bracket' => 'required|in:kids,teens,adults',
            'duration_minutes' => 'required|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'publish_type' => 'required|in:draft,publish_now,schedule',
            'publish_at' => 'nullable|required_if:publish_type,schedule|date|after:now',
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

        // Handle publishing status
        if ($validated['publish_type'] === 'publish_now') {
            $validated['is_published'] = true;
            $validated['publish_status'] = 'published';
            $validated['publish_at'] = now();
        } elseif ($validated['publish_type'] === 'schedule') {
            $validated['is_published'] = false;
            $validated['publish_status'] = 'scheduled';
        } else {
            $validated['is_published'] = false;
            $validated['publish_status'] = 'draft';
            $validated['publish_at'] = null;
        }
        unset($validated['publish_type']);

        $module->update($validated);

        return redirect()->route('admin.modules.index')
            ->with('success', 'Module updated successfully!');
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return redirect()->route('admin.modules.index')
            ->with('success', 'Module deleted successfully!');
    }
}
