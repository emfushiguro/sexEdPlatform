<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\ContentGovernanceService;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    public function __construct(
        private readonly ContentGovernanceService $contentGovernanceService,
    ) {
    }

    public function index()
    {
        $modules = Module::query()
            ->where('content_owner_type', 'admin')
            ->latest()
            ->paginate(12);

        return view('admin.modules.index', compact('modules'));
    }

    public function create()
    {
        return view('admin.modules.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateModule($request);

        $module = $this->contentGovernanceService->createAdminOwnedModule(
            $this->mapModulePayload($validated),
            $request->user(),
        );

        return redirect()->route('admin.modules.show', $module)
            ->with('success', 'Admin-owned module created and published.');
    }

    public function show(Module $module)
    {
        abort_unless($module->content_owner_type === 'admin', 404);

        return view('admin.modules.show', compact('module'));
    }

    public function edit(Module $module)
    {
        abort_unless($module->content_owner_type === 'admin', 404);

        return view('admin.modules.edit', compact('module'));
    }

    public function update(Request $request, Module $module)
    {
        abort_unless($module->content_owner_type === 'admin', 404);

        $validated = $this->validateModule($request);

        $module->update($this->mapModulePayload($validated) + [
            'current_review_status' => 'approved',
            'is_published' => true,
            'published_by_admin_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.modules.show', $module)
            ->with('success', 'Admin-owned module updated.');
    }

    private function validateModule(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'age_bracket' => 'required|in:kids,teens,adults',
            'enrollment_mode' => 'required|in:auto,manual',
            'is_published' => 'nullable|boolean',
        ]);
    }

    private function mapModulePayload(array $validated): array
    {
        $ageBrackets = [
            'kids' => ['min_age' => 5, 'max_age' => 12],
            'teens' => ['min_age' => 13, 'max_age' => 17],
            'adults' => ['min_age' => 18, 'max_age' => 100],
        ];

        return [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'min_age' => $ageBrackets[$validated['age_bracket']]['min_age'],
            'max_age' => $ageBrackets[$validated['age_bracket']]['max_age'],
            'enrollment_mode' => $validated['enrollment_mode'],
            'duration_minutes' => 0,
            'order' => (Module::max('order') ?? 0) + 1,
            'is_premium' => false,
            'certificate_pass_score' => 70,
        ];
    }
}
