# Gamification Enhancement Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace per-quiz daily limits with a global shield system, implement a `GamificationService` centralizing all point/streak logic, add streak savers, streak milestones, a streak card UI, an out-of-shields modal, a gamification rules page, and custom branded toasts.

**Architecture:** `quiz_daily_limits` table is renamed and restructured to `user_daily_shields` (global pool, one row per learner per day). A new `GamificationService` replaces scattered point logic in controllers. The existing `UserGamification` model gains `longest_streak` and `streak_savers` columns. All UI additions use the existing Blade component pattern.

**Tech Stack:** Laravel 12, Blade components, Alpine.js, Tailwind CSS v3, Toastify JS (existing `toast.js`/`toast-custom.css`), PHPUnit

---

## Task 1: Migration — Rename and restructure `quiz_daily_limits`

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_rename_quiz_daily_limits_to_user_daily_shields.php`

**Step 1: Write the failing test**

```php
// tests/Feature/Gamification/UserDailyShieldTest.php
<?php

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Models\UserDailyShield;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDailyShieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(\Schema::hasTable('user_daily_shields'));
        $this->assertTrue(\Schema::hasColumn('user_daily_shields', 'user_id'));
        $this->assertTrue(\Schema::hasColumn('user_daily_shields', 'shields_remaining'));
        $this->assertTrue(\Schema::hasColumn('user_daily_shields', 'date'));
        $this->assertFalse(\Schema::hasColumn('user_daily_shields', 'quiz_id'));
    }

    public function test_old_table_no_longer_exists(): void
    {
        $this->assertFalse(\Schema::hasTable('quiz_daily_limits'));
    }
}
```

**Step 2: Run test to verify it fails**

```bash
php artisan test --filter=UserDailyShieldTest
```
Expected: FAIL — table `user_daily_shields` does not exist.

**Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_daily_limits', function (Blueprint $table) {
            // Drop foreign key and quiz_id column
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');

            // Rename attempts → shields_remaining, change default to 3
            $table->renameColumn('attempts', 'shields_remaining');
        });

        Schema::rename('quiz_daily_limits', 'user_daily_shields');

        Schema::table('user_daily_shields', function (Blueprint $table) {
            // Update unique constraint: was (user_id, quiz_id, date), now (user_id, date)
            // Drop old unique index (name may vary — check your migration)
            $table->dropUnique(['user_id', 'date']); // if it exists from old migration
        });

        // Ensure the correct unique constraint
        Schema::table('user_daily_shields', function (Blueprint $table) {
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::rename('user_daily_shields', 'quiz_daily_limits');
        Schema::table('quiz_daily_limits', function (Blueprint $table) {
            $table->renameColumn('shields_remaining', 'attempts');
            $table->unsignedBigInteger('quiz_id')->nullable()->after('user_id');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
        });
    }
};
```

> **Note:** Run `php artisan migrate:status` first to check the exact unique index name from the old migration and adjust `dropUnique` accordingly. If the old migration did not create a unique index including `quiz_id`, simply ensure `unique(['user_id', 'date'])` is added.

**Step 4: Run migration**

```bash
php artisan migrate
```

**Step 5: Run test to verify it passes**

```bash
php artisan test --filter=UserDailyShieldTest
```
Expected: PASS

**Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat(gamification): rename quiz_daily_limits to user_daily_shields, drop quiz_id"
```

---

## Task 2: Migration — Add `longest_streak` and `streak_savers` to `user_gamification`

**Files:**
- Modify: `database/migrations/2026_03_07_050903_*.php` (the existing longest_streak stub — check its contents first)
- OR Create: new migration if the stub is empty/unusable

**Step 1: Check the existing migration stub**

```bash
cat database/migrations/2026_03_07_050903*.php
```

If it already adds `longest_streak`, extend it to also add `streak_savers`. If it's empty, fill it in.

**Step 2: Write the failing test** (add to `UserDailyShieldTest.php` or a new file)

```php
public function test_user_gamification_has_longest_streak_and_streak_savers(): void
{
    $this->assertTrue(\Schema::hasColumn('user_gamification', 'longest_streak'));
    $this->assertTrue(\Schema::hasColumn('user_gamification', 'streak_savers'));
}
```

**Step 3: Run test to verify it fails**

```bash
php artisan test --filter=test_user_gamification_has_longest_streak_and_streak_savers
```

**Step 4: Fill in / create the migration**

```php
public function up(): void
{
    Schema::table('user_gamification', function (Blueprint $table) {
        if (!Schema::hasColumn('user_gamification', 'longest_streak')) {
            $table->unsignedInteger('longest_streak')->default(0)->after('streak_count');
        }
        if (!Schema::hasColumn('user_gamification', 'streak_savers')) {
            $table->unsignedTinyInteger('streak_savers')->default(0)->after('longest_streak');
        }
    });
}

public function down(): void
{
    Schema::table('user_gamification', function (Blueprint $table) {
        $table->dropColumn(['longest_streak', 'streak_savers']);
    });
}
```

**Step 5: Run migration and test**

```bash
php artisan migrate
php artisan test --filter=test_user_gamification_has_longest_streak_and_streak_savers
```
Expected: PASS

**Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat(gamification): add longest_streak and streak_savers to user_gamification"
```

---

## Task 3: Create `UserDailyShield` model (replaces `QuizDailyLimit`)

**Files:**
- Create: `app/Models/UserDailyShield.php`
- Delete: `app/Models/QuizDailyLimit.php` (after all references updated)

**Step 1: Add tests to `UserDailyShieldTest.php`**

```php
public function test_get_shields_returns_3_for_new_user_today(): void
{
    $user = User::factory()->create();
    $user->assignRole('learner');

    $shields = UserDailyShield::getShields($user);

    $this->assertEquals(3, $shields);
}

public function test_drain_shield_decrements_by_one(): void
{
    $user = User::factory()->create();
    $user->assignRole('learner');

    UserDailyShield::drainShield($user);

    $this->assertEquals(2, UserDailyShield::getShields($user));
}

public function test_drain_shield_floors_at_zero(): void
{
    $user = User::factory()->create();
    $user->assignRole('learner');

    UserDailyShield::drainShield($user);
    UserDailyShield::drainShield($user);
    UserDailyShield::drainShield($user);
    UserDailyShield::drainShield($user); // 4th drain

    $this->assertEquals(0, UserDailyShield::getShields($user));
}

public function test_refill_one_increments_by_one_max_3(): void
{
    $user = User::factory()->create();
    $user->assignRole('learner');

    UserDailyShield::drainShield($user); // now 2
    UserDailyShield::refillOne($user);

    $this->assertEquals(3, UserDailyShield::getShields($user));
}

public function test_refill_full_restores_to_3(): void
{
    $user = User::factory()->create();
    $user->assignRole('learner');

    UserDailyShield::drainShield($user);
    UserDailyShield::drainShield($user); // now 1
    UserDailyShield::refillFull($user);

    $this->assertEquals(3, UserDailyShield::getShields($user));
}

public function test_premium_user_always_gets_max_shields(): void
{
    $user = User::factory()->create();
    $user->assignRole('learner');
    // Make premium — adjust to how isPremium() works in your codebase
    $user->subscription_status = 'active';
    $user->save();

    UserDailyShield::drainShield($user); // should not drain

    $this->assertEquals(PHP_INT_MAX, UserDailyShield::getShields($user));
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=UserDailyShieldTest
```
Expected: FAIL — class not found.

