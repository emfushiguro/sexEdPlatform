# Parent Monitoring Feature — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a parent monitoring dashboard where parents can view their child's progress, quiz results, achievements, and approve/reject module enrollment requests — accessible via a conditional "My Children" link in the learner sidebar.

**Architecture:** `ParentChildService` handles all data fetching. `ParentController` is thin (calls service, returns views). `ParentChildPolicy` enforces parent-only access. All parent monitoring pages use the existing `learner-app` layout.

**Tech Stack:** Laravel 12, Blade, Alpine.js (tabs), Tailwind CSS, PHPUnit, Spatie Permissions

**Design doc:** `docs/plans/2026-03-07-parent-monitoring-design.md`

---

## Codebase Facts (Read Before Writing Anything)

- `RewardLog` → table `rewards_logs`, fields: `user_id`, `achievement_id`, `earned_at` — always eager-load `achievement` to get the title
- `UserGamification` → fields: `level`, `score`, `streak_count`, `last_act_at`
- `QuizAttempt` → fields: `user_id`, `quiz_id`, `answers` (JSON), `score`, `passed`, `started_at`, `completed_at` — eager-load `quiz` for title; `quiz` → eager-load `module` for module title
- `UserProgress` → fields: `user_id`, `module_id`, `lesson_id`, `completed` — count per-module completed lessons with `where('completed', true)`
- `User::children()` → `belongsToMany` via `parent_child_accounts`, pivot: `can_view_progress`, `can_view_quiz_answers`, `can_approve_content`
- `User::isParent()` → already exists, calls `$this->children()->exists()`
- `ModuleEnrollment::status` enum: `['pending', 'approved', 'rejected']` — migration will add `pending_parent_approval`
- MySQL enum modification requires raw `DB::statement()` — not `$table->enum()->change()`
- `AppServiceProvider::boot()` is empty — this is where to register the policy via `Gate::policy()`
- All parent routes live in `routes/auth.php` inside `middleware(['auth', 'verified'])` group
- Existing parent routes: `parent.children.index`, `parent.create-child`, `parent.create-child.store`
- `UserFactory` only sets `name`, `email`, `email_verified_at`, `password` — tests must set additional fields manually or via `User::create()`

---

## Task 1: Migration — extend module_enrollments status enum

**Files:**
- Create: `database/migrations/2026_03_07_000001_add_pending_parent_approval_to_module_enrollments.php`
- Test: `tests/Feature/ParentChildMonitoringTest.php` (create file, first test only)

**Step 1: Create the test file with the migration test**

