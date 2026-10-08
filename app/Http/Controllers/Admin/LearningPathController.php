<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLearningPathRequest;
use App\Models\LearningPath;
use App\Models\Module;
use App\Services\LearningPathAuthoringService;
use App\Services\LearningPathPresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LearningPath::class);

        return view('admin.learning-paths.index', [
            'paths' => LearningPath::query()->with(['learnerCategories', 'creator'])
                ->withCount('pathModules')->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LearningPath::class);

        return view('admin.learning-paths.create', $this->formData());
    }

    public function store(SaveLearningPathRequest $request, LearningPathAuthoringService $authoring): RedirectResponse
    {
        $attributes = $request->validated();
        if ($request->hasFile('thumbnail')) {
            $attributes['thumbnail'] = $request->file('thumbnail')->store('learning-paths', 'public');
        } else {
            unset($attributes['thumbnail']);
        }
        $authoring->save(null, $attributes, $request->user());

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path created.');
    }

    public function edit(LearningPath $learningPath): View
    {
        $this->authorize('update', $learningPath);

        return view('admin.learning-paths.edit', $this->formData($learningPath));
    }

    public function update(SaveLearningPathRequest $request, LearningPath $learningPath, LearningPathAuthoringService $authoring): RedirectResponse
    {
        $attributes = $request->validated();
        if ($request->hasFile('thumbnail')) {
            $attributes['thumbnail'] = $request->file('thumbnail')->store('learning-paths', 'public');
        } else {
            unset($attributes['thumbnail']);
        }
        $authoring->save($learningPath, $attributes, $request->user());

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path updated.');
    }

    public function archive(LearningPath $learningPath): RedirectResponse
    {
        $this->authorize('archive', $learningPath);
        $learningPath->update(['status' => LearningPath::STATUS_ARCHIVED]);

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path archived.');
    }

    public function restore(LearningPath $learningPath): RedirectResponse
    {
        $this->authorize('archive', $learningPath);
        $learningPath->update(['status' => LearningPath::STATUS_DRAFT]);

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path restored as a draft.');
    }

    public function preview(LearningPath $learningPath, LearningPathPresentationService $presentation): View
    {
        $this->authorize('view', $learningPath);
        $learningPath->load(['learnerCategories', 'pathModules.module']);

        return view('admin.learning-paths.preview', [
            'path' => $presentation->preview($learningPath),
        ]);
    }

    private function formData(?LearningPath $path = null): array
    {
        $path?->load(['learnerCategories', 'pathModules.module.learnerCategories', 'pathModules.module.creator']);
        $candidates = Module::query()->learnerVisible()->with(['learnerCategories', 'creator'])
            ->orderBy('title')->get([
                'id', 'title', 'thumbnail', 'created_by', 'min_age', 'max_age', 'content_owner_type',
                'is_published', 'published_revision_id', 'current_review_status',
            ]);
        $modulePool = $candidates->keyBy('id');
        $path?->pathModules->each(function ($membership) use ($modulePool): void {
            if ($membership->module) {
                $modulePool->put($membership->module->id, $membership->module);
            }
        });
        $ids = old('module_ids', $path?->pathModules->pluck('module_id')->all() ?? []);
        $ids = is_array($ids) ? $ids : [];
        $selectedModules = collect($ids)->map(fn ($id) => $modulePool->get((int) $id))
            ->filter()->unique('id')->values();

        return compact('path', 'candidates', 'selectedModules');
    }
}