**Step 3: Create the model**

```php
<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserDailyShield extends Model
{
    protected $table = 'user_daily_shields';

    protected $fillable = ['user_id', 'shields_remaining', 'date'];

    protected function casts(): array
    {
        return [
            'date'              => 'date',
            'shields_remaining' => 'integer',
        ];
    }

    const MAX_SHIELDS = 3;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getShields(User $user): int
    {
        if ($user->isPremium()) {
            return PHP_INT_MAX;
        }

        $row = static::firstOrCreate(
            ['user_id' => $user->id, 'date' => Carbon::today()],
            ['shields_remaining' => static::MAX_SHIELDS]
        );

        return $row->shields_remaining;
    }

    public static function drainShield(User $user): void
    {
        if ($user->isPremium()) {
            return;
        }

        $row = static::firstOrCreate(
            ['user_id' => $user->id, 'date' => Carbon::today()],
            ['shields_remaining' => static::MAX_SHIELDS]
        );

        $row->shields_remaining = max(0, $row->shields_remaining - 1);
        $row->save();
    }

    public static function refillOne(User $user): void
    {
        $row = static::firstOrCreate(
            ['user_id' => $user->id, 'date' => Carbon::today()],
            ['shields_remaining' => static::MAX_SHIELDS]
        );

        $row->shields_remaining = min(static::MAX_SHIELDS, $row->shields_remaining + 1);
        $row->save();
    }

    public static function refillFull(User $user): void
    {
        $row = static::firstOrCreate(
            ['user_id' => $user->id, 'date' => Carbon::today()],
            ['shields_remaining' => static::MAX_SHIELDS]
        );

        $row->shields_remaining = static::MAX_SHIELDS;
        $row->save();
    }
}
```

**Step 4: Run tests to verify they pass**

```bash
php artisan test --filter=UserDailyShieldTest
```
Expected: PASS

**Step 5: Commit**

```bash
git add app/Models/UserDailyShield.php tests/Feature/Gamification/UserDailyShieldTest.php
git commit -m "feat(gamification): add UserDailyShield model with get/drain/refill methods"
```

---

## Task 4: Create `GamificationService`

**Files:**
- Create: `app/Services/GamificationService.php`
- Create: `tests/Feature/Gamification/GamificationServiceTest.php`

**Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Models\UserGamification;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GamificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GamificationService();
    }

    private function userWithGamification(): User
    {
        $user = User::factory()->create();
        $user->assignRole('learner');
        UserGamification::create([
            'user_id'      => $user->id,
            'level'        => 1,
            'score'        => 0,
            'total_points' => 0,
            'streak_count' => 0,
        ]);
        return $user;
    }

    public function test_award_points_increments_score_and_total_points(): void
    {
        $user = $this->userWithGamification();

        $this->service->awardPoints($user, 'topic_complete', 10);

        $gamification = $user->gamification()->first();
        $this->assertEquals(10, $gamification->score);
        $this->assertEquals(10, $gamification->total_points);
    }

    public function test_spend_points_decrements_score_only(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['score' => 100, 'total_points' => 200]);

        $result = $this->service->spendPoints($user, 50);

        $this->assertTrue($result);
        $gamification = $user->gamification()->first();
        $this->assertEquals(50, $gamification->score);
        $this->assertEquals(200, $gamification->total_points); // unchanged
    }

    public function test_spend_points_returns_false_when_insufficient(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['score' => 30]);

        $result = $this->service->spendPoints($user, 50);

        $this->assertFalse($result);
        $this->assertEquals(30, $user->gamification()->first()->score); // unchanged
    }

    public function test_update_streak_increments_when_last_act_was_yesterday(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['last_act_at' => now()->subDay(), 'streak_count' => 3]);

        $this->service->updateStreak($user);

        $this->assertEquals(4, $user->gamification()->first()->streak_count);
    }

    public function test_update_streak_resets_when_missed_day_and_no_savers(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update([
            'last_act_at'   => now()->subDays(2),
            'streak_count'  => 5,
            'streak_savers' => 0,
        ]);

        $this->service->updateStreak($user);

        $this->assertEquals(1, $user->gamification()->first()->streak_count);
    }

    public function test_update_streak_preserves_when_missed_day_and_has_savers(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update([
            'last_act_at'   => now()->subDays(2),
            'streak_count'  => 5,
            'streak_savers' => 2,
        ]);

        $this->service->updateStreak($user);

        $gamification = $user->gamification()->first();
        $this->assertEquals(5, $gamification->streak_count);  // preserved
        $this->assertEquals(1, $gamification->streak_savers); // consumed one
    }

    public function test_longest_streak_updated_when_exceeded(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update([
            'last_act_at'    => now()->subDay(),
            'streak_count'   => 6,
            'longest_streak' => 6,
        ]);

        $this->service->updateStreak($user);

        $this->assertEquals(7, $user->gamification()->first()->longest_streak);
    }

    public function test_check_streak_milestone_returns_50_on_7th_day(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 7]);

        $bonus = $this->service->checkStreakMilestone($user);

        $this->assertEquals(50, $bonus);
    }

    public function test_check_streak_milestone_returns_200_on_30th_day(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 30]);

        $bonus = $this->service->checkStreakMilestone($user);

        $this->assertEquals(200, $bonus);
    }

    public function test_check_streak_milestone_returns_null_on_non_milestone(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 5]);

        $this->assertNull($this->service->checkStreakMilestone($user));
    }
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=GamificationServiceTest
```
Expected: FAIL — class not found.

**Step 3: Create the service**

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGamification;

class GamificationService
{
    public function awardPoints(User $user, string $reason, int $points): void
    {
        $gamification = $user->gamification;
        if (!$gamification) {
            return;
        }

        $gamification->increment('score', $points);
        $gamification->increment('total_points', $points);

        $newLevel = (int) floor($gamification->fresh()->score / 100) + 1;
        if ($newLevel > $gamification->level) {
            $gamification->update(['level' => $newLevel]);
        }
    }

    public function spendPoints(User $user, int $points): bool
    {
        $gamification = $user->gamification;
        if (!$gamification || $gamification->score < $points) {
            return false;
        }

        $gamification->decrement('score', $points);
        // total_points is lifetime total — never decremented

        return true;
    }

    public function updateStreak(User $user): void
    {
        $gamification = $user->gamification;
        if (!$gamification) {
            return;
        }

        $lastAct = $gamification->last_act_at;

        if ($lastAct === null || $lastAct->isYesterday()) {
            // Normal increment
            $gamification->increment('streak_count');
        } elseif ($lastAct->isToday()) {
            // Already counted today — no change needed
            return;
        } else {
            // Missed one or more days
            if ($gamification->streak_savers > 0) {
                // Consume a streak saver and preserve streak
                $gamification->decrement('streak_savers');
                session()->flash('streak_saved', [
                    'streak'       => $gamification->streak_count,
                    'savers_left'  => $gamification->streak_savers - 1 + 1, // after decrement
                ]);
                // Still update last_act_at to today
                $gamification->last_act_at = now();
                $gamification->save();
                return;
            } else {
                // Reset streak
                $gamification->streak_count = 1;
            }
        }

        // Update last_act_at
        $gamification->last_act_at = now();

        // Update longest streak if exceeded
        $fresh = $gamification->fresh();
        if ($gamification->streak_count > ($fresh->longest_streak ?? 0)) {
            $gamification->longest_streak = $gamification->streak_count;
        }

        $gamification->save();

        // Check for milestone bonus
        $bonus = $this->checkStreakMilestone($user);
        if ($bonus !== null) {
            $this->awardPoints($user, 'streak_milestone', $bonus);
            session()->flash('streak_milestone', [
                'days'  => $gamification->streak_count,
                'bonus' => $bonus,
            ]);
        }
    }

    public function checkStreakMilestone(User $user): ?int
    {
        $gamification = $user->gamification;
        if (!$gamification || $gamification->streak_count === 0) {
            return null;
        }

        $count = $gamification->streak_count;

        if ($count % 30 === 0) {
            return 200;
        }

        if ($count % 7 === 0) {
            return 50;
        }

        return null;
    }
}
```