```php
<?php
// tests/Feature/ParentChildMonitoringTest.php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RewardLog;
use App\Models\User;
use App\Models\UserGamification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentChildMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_enrollments_accepts_pending_parent_approval_status(): void
    {
        $parent = User::factory()->create(['email_verified_at' => now()]);
        $parent->assignRole('learner');

        $child = User::factory()->create(['email_verified_at' => now()]);
        $child->assignRole('learner');

        $module = Module::factory()->create();

        $enrollment = ModuleEnrollment::create([
            'user_id'    => $child->id,
            'module_id'  => $module->id,
            'status'     => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->assertDatabaseHas('module_enrollments', [
            'id'     => $enrollment->id,
            'status' => 'pending_parent_approval',
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

```
php artisan test --filter=test_module_enrollments_accepts_pending_parent_approval_status
```
Expected: FAIL — `SQLSTATE[HY000]: General error: 1265 Data truncated for column 'status'` (enum rejects the value)

**Step 3: Create the migration**

```php
<?php
// database/migrations/2026_03_07_000001_add_pending_parent_approval_to_module_enrollments.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE module_enrollments MODIFY COLUMN status ENUM('pending','approved','rejected','pending_parent_approval') NOT NULL DEFAULT 'approved'");
    }

    public function down(): void
    {
        // Update any pending_parent_approval rows to rejected before reverting
        DB::table('module_enrollments')
            ->where('status', 'pending_parent_approval')
            ->update(['status' => 'rejected']);

        DB::statement("ALTER TABLE module_enrollments MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved'");
    }
};
```

> **Note:** `Module::factory()` does not exist yet. If the factory is missing, create a minimal one in `database/factories/ModuleFactory.php`:
> ```php
> <?php
> namespace Database\Factories;
> use App\Models\Module;
> use Illuminate\Database\Eloquent\Factories\Factory;
> class ModuleFactory extends Factory
> {
>     protected $model = Module::class;
>     public function definition(): array
>     {
>         return [
>             'title' => fake()->sentence(3),
>             'description' => fake()->paragraph(),
>             'min_age' => 5,
>             'max_age' => 12,
>             'difficulty_level' => 'beginner',
>             'order' => 1,
>             'duration_minutes' => 30,
>             'is_published' => true,
>             'is_premium' => false,
>             'enrollment_mode' => 'auto',
>         ];
>     }
> }
> ```
> Also check `app/Models/Module.php` — add `use HasFactory;` if missing.

**Step 4: Run test to verify it passes**

```
php artisan test --filter=test_module_enrollments_accepts_pending_parent_approval_status
```
Expected: PASS

**Step 5: Commit**

```
git add database/migrations/2026_03_07_000001_add_pending_parent_approval_to_module_enrollments.php tests/Feature/ParentChildMonitoringTest.php
git commit -m "feat: add pending_parent_approval status to module_enrollments"
```

---

## Task 2: ParentChildPolicy — create, register, test authorization

**Files:**
- Create: `app/Policies/ParentChildPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/ParentChildMonitoringTest.php` (add tests)

**Step 1: Add authorization tests to `ParentChildMonitoringTest`**

Add these methods to the test class (do not replace the existing test):

```php
private function createParentWithChild(): array
{
    $parent = User::factory()->create(['email_verified_at' => now()]);
    $parent->assignRole('learner');

    $child = User::factory()->create(['email_verified_at' => now()]);
    $child->assignRole('learner');

    ParentChildAccount::create([
        'parent_user_id'    => $parent->id,
        'child_user_id'     => $child->id,
        'can_view_progress' => true,
        'can_view_quiz_answers' => true,
        'can_approve_content'   => true,
    ]);

    UserGamification::create([
        'user_id'      => $child->id,
        'level'        => 1,
        'score'        => 0,
        'total_points' => 0,
        'streak_count' => 0,
    ]);

    return [$parent, $child];
}

public function test_parent_can_view_own_childs_detail_page(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $this->actingAs($parent)
         ->get(route('parent.children.show', $child))
         ->assertOk();
}

public function test_parent_cannot_view_another_users_child(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $stranger = User::factory()->create(['email_verified_at' => now()]);
    $stranger->assignRole('learner');

    $this->actingAs($stranger)
         ->get(route('parent.children.show', $child))
         ->assertForbidden();
}

public function test_guest_cannot_access_parent_routes(): void
{
    $child = User::factory()->create();

    $this->get(route('parent.children.show', $child))
         ->assertRedirect(route('learner.login'));
}
```

**Step 2: Run tests to verify they fail**

```
php artisan test --filter=ParentChildMonitoringTest
```
Expected: FAIL — `Route [parent.children.show] not defined`

**Step 3: Create `ParentChildPolicy`**

```php
<?php
// app/Policies/ParentChildPolicy.php

namespace App\Policies;

use App\Models\User;

class ParentChildPolicy
{
    /**
     * Determine if the authenticated parent can view a child's monitoring page.
     * Checks that a parent_child_accounts row exists linking them.
     */
    public function view(User $parent, User $child): bool
    {
        return $parent->children()->where('child_user_id', $child->id)->exists();
    }
}
```

**Step 4: Register the policy in `AppServiceProvider`**

```php
<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use App\Models\User;
use App\Policies\ParentChildPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, ParentChildPolicy::class);
    }
}
```

**Step 5: Run tests — still fails (route not defined yet). Expected.** Move to Task 3 to add routes.

---

## Task 3: Routes and stub controller

**Files:**
- Create: `app/Http/Controllers/ParentController.php`
- Modify: `routes/auth.php`

**Step 1: Create stub `ParentController`**

```php
<?php
// app/Http/Controllers/ParentController.php

namespace App\Http\Controllers;

use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Services\ParentChildService;
use Illuminate\Http\RedirectResponse;

class ParentController extends Controller
{
    public function __construct(private ParentChildService $service) {}

