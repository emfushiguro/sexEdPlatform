<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreModuleRequest;
use App\Http\Requests\Instructor\UpdateModuleRequest;
use App\Models\Module;
use App\Services\EntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ModuleController extends Controller
{
    public function __construct(private readonly EntitlementService $entitlementService)
    {
    }

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

    public function store(StoreModuleRequest $request)
    {
        $validated = $request->validated();

        $validated['access_type'] = $validated['access_type'] ?? 'free';

        $this->guardPaidAccess($validated['access_type'] ?? 'free');

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

        if ($request->has('is_published')) {
            $validated['is_published'] = $request->boolean('is_published');
        } elseif ($request->filled('action')) {
            $validated['is_published'] = $request->input('action') === 'publish';
        } else {
            $validated['is_published'] = true;
        }

        $validated['price_currency'] = strtoupper($validated['price_currency'] ?? 'PHP');
        if (($validated['access_type'] ?? 'free') === 'paid') {
            $validated['is_premium'] = true;
        } else {
            $validated['is_premium'] = false;
            $validated['price_amount'] = null;
        }
        
        // Duration will be auto-calculated from lessons
        $validated['duration_minutes'] = 0;

        $validated['order'] = $validated['order'] ?? Module::max('order') + 1;
        $validated['created_by'] = Auth::id();
        $validated['content_owner_type'] = 'instructor';
        $validated['current_review_status'] = 'draft';

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

    public function update(UpdateModuleRequest $request, Module $module)
    {
        $validated = $request->validated();

        $validated['access_type'] = $validated['access_type'] ?? ($module->access_type ?? 'free');

        $this->guardPaidAccess($validated['access_type'] ?? 'free');

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

        if ($request->has('is_published')) {
            $validated['is_published'] = $request->boolean('is_published');
        } elseif ($request->filled('action')) {
            $validated['is_published'] = $request->input('action') === 'publish';
        } else {
            unset($validated['is_published']);
        }
        $validated['content_owner_type'] = $module->content_owner_type ?? 'instructor';

        $validated['price_currency'] = strtoupper($validated['price_currency'] ?? 'PHP');
        if (($validated['access_type'] ?? 'free') === 'paid') {
            $validated['is_premium'] = true;
        } else {
            $validated['is_premium'] = false;
            $validated['price_amount'] = null;
        }
        
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

    private function guardPaidAccess(string $accessType): void
    {
        if ($accessType !== 'paid') {
            return;
        }

        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return;
        }

        if (! $this->entitlementService->canAccessFeature($user, 'instructor_paid_modules')) {
            throw ValidationException::withMessages([
                'access_type' => 'Paid modules require an active entitlement.',
            ]);
        }
    }
}