**Step 4: Run tests to verify they pass**

```bash
php artisan test --filter=GamificationServiceTest
```
Expected: PASS

**Step 5: Commit**

```bash
git add app/Services/GamificationService.php tests/Feature/Gamification/GamificationServiceTest.php
git commit -m "feat(gamification): add GamificationService with award/spend/streak/milestone logic"
```

---

## Task 5: Update `UserGamification` model — add new fillable fields

**Files:**
- Modify: `app/Models/UserGamification.php`

**Step 1: Update fillable and casts**

Add `longest_streak` and `streak_savers` to `$fillable` and `casts()`:

```php
protected $fillable = [
    'user_id', 'level', 'score', 'total_points',
    'streak_count', 'last_act_at', 'longest_streak', 'streak_savers',
];

protected function casts(): array
{
    return [
        'level'          => 'integer',
        'score'          => 'integer',
        'total_points'   => 'integer',
        'streak_count'   => 'integer',
        'longest_streak' => 'integer',
        'streak_savers'  => 'integer',
        'last_act_at'    => 'datetime',
    ];
}
```

**Step 2: Remove `addPoints()` and `updateStreak()` from the model**

These are now in `GamificationService`. Keep the model as a pure Eloquent model. Remove both methods.

**Step 3: Run the full test suite to catch regressions**

```bash
php artisan test
```

Fix any callers of `$gamification->addPoints()` or `$gamification->updateStreak()` — they will be replaced in Tasks 6–7.

**Step 4: Commit**

```bash
git add app/Models/UserGamification.php
git commit -m "refactor(gamification): move addPoints/updateStreak to GamificationService, add new fillable fields"
```

---

## Task 6: Update `QuizController` — replace `QuizDailyLimit` with `UserDailyShield`, wire `GamificationService`

**Files:**
- Modify: `app/Http/Controllers/QuizController.php`
- Create: `tests/Feature/Gamification/QuizShieldGateTest.php`

**Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Gamification;