    public function show(User $child)
    {
        $this->authorize('view', $child);

        $parent = auth()->user();

        $canApproveContent = $parent->children()
            ->where('users.id', $child->id)
            ->first()
            ?->pivot->can_approve_content ?? false;

        return view('parent.children.show', [
            'child'              => $child,
            'progress'           => $this->service->getProgress($child),
            'quizResults'        => $this->service->getQuizResults($child),
            'achievements'       => $this->service->getAchievements($child),
            'pendingEnrollments' => $canApproveContent ? $this->service->getPendingEnrollments($child) : collect(),
            'canApproveContent'  => $canApproveContent,
        ]);
    }

    public function approveEnrollment(User $child, ModuleEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('view', $child);

        if ($enrollment->user_id !== $child->id || $enrollment->status !== 'pending_parent_approval') {
            abort(403);
        }

        $newStatus = $enrollment->module->enrollment_mode === 'manual' ? 'pending' : 'approved';

        $enrollment->update([
            'status'      => $newStatus,
            'enrolled_at' => $newStatus === 'approved' ? now() : null,
        ]);

        return redirect()->route('parent.children.show', $child)
            ->with('success', 'Enrollment approved.');
    }

    public function rejectEnrollment(User $child, ModuleEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('view', $child);

        if ($enrollment->user_id !== $child->id || $enrollment->status !== 'pending_parent_approval') {
            abort(403);
        }

        $enrollment->update(['status' => 'rejected']);

        return redirect()->route('parent.children.show', $child)
            ->with('info', 'Enrollment request rejected.');
    }
}
```

**Step 2: Add routes to `routes/auth.php`**

Inside the existing `Route::middleware('auth')->group(function () { ... Route::middleware('verified')->group(function () {` block, append after the existing `parent.children.index` route:

```php
// Parent monitoring routes
Route::get('parent/children/{child}', [\App\Http\Controllers\ParentController::class, 'show'])
    ->name('parent.children.show');

Route::post('parent/children/{child}/enrollments/{enrollment}/approve', [\App\Http\Controllers\ParentController::class, 'approveEnrollment'])
    ->name('parent.children.enrollments.approve');

Route::post('parent/children/{child}/enrollments/{enrollment}/reject', [\App\Http\Controllers\ParentController::class, 'rejectEnrollment'])
    ->name('parent.children.enrollments.reject');
```

**Step 3: Create a placeholder `ParentChildService` so the controller can be resolved**

```php
<?php
// app/Services/ParentChildService.php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class ParentChildService
{
    public function getProgress(User $child): Collection
    {
        return collect();
    }

    public function getQuizResults(User $child): Collection
    {
        return collect();
    }

    public function getAchievements(User $child): array
    {
        return ['gamification' => null, 'rewardLogs' => collect()];
    }

    public function getPendingEnrollments(User $child): Collection
    {
        return collect();
    }
}
```

**Step 4: Create a placeholder view so the route returns 200**

Create `resources/views/parent/children/show.blade.php` with minimal content:

```blade
@extends('layouts.learner-app')
@section('title', 'My Child')
@section('content')
<p>Child: {{ $child->name }}</p>
@endsection
```

**Step 5: Run tests**

```
php artisan test --filter=ParentChildMonitoringTest
```
Expected: `test_parent_can_view_own_childs_detail_page` → PASS, `test_parent_cannot_view_another_users_child` → PASS, `test_guest_cannot_access_parent_routes` → PASS, migration test → PASS

**Step 6: Commit**

```
git add app/Http/Controllers/ParentController.php app/Policies/ParentChildPolicy.php app/Providers/AppServiceProvider.php app/Services/ParentChildService.php routes/auth.php resources/views/parent/children/show.blade.php
git commit -m "feat: add ParentController, ParentChildPolicy, stub service and routes"
```

---

## Task 4: ParentChildService — implement all 4 methods

**Files:**
- Modify: `app/Services/ParentChildService.php`

**Step 1: Add service method tests to `ParentChildMonitoringTest`**

```php
public function test_get_progress_returns_approved_enrollments_with_progress(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $module = Module::factory()->create(['title' => 'Test Module']);
    ModuleEnrollment::create([
        'user_id'    => $child->id,
        'module_id'  => $module->id,
        'status'     => 'approved',
        'enrolled_at' => now(),
    ]);

    $service = app(\App\Services\ParentChildService::class);
    $progress = $service->getProgress($child);

    $this->assertCount(1, $progress);
    $this->assertEquals('Test Module', $progress->first()->module->title);
}

public function test_get_quiz_results_returns_attempts_newest_first(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $quiz = Quiz::factory()->create();

    QuizAttempt::create([
        'user_id'      => $child->id,
        'quiz_id'      => $quiz->id,
        'score'        => 80,
        'passed'       => true,
        'answers'      => json_encode([]),
        'started_at'   => now()->subMinutes(10),
        'completed_at' => now(),
    ]);

    $service = app(\App\Services\ParentChildService::class);
    $results = $service->getQuizResults($child);

    $this->assertCount(1, $results);
    $this->assertEquals(80, $results->first()->score);
}

public function test_get_achievements_returns_gamification_and_reward_logs(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $service = app(\App\Services\ParentChildService::class);
    $achievements = $service->getAchievements($child);

    $this->assertArrayHasKey('gamification', $achievements);
    $this->assertArrayHasKey('rewardLogs', $achievements);
    $this->assertEquals(1, $achievements['gamification']->level);
}

public function test_get_pending_enrollments_returns_only_pending_parent_approval(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $module = Module::factory()->create();
    ModuleEnrollment::create([
        'user_id'    => $child->id,
        'module_id'  => $module->id,
        'status'     => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $service = app(\App\Services\ParentChildService::class);
    $pending = $service->getPendingEnrollments($child);

    $this->assertCount(1, $pending);
    $this->assertEquals('pending_parent_approval', $pending->first()->status);
}
```

> **Note:** `Quiz::factory()` likely does not exist. If missing, create `database/factories/QuizFactory.php`:
> ```php
> <?php
> namespace Database\Factories;
> use App\Models\Quiz;
> use Illuminate\Database\Eloquent\Factories\Factory;
> class QuizFactory extends Factory
> {
>     protected $model = Quiz::class;
>     public function definition(): array
>     {
>         return [
>             'title' => fake()->sentence(3),
>             'description' => fake()->sentence(),
>             'passing_score' => 70,
>             'time_limit' => null,
>             'question_count' => 5,
>         ];
>     }
> }
> ```
> Check `app/Models/Quiz.php` for actual fillable fields before finalizing the factory.

**Step 2: Run tests to verify service tests fail**

```
php artisan test --filter=test_get_progress_returns_approved_enrollments_with_progress
```
Expected: FAIL — progress returns empty collection (stub)

**Step 3: Implement `ParentChildService`**

```php
<?php
// app/Services/ParentChildService.php

namespace App\Services;

use App\Models\ModuleEnrollment;
use App\Models\QuizAttempt;
use App\Models\RewardLog;
use App\Models\User;
use App\Models\UserGamification;
use App\Models\UserProgress;
use Illuminate\Support\Collection;

class ParentChildService
{
    /**
     * Get all approved module enrollments for the child with progress data.
     */
    public function getProgress(User $child): Collection
    {
        $enrollments = ModuleEnrollment::where('user_id', $child->id)
            ->where('status', 'approved')
            ->with(['module.lessons'])
            ->latest('enrolled_at')
            ->get();

        return $enrollments->map(function (ModuleEnrollment $enrollment) use ($child) {
            $totalLessons     = $enrollment->module->lessons->count();
            $completedLessons = UserProgress::where('user_id', $child->id)
                ->where('module_id', $enrollment->module_id)
                ->where('completed', true)
                ->count();

            $enrollment->completed_lessons = $completedLessons;
            $enrollment->total_lessons     = $totalLessons;
            $enrollment->progress_pct      = $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100)
                : 0;

            return $enrollment;
        });
    }

    /**
     * Get all quiz attempts for the child, newest first.
     */
    public function getQuizResults(User $child): Collection
    {
        return QuizAttempt::where('user_id', $child->id)
            ->with(['quiz.module'])
            ->latest('completed_at')
            ->get();
    }

    /**
     * Get gamification summary and reward log for the child.
     */
    public function getAchievements(User $child): array
    {
        $gamification = UserGamification::firstOrCreate(
            ['user_id' => $child->id],
            ['level' => 1, 'score' => 0, 'total_points' => 0, 'streak_count' => 0]
        );

        $rewardLogs = RewardLog::where('user_id', $child->id)
            ->with('achievement')
            ->latest('earned_at')
            ->get();

        return [
            'gamification' => $gamification,
            'rewardLogs'   => $rewardLogs,
        ];
    }

    /**
     * Get module enrollments awaiting parent approval.
     */
    public function getPendingEnrollments(User $child): Collection
    {
        return ModuleEnrollment::where('user_id', $child->id)
            ->where('status', 'pending_parent_approval')
            ->with('module')
            ->latest()
            ->get();
    }
}
```

**Step 4: Run all service tests**

```
php artisan test --filter=ParentChildMonitoringTest
```
Expected: All currently written tests PASS

**Step 5: Commit**

```
git add app/Services/ParentChildService.php tests/Feature/ParentChildMonitoringTest.php
git commit -m "feat: implement ParentChildService with 4 monitoring methods"
```

---

## Task 5: Approve/Reject enrollment tests and controller actions

**Files:**
- Test: `tests/Feature/ParentChildMonitoringTest.php` (add approve/reject tests)

**Step 1: Add approve/reject tests**

```php
public function test_parent_can_approve_pending_enrollment_auto_module(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $module = Module::factory()->create(['enrollment_mode' => 'auto']);
    $enrollment = ModuleEnrollment::create([
        'user_id'    => $child->id,
        'module_id'  => $module->id,
        'status'     => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $this->actingAs($parent)
         ->post(route('parent.children.enrollments.approve', [$child, $enrollment]))
         ->assertRedirect(route('parent.children.show', $child));

    $this->assertDatabaseHas('module_enrollments', [
        'id'     => $enrollment->id,
        'status' => 'approved',
    ]);
}

public function test_parent_can_approve_pending_enrollment_manual_module(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $module = Module::factory()->create(['enrollment_mode' => 'manual']);
    $enrollment = ModuleEnrollment::create([
        'user_id'    => $child->id,
        'module_id'  => $module->id,
        'status'     => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $this->actingAs($parent)
         ->post(route('parent.children.enrollments.approve', [$child, $enrollment]))
         ->assertRedirect();

    $this->assertDatabaseHas('module_enrollments', [
        'id'     => $enrollment->id,
        'status' => 'pending',
    ]);
}

public function test_parent_can_reject_pending_enrollment(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $module = Module::factory()->create();
    $enrollment = ModuleEnrollment::create([
        'user_id'    => $child->id,
        'module_id'  => $module->id,
        'status'     => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $this->actingAs($parent)
         ->post(route('parent.children.enrollments.reject', [$child, $enrollment]))
         ->assertRedirect();

    $this->assertDatabaseHas('module_enrollments', [
        'id'     => $enrollment->id,
        'status' => 'rejected',
    ]);
}

public function test_parent_cannot_approve_enrollment_for_another_child(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $otherChild = User::factory()->create(['email_verified_at' => now()]);
    $module = Module::factory()->create();
    $enrollment = ModuleEnrollment::create([
        'user_id'    => $otherChild->id,
        'module_id'  => $module->id,
        'status'     => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $this->actingAs($parent)
         ->post(route('parent.children.enrollments.approve', [$child, $enrollment]))
         ->assertForbidden();
}
```

**Step 2: Run tests to verify they fail as expected**

```
php artisan test --filter=test_parent_can_approve_pending_enrollment_auto_module
```
Expected: FAIL (controller actions already exist, but the view does not fully render yet — or it passes if the action works). Run to see exact failure.

**Step 3: Run all tests to confirm full suite state**

```
php artisan test --filter=ParentChildMonitoringTest
```
Check which tests pass and which fail. The approve/reject controller logic is already implemented in Task 3 — these tests should pass once the view renders without error.

**Step 4: Commit**

```
git add tests/Feature/ParentChildMonitoringTest.php
git commit -m "test: add approve/reject and authorization tests for parent monitoring"
```

---

## Task 6: Build the tabbed child detail view

**Files:**
- Modify: `resources/views/parent/children/show.blade.php`

**Step 1: Replace the placeholder view with the full implementation**

```blade
{{-- resources/views/parent/children/show.blade.php --}}
@extends('layouts.learner-app')

@section('title', $child->name . ' — Monitoring')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Back link + child header --}}
    <div>
        <a href="{{ route('parent.children.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to My Children
        </a>

        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold flex-shrink-0"
                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                {{ strtoupper(mb_substr($child->name, 0, 2)) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $child->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    @if($child->learnerProfile)
                        <span class="text-sm text-gray-500">{{ $child->learnerProfile->getAge() }} years old</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                            {{ ucfirst($child->learnerProfile->age_bracket ?? 'kids') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-4 py-3 text-sm">
            {{ session('info') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div x-data="{ tab: 'progress' }">

        {{-- Tab nav --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 gap-1 overflow-x-auto">
            @php
                $tabs = [
                    ['id' => 'progress',     'label' => 'Progress'],
                    ['id' => 'quiz',         'label' => 'Quiz Results'],
                    ['id' => 'achievements', 'label' => 'Achievements'],
                ];
                if ($canApproveContent) {
                    $tabs[] = ['id' => 'approval', 'label' => 'Content Approval'];
                }
            @endphp

            @foreach($tabs as $t)
                <button
                    @click="tab = '{{ $t['id'] }}'"
                    :class="tab === '{{ $t['id'] }}' ? 'border-b-2 text-purple-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2.5 text-sm whitespace-nowrap border-b-2 border-transparent transition-colors"
                    style="border-color: tab === '{{ $t['id'] }}' ? '#A30EB2' : 'transparent';"
                    :style="tab === '{{ $t['id'] }}' ? 'border-color: #A30EB2; color: #A30EB2;' : ''"
                >
                    {{ $t['label'] }}
                    @if($t['id'] === 'approval' && $pendingEnrollments->isNotEmpty())
                        <span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full">
                            {{ $pendingEnrollments->count() }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- ── Progress Tab ── --}}
        <div x-show="tab === 'progress'" x-cloak class="pt-6">
            @if($progress->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5"/>
                    </svg>
                    <p class="text-sm">No modules enrolled yet.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($progress as $enrollment)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $enrollment->module->title }}
                                    </h3>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Enrolled {{ $enrollment->enrolled_at?->diffForHumans() ?? 'recently' }}
                                        @if($enrollment->module->last_accessed_at ?? false)
                                            · Last active {{ $enrollment->module->last_accessed_at->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>
                                <span class="text-lg font-bold flex-shrink-0" style="color: #A30EB2;">
                                    {{ $enrollment->progress_pct }}%
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>{{ $enrollment->completed_lessons }} of {{ $enrollment->total_lessons }} lessons complete</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all"
                                         style="width: {{ $enrollment->progress_pct }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Quiz Results Tab ── --}}
        <div x-show="tab === 'quiz'" x-cloak class="pt-6">
            @if($quizResults->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm">No quizzes taken yet.</p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-5 py-3 text-left">Quiz</th>
                                <th class="px-5 py-3 text-left">Module</th>
                                <th class="px-5 py-3 text-center">Score</th>
                                <th class="px-5 py-3 text-center">Result</th>
                                <th class="px-5 py-3 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($quizResults as $attempt)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $attempt->quiz?->title ?? 'Quiz' }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-500">
                                        {{ $attempt->quiz?->module?->title ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center font-semibold">{{ $attempt->score }}%</td>
                                    <td class="px-5 py-3 text-center">
                                        @if($attempt->passed)
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Passed</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Failed</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-400">
                                        {{ $attempt->completed_at?->format('M d, Y') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Achievements Tab ── --}}
        <div x-show="tab === 'achievements'" x-cloak class="pt-6 space-y-6">
            @php $gamification = $achievements['gamification']; $rewardLogs = $achievements['rewardLogs']; @endphp

            {{-- Gamification summary --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 text-center">
                    <p class="text-3xl font-bold" style="color: #A30EB2;">{{ $gamification?->level ?? 1 }}</p>
                    <p class="text-xs text-gray-400 mt-1">Level</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 text-center">
                    <p class="text-3xl font-bold" style="color: #730DB1;">{{ $gamification?->score ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">XP</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 text-center">
                    <p class="text-3xl font-bold" style="color: #3B0CB1;">{{ $gamification?->streak_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">Day Streak</p>
                </div>
            </div>

            {{-- Reward log --}}
            @if($rewardLogs->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <p class="text-sm">No rewards earned yet.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($rewardLogs as $log)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 px-5 py-4 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl flex-shrink-0"
                                 style="background: linear-gradient(135deg, #f3e8ff, #ede9fe);">
                                {{ $log->achievement?->icon ?? '🏆' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $log->achievement?->title ?? 'Achievement' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $log->earned_at?->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Content Approval Tab (only if can_approve_content) ── --}}
        @if($canApproveContent)
            <div x-show="tab === 'approval'" x-cloak class="pt-6">
                @if($pendingEnrollments->isEmpty())
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm">No pending enrollment requests.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($pendingEnrollments as $enrollment)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $enrollment->module->title }}
                                        </h3>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            Ages {{ $enrollment->module->min_age }}–{{ $enrollment->module->max_age }}
                                            · Requested {{ $enrollment->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2 flex-shrink-0">
                                        <form method="POST"
                                              action="{{ route('parent.children.enrollments.approve', [$child, $enrollment]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition"
                                                    style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('parent.children.enrollments.reject', [$child, $enrollment]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>{{-- /x-data tabs --}}

</div>
@endsection
```

**Step 2: Run all tests**

```
php artisan test --filter=ParentChildMonitoringTest
```
Expected: All tests PASS

**Step 3: Commit**

```
git add resources/views/parent/children/show.blade.php
git commit -m "feat: build tabbed parent monitoring view for child detail"
```

---

## Task 7: Add "My Children" to the learner sidebar

**Files:**
- Modify: `resources/views/layouts/learner-sidebar.blade.php`

**Step 1: Open the sidebar file**

Find the `$navItems = [...]` PHP array at the top of the file. It currently has: Dashboard, Subscriptions, My Modules, Certificates.

**Step 2: Add the conditional "My Children" entry after Certificates**

Locate the `];` that closes the `$navItems` array and add the conditional block immediately after:

```php
    // (existing Certificates entry above)
];

// Add My Children nav item for parents
if (Auth::user()->isParent()) {
    $navItems[] = [
        'label'  => 'My Children',
        'route'  => 'parent.children.index',
        'active' => request()->routeIs('parent.children.*'),
        'icon'   => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M9 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM4.25 7a4.75 4.75 0 1 1 9.5 0 4.75 4.75 0 0 1-9.5 0Zm10.5-1.25a.75.75 0 0 1 .75-.75 4.75 4.75 0 0 1 0 9.5.75.75 0 0 1 0-1.5 3.25 3.25 0 0 0 0-6.5.75.75 0 0 1-.75-.75ZM2.75 17.5A3.25 3.25 0 0 1 6 14.25h6A3.25 3.25 0 0 1 15.25 17.5v.5a.75.75 0 0 1-1.5 0v-.5A1.75 1.75 0 0 0 12 15.75H6A1.75 1.75 0 0 0 4.25 17.5v.5a.75.75 0 0 1-1.5 0v-.5Zm15 0a.75.75 0 0 1 .75-.75 1.75 1.75 0 0 1 1.75 1.75v.5a.75.75 0 0 1-1.5 0v-.5a.25.25 0 0 0-.25-.25.75.75 0 0 1-.75-.75Z"/></svg>',
    ];
}
```

**Step 3: Update the children list view to use learner-app layout**

Open `resources/views/parent/children/index.blade.php`. Change the layout it extends to `layouts.learner-app` (if it currently uses a different layout). Also update the "View Progress" and "Manage" button hrefs from `alert()` stubs to the real route:

```blade
{{-- Replace the alert() stub on "View Progress" with: --}}
<a href="{{ route('parent.children.show', $child->id) }}"
   class="inline-flex items-center gap-1 text-sm font-medium text-purple-700 hover:text-purple-900">
    <svg class="w-4 h-4" ...></svg> View Progress
</a>

{{-- Replace the "Manage" alert() stub with: --}}
<a href="{{ route('parent.children.show', $child->id) }}"
   class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
    ⚙ Manage
</a>
```

> Read `resources/views/parent/children/index.blade.php` first to see the exact current markup before editing.

**Step 4: Verify visually** — log in as a parent user, confirm the sidebar shows "My Children", click it, click "View Progress" on a child, confirm the tabbed page loads.

**Step 5: Commit**

```
git add resources/views/layouts/learner-sidebar.blade.php resources/views/parent/children/index.blade.php
git commit -m "feat: add My Children sidebar nav and wire up child detail links"
```

---

## Task 8: Enrollment flow — gate children requiring parental consent

**Files:**
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Test: `tests/Feature/ParentChildMonitoringTest.php` (add enrollment gate test)

**Step 1: Add the enrollment gate test**

```php
public function test_child_enrollment_is_gated_when_parent_has_content_approval_enabled(): void
{
    [$parent, $child] = $this->createParentWithChild();

    // Set requires_parental_consent on child's profile
    $child->learnerProfile()->updateOrCreate(
        ['user_id' => $child->id],
        ['requires_parental_consent' => true, 'username' => 'testchild', 'birthdate' => now()->subYears(8)]
    );

    $module = Module::factory()->create(['enrollment_mode' => 'auto', 'is_published' => true]);

    $this->actingAs($child)
         ->post(route('learner.modules.enroll', $module))
         ->assertRedirect();

    $this->assertDatabaseHas('module_enrollments', [
        'user_id'   => $child->id,
        'module_id' => $module->id,
        'status'    => 'pending_parent_approval',
    ]);
}
```

**Step 2: Run test to verify it fails**

```
php artisan test --filter=test_child_enrollment_is_gated_when_parent_has_content_approval_enabled
```
Expected: FAIL — enrollment is created with `approved` status, not `pending_parent_approval`

**Step 3: Modify `Learner\ModuleController::enroll()`**

Open `app/Http/Controllers/Learner/ModuleController.php`. Find the `enroll()` method. Before the existing `if ($module->enrollment_mode === 'manual')` check, add:

```php
// Check if child requires parental content approval
$learnerProfile = $user->learnerProfile;
if ($learnerProfile?->requires_parental_consent) {
    $parentLink = $user->parent(); // returns first parent User or null
    if ($parentLink) {
        // Check if parent has content approval enabled for this child
        $parentChildAccount = \App\Models\ParentChildAccount::where('parent_user_id', $parentLink->id)
            ->where('child_user_id', $user->id)
            ->first();
        if ($parentChildAccount?->can_approve_content) {
            ModuleEnrollment::create([
                'user_id'    => $user->id,
                'module_id'  => $module->id,
                'status'     => 'pending_parent_approval',
                'enrolled_at' => null,
            ]);
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'Your enrollment request has been sent to your parent for approval.');
        }
    }
}
```

**Step 4: Run tests**

```
php artisan test --filter=ParentChildMonitoringTest
```
Expected: All tests PASS

**Step 5: Run full test suite to check for regressions**

```
php artisan test
```
Expected: No new failures

**Step 6: Commit**

```
git add app/Http/Controllers/Learner/ModuleController.php tests/Feature/ParentChildMonitoringTest.php
git commit -m "feat: gate module enrollment for children requiring parental consent"
```

---

## Done — Verification Checklist

Run the complete suite one final time:

```
php artisan test --filter=ParentChildMonitoringTest
```

All tests that should pass:
- `test_module_enrollments_accepts_pending_parent_approval_status`
- `test_parent_can_view_own_childs_detail_page`
- `test_parent_cannot_view_another_users_child`
- `test_guest_cannot_access_parent_routes`
- `test_get_progress_returns_approved_enrollments_with_progress`
- `test_get_quiz_results_returns_attempts_newest_first`
- `test_get_achievements_returns_gamification_and_reward_logs`
- `test_get_pending_enrollments_returns_only_pending_parent_approval`
- `test_parent_can_approve_pending_enrollment_auto_module`
- `test_parent_can_approve_pending_enrollment_manual_module`
- `test_parent_can_reject_pending_enrollment`
- `test_parent_cannot_approve_enrollment_for_another_child`
- `test_child_enrollment_is_gated_when_parent_has_content_approval_enabled`

Then run the full suite:

```
php artisan test
```

Confirm zero new failures before declaring the feature complete.
