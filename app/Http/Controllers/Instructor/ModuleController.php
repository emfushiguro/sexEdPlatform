<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreModuleRequest;
use App\Http\Requests\Instructor\UpdateModuleRequest;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Monetization\CommissionPolicyResolver;
use App\Support\InstructorRestrictionGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ModuleController extends Controller
{
    public function __construct(
        private readonly InstructorRestrictionGate $instructorRestrictionGate,
        private readonly CommissionPolicyResolver $commissionPolicyResolver,
    ) {
    }

    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $user = Auth::user();

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

        // Include legacy lesson-attached quizzes that were stored with a null module_id.
        $moduleIds = $modules->getCollection()->pluck('id');

        if ($moduleIds->isNotEmpty()) {
            $directQuizCounts = Quiz::query()
                ->whereIn('module_id', $moduleIds)
                ->selectRaw('module_id, COUNT(*) as total')
                ->groupBy('module_id')
                ->pluck('total', 'module_id');

            $lessonQuizCounts = Quiz::query()
                ->join('lessons', 'lessons.id', '=', 'quizzes.lesson_id')
                ->whereNull('quizzes.module_id')
                ->whereIn('lessons.module_id', $moduleIds)
                ->selectRaw('lessons.module_id as module_id, COUNT(*) as total')
                ->groupBy('lessons.module_id')
                ->pluck('total', 'module_id');

            $modules->setCollection(
                $modules->getCollection()->map(function (Module $module) use ($directQuizCounts, $lessonQuizCounts) {
                    $module->quizzes_count = (int) ($directQuizCounts[$module->id] ?? 0)
                        + (int) ($lessonQuizCounts[$module->id] ?? 0);

                    return $module;
                })
            );
        }

        $pendingCount = \App\Models\ModuleEnrollment::where('status', 'pending')
            ->whereHas('module', fn ($q) => $q->where('created_by', Auth::id()))
            ->count();

        $restrictionProfile = $user ? $this->instructorRestrictionGate->activeRestrictionProfile($user) : null;
        $isRestricted = $restrictionProfile !== null;
        $restrictionMessage = $isRestricted
            ? $this->instructorRestrictionGate->restrictionMessage($user)
            : null;

        $effectiveCommissionPolicy = $this->resolveEffectiveCommissionPolicyPayload($user);

        return view('instructor.modules.index', compact(
            'modules',
            'pendingCount',
            'status',
            'isRestricted',
            'restrictionProfile',
            'restrictionMessage',
            'effectiveCommissionPolicy',
        ));
    }

    public function create()
    {
        $user = Auth::user();
        if ($user && $this->instructorRestrictionGate->isRestricted($user)) {
            return redirect()->route('instructor.modules.index')
                ->with('error', $this->instructorRestrictionGate->restrictionMessage($user));
        }

        return view('instructor.modules.create', [
            'isRestricted' => false,
            'restrictionProfile' => null,
            'restrictionMessage' => null,
            'effectiveCommissionPolicy' => $this->resolveEffectiveCommissionPolicyPayload($user),
        ]);
    }

    public function store(StoreModuleRequest $request)
    {
        if ($this->instructorRestrictionGate->isRestricted($request->user())) {
            return redirect()->route('instructor.modules.index')
                ->with('error', $this->instructorRestrictionGate->restrictionMessage($request->user()));
        }

        $validated = $request->validated();

        $validated['access_type'] = $validated['access_type'] ?? 'free';

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

        $validated['is_published'] = false;

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

        $message = 'Module saved as draft. Submit it for admin review when it is ready.';

        return redirect()->route('instructor.modules.show', $module)
            ->with('success', $message);
    }

    public function show(Module $module)
    {
        abort_unless((int) $module->created_by === (int) Auth::id(), 403);

        $module->load([
            'lessons' => fn ($q) => $q->orderBy('order'),
            'quizzes',
            'reviewRequests' => fn ($query) => $query->latest(),
            'enrollments' => fn ($query) => $query
                ->latest()
                ->with('user:id,name,first_name,last_name,email'),
        ]);

        $pendingEnrollmentsCount = $module->enrollments->where('status', 'pending')->count();

        return view('instructor.modules.show', compact('module', 'pendingEnrollmentsCount'));
    }

    public function edit(Module $module)
    {
        $user = Auth::user();
        $restrictionProfile = $user ? $this->instructorRestrictionGate->activeRestrictionProfile($user) : null;

        return view('instructor.modules.edit', [
            'module' => $module,
            'isRestricted' => $restrictionProfile !== null,
            'restrictionProfile' => $restrictionProfile,
            'restrictionMessage' => $restrictionProfile ? $this->instructorRestrictionGate->restrictionMessage($user) : null,
            'effectiveCommissionPolicy' => $this->resolveEffectiveCommissionPolicyPayload($user),
        ]);
    }

    private function resolveEffectiveCommissionPolicyPayload(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        try {
            $policy = $this->commissionPolicyResolver->resolveForInstructor((int) $user->id);

            return [
                'commission_percent' => (float) $policy->commission_percent,
                'tax_basis' => (string) $policy->tax_basis,
                'refund_policy' => (string) $policy->refund_policy,
            ];
        } catch (RuntimeException) {
            return null;
        }
    }

    public function update(UpdateModuleRequest $request, Module $module)
    {
        $validated = $request->validated();

        $validated['access_type'] = $validated['access_type'] ?? ($module->access_type ?? 'free');

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

        $validated['is_published'] = false;
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
        $module->update([
            'is_published' => false,
            'current_review_status' => $module->current_review_status ?? 'draft',
        ]);

        return back()->with('info', 'Instructor modules now require admin approval before publication.');
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