use App\Models\Quiz;
use App\Models\User;
use App\Models\UserDailyShield;
use App\Models\UserGamification;
use App\Models\ModuleEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizShieldGateTest extends TestCase
{
    use RefreshDatabase;

    private function enrolledLearner(Quiz $quiz): User
    {
        $user = User::factory()->create();
        $user->assignRole('learner');
        UserGamification::create([
            'user_id' => $user->id, 'level' => 1, 'score' => 0, 'total_points' => 0, 'streak_count' => 0,
        ]);
        $moduleId = $quiz->module_id ?? $quiz->lesson?->module_id;
        ModuleEnrollment::create(['user_id' => $user->id, 'module_id' => $moduleId, 'status' => 'approved']);
        return $user;
    }

    public function test_quiz_start_blocked_when_zero_shields(): void
    {
        $quiz = Quiz::factory()->create();
        $user = $this->enrolledLearner($quiz);

        // Drain all shields
        UserDailyShield::create(['user_id' => $user->id, 'shields_remaining' => 0, 'date' => today()]);

        $response = $this->actingAs($user)->get(route('quizzes.start', $quiz));

        $response->assertRedirect();
        $this->assertSessionHasErrors(['shields']); // or assertSessionHas('out_of_shields', true)
    }

    public function test_shield_drained_after_failed_quiz(): void
    {
        $quiz = Quiz::factory()->has(\App\Models\QuizQuestion::factory()->count(1), 'questions')->create();
        $user = $this->enrolledLearner($quiz);

        // Submit with no answers (will fail)
        $this->actingAs($user)->post(route('quizzes.submit', $quiz), ['answers' => []]);

        $this->assertEquals(2, UserDailyShield::getShields($user));
    }

    public function test_shield_not_drained_after_passed_quiz(): void
    {
        // Build a simple passing quiz scenario
        // (Adjust factory calls to your actual factories)
        $quiz = Quiz::factory()->create(['passing_score' => 70]);
        $user = $this->enrolledLearner($quiz);

        // Submit with all correct answers...
        // (Full test setup depends on question factories — expand as needed)
        $this->assertEquals(3, UserDailyShield::getShields($user));
    }
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=QuizShieldGateTest
```

**Step 3: Update `QuizController`**

Replace all `QuizDailyLimit` references with `UserDailyShield`. Replace `$gamification->addPoints()` / `$gamification->updateStreak()` calls with `GamificationService`.

Key changes in `start()`:
```php
use App\Models\UserDailyShield;
use App\Services\GamificationService;

public function start(Quiz $quiz)
{
    $user = auth()->user();

    // ... enrollment check (unchanged) ...

    // Shield check (replaces QuizDailyLimit check)
    $shields = UserDailyShield::getShields($user);
    if ($shields <= 0) {
        return redirect()->back()
            ->with('out_of_shields', true)
            ->withErrors(['shields' => 'You have no shields left today.']);
    }

    $quiz->load(['questions.options']);
    return view('quizzes.take', compact('quiz', 'shields'));
}
```

Key changes in `submit()` — after scoring:
```php
$gamificationService = app(GamificationService::class);

if ($passed) {
    $points = $score === 100 ? 30 : 25;
    $gamificationService->awardPoints($user, 'quiz_pass', $points);
    $message = "Congratulations! You passed and earned {$points} points! 🎉";
    session()->flash('points_earned', ['points' => $points, 'reason' => 'quiz pass']);
} else {
    $gamificationService->awardPoints($user, 'quiz_fail', 5);
    // Drain shield on failure
    UserDailyShield::drainShield($user);
    $remainingShields = UserDailyShield::getShields($user);
    session()->flash('shield_lost', ['remaining' => $remainingShields]);
    if ($remainingShields === 0) {
        session()->flash('out_of_shields', true);
    }
    $message = "You earned 5 points for trying! Keep practicing! 💪";
    session()->flash('points_earned', ['points' => 5, 'reason' => 'quiz attempt']);
}

// Remove QuizDailyLimit::incrementAttempts() call — no longer needed
```

Also remove the `history()` method's `QuizDailyLimit::getRemainingAttempts()` call — replace with `UserDailyShield::getShields($user)`.

**Step 4: Run tests**

```bash
php artisan test --filter=QuizShieldGateTest
php artisan test
```
Expected: PASS (fix regressions as they appear)

**Step 5: Commit**

```bash
git add app/Http/Controllers/QuizController.php tests/Feature/Gamification/QuizShieldGateTest.php
git commit -m "feat(gamification): update QuizController to use UserDailyShield and GamificationService"
```

---

## Task 7: Update `LessonController` — wire `GamificationService` for topic/lesson point awards and streak

**Files:**
- Modify: `app/Http/Controllers/Learner/LessonController.php`
- Create: `tests/Feature/Gamification/LessonGamificationTest.php`

**Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Gamification;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Models\UserGamification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonGamificationTest extends TestCase
{
    use RefreshDatabase;

    private function enrolledLearner(Module $module): User
    {
        $user = User::factory()->create();
        $user->assignRole('learner');
        UserGamification::create([
            'user_id' => $user->id, 'level' => 1, 'score' => 0, 'total_points' => 0,
            'streak_count' => 0, 'longest_streak' => 0,
        ]);
        ModuleEnrollment::create(['user_id' => $user->id, 'module_id' => $module->id, 'status' => 'approved']);
        return $user;
    }

    public function test_completing_topic_awards_10_points(): void
    {
        $module = Module::factory()->published()->create();
        $lesson = Lesson::factory()->published()->for($module)->create();
        $topic  = LessonTopic::factory()->for($lesson)->create();
        $user   = $this->enrolledLearner($module);

        $this->actingAs($user)->post(route('learner.lessons.topics.complete', $topic));

        $this->assertEquals(10, $user->gamification()->first()->score);
    }

    public function test_completing_topic_updates_streak(): void
    {
        $module = Module::factory()->published()->create();
        $lesson = Lesson::factory()->published()->for($module)->create();
        $topic  = LessonTopic::factory()->for($lesson)->create();
        $user   = $this->enrolledLearner($module);

        $this->actingAs($user)->post(route('learner.lessons.topics.complete', $topic));

        $this->assertNotNull($user->gamification()->first()->last_act_at);
    }

    public function test_completing_last_topic_awards_lesson_bonus_15_points(): void
    {
        $module = Module::factory()->published()->create();
        $lesson = Lesson::factory()->published()->for($module)->create();
        $topic  = LessonTopic::factory()->for($lesson)->create(['is_prerequisite' => true]);
        $user   = $this->enrolledLearner($module);

        $this->actingAs($user)->post(route('learner.lessons.topics.complete', $topic));

        // Topic (+10) + Lesson complete bonus (+15) = 25
        $this->assertEquals(25, $user->gamification()->first()->score);
    }
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=LessonGamificationTest
```

**Step 3: Update `LessonController::completeTopic()`**

Replace the `$gamification->addPoints(5)` call:

```php
use App\Services\GamificationService;

public function completeTopic(LessonTopic $topic)
{
    $user = Auth::user();
    // ... existing security checks and markCompleted() call (unchanged) ...

    $gamificationService = app(GamificationService::class);

    // Award topic completion points (+10) and update streak
    $gamificationService->awardPoints($user, 'topic_complete', 10);
    $gamificationService->updateStreak($user);
    session()->flash('points_earned', ['points' => 10, 'reason' => 'topic complete']);

    // Check if all topics complete → award lesson bonus
    $allTopics     = $lesson->topics()->ordered()->get();
    $completedCount = \App\Models\LessonTopicProgress::where('user_id', $user->id)
        ->whereIn('lesson_topic_id', $allTopics->pluck('id'))
        ->where('completed', true)
        ->count();

    if ($completedCount === $allTopics->count()) {
        // Mark lesson as completed (existing logic — unchanged)
        UserProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['module_id' => $module->id, 'completed' => true, 'progress_percentage' => 100, 'completed_at' => now()]
        );
        // Award lesson complete bonus (+15)
        $gamificationService->awardPoints($user, 'lesson_complete', 15);
        session()->flash('points_earned', ['points' => 15, 'reason' => 'lesson complete']);
    }

    // ... existing redirect logic (unchanged) ...
}
```

Also update `complete()` method (marks lesson complete directly) to use `GamificationService` and award the correct 15 pts instead of old 10.

**Step 4: Run tests**

```bash
php artisan test --filter=LessonGamificationTest
php artisan test
```
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Learner/LessonController.php tests/Feature/Gamification/LessonGamificationTest.php
git commit -m "feat(gamification): wire LessonController to GamificationService, award 10pts topic / 15pts lesson"
```

---

## Task 8: `ShieldRefillController` + `StreakSaverController`

**Files:**
- Create: `app/Http/Controllers/Learner/ShieldRefillController.php`
- Create: `app/Http/Controllers/Learner/StreakSaverController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Gamification/ShieldRefillTest.php`

**Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Models\UserDailyShield;
use App\Models\UserGamification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShieldRefillTest extends TestCase
{
    use RefreshDatabase;

    private function learnerWithPoints(int $score): User
    {
        $user = User::factory()->create();
        $user->assignRole('learner');
        UserGamification::create([
            'user_id' => $user->id, 'level' => 1,
            'score' => $score, 'total_points' => $score, 'streak_count' => 0,
        ]);
        UserDailyShield::create(['user_id' => $user->id, 'shields_remaining' => 0, 'date' => today()]);
        return $user;
    }

    public function test_single_refill_costs_50_points_and_adds_one_shield(): void
    {
        $user = $this->learnerWithPoints(100);

        $this->actingAs($user)->post(route('learn.shields.refill'), ['type' => 'single']);

        $this->assertEquals(50, $user->gamification()->first()->score);
        $this->assertEquals(1, UserDailyShield::getShields($user));
    }

    public function test_full_refill_costs_100_points_and_restores_3_shields(): void
    {
        $user = $this->learnerWithPoints(150);

        $this->actingAs($user)->post(route('learn.shields.refill'), ['type' => 'full']);

        $this->assertEquals(50, $user->gamification()->first()->score);
        $this->assertEquals(3, UserDailyShield::getShields($user));
    }

    public function test_refill_fails_when_insufficient_points(): void
    {
        $user = $this->learnerWithPoints(30);

        $response = $this->actingAs($user)->post(route('learn.shields.refill'), ['type' => 'single']);

        $response->assertRedirect();
        $this->assertEquals(0, UserDailyShield::getShields($user)); // unchanged
        $this->assertEquals(30, $user->gamification()->first()->score); // unchanged
    }

    public function test_streak_saver_purchase_costs_75_points(): void
    {
        $user = $this->learnerWithPoints(150);

        $this->actingAs($user)->post(route('learn.streak-savers.buy'));

        $this->assertEquals(75, $user->gamification()->first()->score);
        $this->assertEquals(1, $user->gamification()->first()->streak_savers);
    }

    public function test_streak_saver_capped_at_3(): void
    {
        $user = $this->learnerWithPoints(500);
        $user->gamification()->update(['streak_savers' => 3]);

        $response = $this->actingAs($user)->post(route('learn.streak-savers.buy'));

        $response->assertRedirect();
        $this->assertEquals(3, $user->gamification()->first()->streak_savers); // unchanged
        $this->assertEquals(500, $user->gamification()->first()->score); // unchanged
    }
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=ShieldRefillTest
```

**Step 3: Add routes to `routes/web.php`** (inside the `learn` prefix + auth middleware group)

```php
Route::post('/shields/refill', [\App\Http\Controllers\Learner\ShieldRefillController::class, 'store'])->name('learn.shields.refill');
Route::post('/streak-savers/buy', [\App\Http\Controllers\Learner\StreakSaverController::class, 'store'])->name('learn.streak-savers.buy');
Route::get('/gamification', [\App\Http\Controllers\Learner\GamificationController::class, 'rules'])->name('learn.gamification');
```

**Step 4: Create `ShieldRefillController`**

```php
<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\UserDailyShield;
use App\Services\GamificationService;
use Illuminate\Http\Request;

class ShieldRefillController extends Controller
{
    public function store(Request $request, GamificationService $gamification)
    {
        $request->validate(['type' => 'required|in:single,full']);

        $user = auth()->user();
        $type = $request->input('type');
        $cost = $type === 'full' ? 100 : 50;

        if (!$gamification->spendPoints($user, $cost)) {
            return back()->with('error', 'Not enough points to refill shields.');
        }

        if ($type === 'full') {
            UserDailyShield::refillFull($user);
        } else {
            UserDailyShield::refillOne($user);
        }

        $remaining = UserDailyShield::getShields($user);
        session()->flash('shield_refilled', ['type' => $type, 'remaining' => $remaining]);

        return back()->with('success', $type === 'full'
            ? 'Full shield refill! You\'re back to 3 shields.'
            : '+1 Shield restored.');
    }
}
```

**Step 5: Create `StreakSaverController`**

```php
<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;

class StreakSaverController extends Controller
{
    public function store(GamificationService $gamification)
    {
        $user = auth()->user();
        $gamificationRecord = $user->gamification;

        if (!$gamificationRecord || $gamificationRecord->streak_savers >= 3) {
            return back()->with('error', 'You already have the maximum number of streak savers (3).');
        }

        if (!$gamification->spendPoints($user, 75)) {
            return back()->with('error', 'Not enough points to buy a streak saver.');
        }

        $gamificationRecord->increment('streak_savers');

        return back()->with('success', 'Streak Saver purchased! You now have ' . ($gamificationRecord->streak_savers + 1) . ' saver(s).');
    }
}
```

**Step 6: Run tests**

```bash
php artisan test --filter=ShieldRefillTest
php artisan test
```
Expected: PASS

**Step 7: Commit**

```bash
git add app/Http/Controllers/Learner/ShieldRefillController.php app/Http/Controllers/Learner/StreakSaverController.php routes/web.php tests/Feature/Gamification/ShieldRefillTest.php
git commit -m "feat(gamification): add ShieldRefillController and StreakSaverController with routes"
```

---

## Task 9: Update `DashboardController` — swap `QuizDailyLimit` references, pass new data

**Files:**
- Modify: `app/Http/Controllers/Learner/DashboardController.php`

**Step 1: Replace `QuizDailyLimit` with `UserDailyShield`**

```php
use App\Models\UserDailyShield;
// Remove: use App\Models\QuizDailyLimit;

// Replace the quiz attempts block:
$shieldsRemaining = UserDailyShield::getShields($user);
$maxShields       = UserDailyShield::MAX_SHIELDS;

// Streak active days this ISO week (for streak card weekly dots)
$streakActiveDays = \App\Models\LessonTopicProgress::where('user_id', $user->id)
    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
    ->get()
    ->map(fn($p) => (int) $p->created_at->dayOfWeek) // 0=Sun, 6=Sat
    ->unique()
    ->values()
    ->toArray();

$longestStreak = $gamification?->longest_streak ?? 0;
$streakSavers  = $gamification?->streak_savers ?? 0;
```

Add all new variables to the `compact()` call, replacing `quizAttemptsUsed/quizAttemptsRemaining/maxQuizAttempts`.

**Step 2: Run the full test suite**

```bash
php artisan test
```
Fix any view errors by updating the dashboard view's variable names in the next task.

**Step 3: Commit**

```bash
git add app/Http/Controllers/Learner/DashboardController.php
git commit -m "feat(gamification): update DashboardController to pass shields and streak data"
```

---

## Task 10: Shield SVG component

**Files:**
- Create: `resources/views/components/icons/shield.blade.php`

No test needed — it's a presentational component. Create directly:

```blade
{{-- Shield SVG icon. Props: state (full|empty|broken), size (default 24) --}}
@props(['state' => 'full', 'size' => 24])

@if($state === 'full')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="shield-full-grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#A30EB2;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#3B0CB1;stop-opacity:1" />
        </linearGradient>
    </defs>
    <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z"
          fill="url(#shield-full-grad)" stroke="none"/>
    <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>

@elseif($state === 'empty')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z"
          fill="#D1D5DB" opacity="0.7" stroke="none"/>
</svg>

@else {{-- broken --}}
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z"
          fill="#9CA3AF" opacity="0.5" stroke="none"/>
    <line x1="10" y1="8" x2="14" y2="16" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="14" y1="8" x2="10" y2="16" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round"/>
</svg>
@endif
```

**Commit:**

```bash
git add resources/views/components/icons/shield.blade.php
git commit -m "feat(gamification): add shield SVG Blade component with full/empty/broken states"
```

---

## Task 11: Update gamification panel — replace quiz attempts with shields

**Files:**
- Modify: `resources/views/components/learner/gamification-panel.blade.php`

**Step 1: Update props**

Replace `$quizAttemptsUsed`, `$quizAttemptsRemaining`, `$maxQuizAttempts` with `$shieldsRemaining`, `$maxShields = 3`.

**Step 2: Replace the "Quiz Attempts Today" section with shields**

```blade
{{-- ─── Shields today ─── --}}
<div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl mb-4 cursor-pointer"
     @click="$dispatch('open-shields-modal')">
    <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Shields Today</span>
    </div>
    <div class="flex items-center gap-1">
        @for($i = 0; $i < 3; $i++)
            <x-icons.shield :state="$i < $shieldsRemaining ? 'full' : 'empty'" :size="20" />
        @endfor
        @if(!$isPremium)
            <span class="text-xs font-bold text-purple-700 ml-1">{{ $shieldsRemaining }}/3</span>
        @else
            <span class="text-xs font-bold text-purple-700 ml-1">∞</span>
        @endif
    </div>
</div>
```

**Step 3: Commit**

```bash
git add resources/views/components/learner/gamification-panel.blade.php
git commit -m "feat(gamification): replace quiz attempts with shields in gamification panel"
```

---

## Task 12: Create Streak Card component

**Files:**
- Create: `resources/views/components/learner/streak-card.blade.php`

```blade
{{--
    Streak card — shows weekly activity dots, current/longest streak, and streak savers.
    Props: $gamification, $streakActiveDays (array of 0-6 int), $longestStreak, $streakSavers, $score
--}}
@props(['gamification', 'streakActiveDays' => [], 'longestStreak' => 0, 'streakSavers' => 0, 'score' => 0])

@php
    $currentStreak = $gamification?->streak_count ?? 0;
    $days = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
    $todayDow = (int) now()->dayOfWeek; // 0=Sun, 6=Sat
    $canBuySaver = $streakSavers < 3 && $score >= 75;
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
    {{-- Header --}}
    <div class="flex items-center gap-2 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-orange-500">
            <path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd" />
        </svg>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Your Streak</h3>
    </div>

    {{-- Weekly dots --}}
    <div class="flex justify-between mb-4">
        @foreach($days as $index => $label)
            @php $isActive = in_array($index, $streakActiveDays); $isToday = $index === $todayDow; @endphp
            <div class="flex flex-col items-center gap-1">
                <div class="w-9 h-9 rounded-full flex items-center justify-center
                    {{ $isToday ? 'ring-2 ring-offset-2 ring-purple-400' : '' }}
                    {{ $isActive ? 'text-white' : 'bg-gray-100 dark:bg-gray-700' }}"
                    @if($isActive) style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);" @endif>
                    @if($isActive)
                        <x-icons.shield state="full" :size="16" />
                    @endif
                </div>
                <span class="text-[10px] font-medium {{ $isToday ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400' }}">
                    {{ $label }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Current / Longest streak --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
            <div class="flex items-center justify-center gap-1 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-green-500">
                    <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] text-gray-500 dark:text-gray-400">Current</span>
            </div>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $currentStreak }}</p>
            <p class="text-[10px] text-gray-400">days</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
            <div class="flex items-center justify-center gap-1 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-orange-500">
                    <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] text-gray-500 dark:text-gray-400">Longest</span>
            </div>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $longestStreak }}</p>
            <p class="text-[10px] text-gray-400">days</p>
        </div>
    </div>

    {{-- Streak Savers --}}
    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Streak Savers</span>
            <div class="flex items-center gap-1">
                @for($i = 0; $i < 3; $i++)
                    <x-icons.shield :state="$i < $streakSavers ? 'full' : 'empty'" :size="16" />
                @endfor
                <span class="text-xs text-gray-500 ml-1">{{ $streakSavers }}/3</span>
            </div>
        </div>

        <form method="POST" action="{{ route('learn.streak-savers.buy') }}">
            @csrf
            <button type="submit"
                @if(!$canBuySaver) disabled @endif
                class="w-full text-xs font-semibold py-2 rounded-xl transition-all
                    {{ $canBuySaver
                        ? 'text-white hover:opacity-90'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500' }}"
                @if($canBuySaver) style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);" @endif
                title="{{ !$canBuySaver ? ($streakSavers >= 3 ? 'Already at max savers' : 'Not enough points (need 75)') : '' }}"
            >
                Buy Saver — ⭐ 75
            </button>
        </form>
    </div>
</div>
```

**Commit:**

```bash
git add resources/views/components/learner/streak-card.blade.php
git commit -m "feat(gamification): add streak card Blade component"
```

---

## Task 13: Add streak card to learner dashboard

**Files:**
- Modify: `resources/views/learner/dashboard.blade.php`

**Step 1: Find the right column section**

The dashboard right column currently contains `<x-learner.gamification-panel ...>`. Add the streak card directly below it:

```blade
<x-learner.gamification-panel
    :user="$user"
    :learnerProfile="$learnerProfile"
    :gamification="$gamification"
    :xpInLevel="$xpInLevel"
    :xpPercent="$xpPercent"
    :totalEnrolled="$totalEnrolled"
    :shieldsRemaining="$shieldsRemaining"
    :maxShields="$maxShields"
    :recentAchievements="$recentAchievements"
/>

{{-- Streak card --}}
<x-learner.streak-card
    :gamification="$gamification"
    :streakActiveDays="$streakActiveDays"
    :longestStreak="$longestStreak"
    :streakSavers="$streakSavers"
    :score="$gamification?->score ?? 0"
/>
```

**Step 2: Update props on gamification panel** (remove old quiz attempt props, add new shield props)

**Step 3: Commit**

```bash
git add resources/views/learner/dashboard.blade.php
git commit -m "feat(gamification): add streak card to learner dashboard right column"
```

---

## Task 14: Create out-of-shields modal component

**Files:**
- Create: `resources/views/components/learner/out-of-shields-modal.blade.php`

This Alpine.js modal is triggered by `session('out_of_shields')` on page load.

```blade
@props(['score' => 0])

<div
    x-data="{ open: {{ session('out_of_shields') ? 'true' : 'false' }} }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="background: rgba(0,0,0,0.5);"
>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden" @click.outside="open = false">
        {{-- Close button --}}
        <button @click="open = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="grid grid-cols-2">
            {{-- Left panel --}}
            <div class="p-6 flex flex-col items-center justify-center text-center border-r border-gray-100 dark:border-gray-700">
                <x-icons.shield state="broken" :size="64" class="mb-4 opacity-60" />
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">You're out of Shields</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    No shields left today. Spend points to keep going, or come back tomorrow.
                </p>
                {{-- 3 empty shield dots --}}
                <div class="flex gap-2 mb-2">
                    <x-icons.shield state="empty" :size="28" />
                    <x-icons.shield state="empty" :size="28" />
                    <x-icons.shield state="empty" :size="28" />
                </div>
                <p class="text-xs text-gray-400">Your points: <strong class="text-purple-600">⭐ {{ number_format($score) }}</strong></p>
            </div>

            {{-- Right panel --}}
            <div class="p-6 flex flex-col gap-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Refill Options</p>

                {{-- +1 Shield --}}
                <form method="POST" action="{{ route('learn.shields.refill') }}">
                    @csrf
                    <input type="hidden" name="type" value="single">
                    <button type="submit"
                        @if($score < 50) disabled @endif
                        class="w-full rounded-xl border-2 border-purple-200 dark:border-purple-700 p-4 text-left
                            {{ $score >= 50 ? 'hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20 cursor-pointer' : 'opacity-50 cursor-not-allowed' }}
                            transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">+1 Shield</p>
                                <p class="text-xs text-gray-500">One shield restored</p>
                            </div>
                            <span class="text-sm font-bold text-purple-600">⭐ 50</span>
                        </div>
                    </button>
                </form>

                {{-- Full Refill --}}
                <form method="POST" action="{{ route('learn.shields.refill') }}">
                    @csrf
                    <input type="hidden" name="type" value="full">
                    <button type="submit"
                        @if($score < 100) disabled @endif
                        class="w-full rounded-xl p-4 text-left text-white
                            {{ $score >= 100 ? 'hover:opacity-90 cursor-pointer' : 'opacity-50 cursor-not-allowed' }}
                            transition-all"
                        @if($score >= 100) style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);" @else style="background: #9CA3AF;" @endif>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold">Full Refill</p>
                                <p class="text-xs opacity-80">Back to 3 shields</p>
                            </div>
                            <span class="text-sm font-bold">⭐ 100</span>
                        </div>
                    </button>
                </form>

                @if($score < 50)
                    <p class="text-xs text-red-500 text-center">Not enough points. Complete more lessons to earn points!</p>
                @endif
            </div>
        </div>
    </div>
</div>
```

Add the modal to `layouts/learner-app.blade.php` (just before `</body>`):

```blade
<x-learner.out-of-shields-modal :score="auth()->user()?->gamification?->score ?? 0" />
```

**Commit:**

```bash
git add resources/views/components/learner/out-of-shields-modal.blade.php resources/views/layouts/learner-app.blade.php
git commit -m "feat(gamification): add out-of-shields modal component"
```

---

## Task 15: New gamification toast functions in `toast.js` + CSS

**Files:**
- Modify: `resources/js/toast.js`
- Modify: `resources/css/toast-custom.css`

**Step 1: Add to `toast.js`** (append after existing exports)

```js
// Shield SVG icon (inline, white)
const shieldIcon = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z" fill="white" opacity="0.9"/>
  <path d="M9 12L11 14L15 10" stroke="#A30EB2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>`;

const brokenShieldIcon = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z" fill="white" opacity="0.6"/>
  <line x1="10" y1="8" x2="14" y2="16" stroke="#fee2e2" stroke-width="1.5" stroke-linecap="round"/>
  <line x1="14" y1="8" x2="10" y2="16" stroke="#fee2e2" stroke-width="1.5" stroke-linecap="round"/>
</svg>`;

// Shield Lost
export function showShieldLost(remaining, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const duration = 5000;
    const config = {
        ...defaultConfig,
        ...options,
        duration,
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${brokenShieldIcon}</span><span class="toast-message"><strong>Shield lost!</strong> ${remaining} shield${remaining !== 1 ? 's' : ''} remaining today.</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-shield-lost',
        ariaLive: 'assertive',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Shield Refilled
export function showShieldRefilled(type, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const duration = 4000;
    const message = type === 'full'
        ? '<strong>Full shield refill!</strong> You\'re back to 3 shields.'
        : '<strong>+1 Shield restored.</strong>';
    const config = {
        ...defaultConfig,
        ...options,
        duration,
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${shieldIcon}</span><span class="toast-message">${message}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-shield-refill',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Points Earned (compact)
export function showPointsEarned(points, reason, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const duration = 3000;
    const starIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
    const config = {
        ...defaultConfig,
        ...options,
        duration,
        text: `<div class="toast-content toast-content-compact"><span class="toast-icon-wrapper" style="width:24px;height:24px;">${starIcon}</span><span class="toast-message">+${points} pts — ${reason}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-points',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Streak Milestone
export function showStreakMilestone(days, bonus, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const duration = 7000;
    const lightningIcon = `<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd"/></svg>`;
    const emoji = days >= 30 ? '🏆' : '🔥';
    const config = {
        ...defaultConfig,
        ...options,
        duration,
        text: `<div class="toast-content toast-content-large"><span class="toast-icon-wrapper toast-icon-celebration">${lightningIcon}</span><span class="toast-message">${emoji} <strong>${days}-day streak!</strong> +${bonus} points awarded.</span></div>`,
        gravity: 'top',
        position: 'center',
        offset: getToastOffset(),
        className: 'custom-toast toast-streak-milestone',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Streak Saved
export function showStreakSaved(streak, saversLeft, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const duration = 6000;
    const config = {
        ...defaultConfig,
        ...options,
        duration,
        text: `<div class="toast-content toast-content-large"><span class="toast-icon-wrapper toast-icon-celebration">${shieldIcon}</span><span class="toast-message">🛡 <strong>Streak Saved!</strong> Your ${streak}-day streak is protected. ${saversLeft} saver${saversLeft !== 1 ? 's' : ''} left.</span></div>`,
        gravity: 'top',
        position: 'center',
        offset: getToastOffset(),
        className: 'custom-toast toast-streak-saved',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}
```

**Step 2: Add to `toast-custom.css`**

```css
/* Shield Lost */
.toast-shield-lost {
    background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%) !important;
    min-width: 320px !important;
}

/* Shield Refill */
.toast-shield-refill {
    background: linear-gradient(135deg, #A30EB2 0%, #3B0CB1 100%) !important;
}

/* Points Earned — compact */
.toast-points {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
    padding: 10px 14px !important;
    font-size: 13px !important;
    min-width: 160px !important;
    max-width: 260px !important;
}

/* Streak Milestone — centered, large */
.toast-streak-milestone {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    padding: 20px 28px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    min-width: 350px !important;
    box-shadow: 0 20px 60px rgba(245, 158, 11, 0.4), 0 8px 16px rgba(0,0,0,0.2) !important;
    animation: achievementBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards !important;
}

/* Streak Saved — centered, brand gradient */
.toast-streak-saved {
    background: linear-gradient(135deg, #A30EB2 0%, #3B0CB1 100%) !important;
    padding: 18px 24px !important;
    font-size: 15px !important;
    min-width: 320px !important;
    animation: achievementBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards !important;
}
```

**Step 3: Wire toasts to session flash in `learner-app.blade.php`**

Add to the `@stack('scripts')` or a dedicated `<script>` block at the bottom of the layout:

```blade
@if(session('shield_lost'))
<script>
    import { showShieldLost } from '/resources/js/toast.js'; // adjust to your import path
    document.addEventListener('DOMContentLoaded', () => {
        showShieldLost({{ session('shield_lost.remaining') }});
    });
</script>
@endif
```

> **Use the existing pattern** your app already uses for session flash → JS toast. Search for how other flash messages trigger Toastify in the learner layout and replicate that pattern for each new flash key: `shield_lost`, `shield_refilled`, `points_earned`, `streak_milestone`, `streak_saved`.

**Step 4: Commit**

```bash
git add resources/js/toast.js resources/css/toast-custom.css
git commit -m "feat(gamification): add gamification toast functions and CSS classes"
```

---

## Task 16: Gamification rules page

**Files:**
- Create: `app/Http/Controllers/Learner/GamificationController.php`
- Create: `resources/views/learner/gamification/rules.blade.php`

**Step 1: Create the controller**

```php
<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\UserDailyShield;

class GamificationController extends Controller
{
    public function rules()
    {
        $user          = auth()->user();
        $gamification  = $user->gamification;
        $shieldsRemaining = UserDailyShield::getShields($user);

        return view('learner.gamification.rules', compact('gamification', 'shieldsRemaining'));
    }
}
```

**Step 2: Create the view** (`resources/views/learner/gamification/rules.blade.php`)

Extends `layouts.learner-app`. Sections:

```blade
@extends('layouts.learner-app')
@section('title', 'How It Works — Gamification')
@section('content')

{{-- Hero --}}
<div class="rounded-2xl overflow-hidden mb-8 relative"
     style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 20px 20px;"></div>
    <div class="relative z-10 p-8 text-center">
        <x-icons.shield state="full" :size="64" class="mx-auto mb-4" />
        <h1 class="text-3xl font-bold text-white mb-2">How ConciousConnections Rewards You</h1>
        <p class="text-purple-200 text-sm">Knowledge is your shield. Keep learning to stay protected.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Section A: Shields --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="border-l-4 border-purple-400 pl-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Your Shields</h2>
            <p class="text-xs text-gray-400">Your daily quiz protection</p>
        </div>
        <div class="flex gap-4 mb-4">
            <div class="text-center"><x-icons.shield state="full" :size="40" /><p class="text-xs mt-1 text-gray-500">Full</p></div>
            <div class="text-center"><x-icons.shield state="empty" :size="40" /><p class="text-xs mt-1 text-gray-500">Drained</p></div>
            <div class="text-center"><x-icons.shield state="broken" :size="40" /><p class="text-xs mt-1 text-gray-500">Broken</p></div>
        </div>
        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2 mb-4">
            <li>• You start each day with <strong>3 shields</strong></li>
            <li>• Each failed quiz drains <strong>1 shield</strong></li>
            <li>• Zero shields = you cannot proceed past a quiz</li>
            <li>• Shields reset at <strong>midnight</strong> each day</li>
            <li>• <strong>Premium</strong> users have unlimited shields</li>
        </ul>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-400 text-xs border-b border-gray-100 pb-2">
                <th class="pb-2">Refill</th><th class="pb-2 text-right">Cost</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                <tr><td class="py-2">+1 Shield</td><td class="py-2 text-right font-bold text-purple-600">⭐ 50</td></tr>
                <tr><td class="py-2">Full Refill (3 shields)</td><td class="py-2 text-right font-bold text-purple-600">⭐ 100</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Section B: Points --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="border-l-4 border-indigo-400 pl-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Earning Points</h2>
            <p class="text-xs text-gray-400">⭐ points = your learning currency</p>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-400 text-xs border-b border-gray-100 dark:border-gray-700 pb-2">
                <th class="pb-2">Action</th><th class="pb-2 text-right">Points</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach([
                    ['Complete a topic', '+10'],
                    ['Complete a lesson', '+15'],
                    ['Pass a quiz (≥70%)', '+25'],
                    ['Perfect quiz score (100%)', '+30'],
                    ['Fail a quiz (participation)', '+5'],
                    ['Complete a module', '+100'],
                    ['7-day streak milestone', '+50'],
                    ['30-day streak milestone', '+200'],
                ] as [$action, $pts])
                <tr>
                    <td class="py-2 text-gray-600 dark:text-gray-300">{{ $action }}</td>
                    <td class="py-2 text-right font-bold text-indigo-600">{{ $pts }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Section C: Streaks --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="border-l-4 border-orange-400 pl-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Streak Rules</h2>
            <p class="text-xs text-gray-400">Consistency builds knowledge</p>
        </div>
        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2 mb-4">
            <li>• <strong>What counts:</strong> Completing a lesson topic</li>
            <li>• Complete at least one topic per day to keep your streak</li>
            <li>• Missing a day resets your streak to 1</li>
            <li>• Your <strong>Longest Streak</strong> is saved forever</li>
        </ul>
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-3 mb-4">
            <p class="text-xs font-bold text-orange-700 dark:text-orange-400 mb-2">Milestone Bonuses</p>
            <div class="flex gap-4 text-sm">
                <div>🔥 <strong>7-day streak</strong> → +50 pts</div>
                <div>🏆 <strong>30-day streak</strong> → +200 pts</div>
            </div>
        </div>
        <p class="text-xs font-bold text-gray-500 mb-2">Streak Savers</p>
        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
            <li>• Hold up to <strong>3 streak savers</strong> at a time</li>
            <li>• Cost: <strong>⭐ 75 pts</strong> each</li>
            <li>• Auto-consumed if you miss a day — streak preserved silently</li>
        </ul>
    </div>

    {{-- Section D: Levels --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="border-l-4 border-green-400 pl-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Levels</h2>
            <p class="text-xs text-gray-400">Level = floor(spendable points ÷ 100) + 1</p>
        </div>
        <div class="space-y-2">
            @foreach(range(1, 10) as $lvl)
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">{{ $lvl }}</div>
                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full"
                         style="width: {{ min(100, ($gamification?->score ?? 0) / ($lvl * 100) * 100) }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);"></div>
                </div>
                <span class="text-xs text-gray-500 w-20 text-right">{{ ($lvl - 1) * 100 }}–{{ $lvl * 100 }} pts</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Learner/GamificationController.php resources/views/learner/gamification/rules.blade.php
git commit -m "feat(gamification): add gamification rules page"
```

---

## Task 17: Delete `QuizDailyLimit` model + clean up all remaining references

**Step 1: Search for all remaining references**

```bash
Select-String -Path "app/**/*.php","resources/**/*.blade.php" -Pattern "QuizDailyLimit" -Recurse
```

**Step 2: For each reference found**, replace with `UserDailyShield` equivalent.

**Step 3: Delete the old model**

```bash
Remove-Item app/Models/QuizDailyLimit.php
```

**Step 4: Run full test suite**

```bash
php artisan test
```
Expected: All PASS. Fix any remaining references.

**Step 5: Commit**

```bash
git add -A
git commit -m "refactor(gamification): remove QuizDailyLimit, replace all references with UserDailyShield"
```

---

## Task 18: Final verification

**Step 1: Run the full test suite**

```bash
php artisan test
```
Expected: All tests PASS, zero failures.

**Step 2: Clear and warm caches**

```bash
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

**Step 3: Manual smoke test checklist**
- [ ] Log in as free learner — see 3 shields in gamification panel
- [ ] Complete a lesson topic — see +10 pts toast, streak updated
- [ ] Fail a quiz — shield drains to 2, shield lost toast fires
- [ ] Fail quiz again to 1, then to 0 — out-of-shields modal appears
- [ ] Spend 50 pts to refill +1 shield — toast fires, shield count updates
- [ ] Spend 100 pts for full refill — back to 3 shields
- [ ] Buy streak saver from streak card — count shows 1/3
- [ ] Visit `/learn/gamification` — rules page renders correctly
- [ ] Confirm premium user sees ∞ shields and is never blocked

**Step 4: Final commit**

```bash
git add -A
git commit -m "feat(gamification): complete shield system, streak card, gamification service, rules page"
```
