<?php

declare(strict_types=1);

namespace Tests\Feature\Learner;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\LessonTopicProgress;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Services\Chat\ChatSuggestionCatalog;
use App\Services\EntitlementService;
use App\Services\GamificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LearnerLearningPathJourneyTest extends TestCase
{
    protected bool $seed = false;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(EntitlementService::class, new class
        {
            public function canAccessFeature(): bool
            {
                return true;
            }
        });
        app()->instance(GamificationService::class, new class
        {
            public function shieldRefillCost(string $type): int
            {
                return $type === 'full' ? 100 : 25;
            }

            public function shieldFullRefillTarget(): int
            {
                return 5;
            }
        });
        app()->instance(ChatSuggestionCatalog::class, new class
        {
            public function forUser(): array
            {
                return [];
            }
        });
    }

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Learner Learning Path journey tests require in-memory SQLite.');
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('account_type')->nullable();
            $table->string('age_bracket_cached')->nullable();
            $table->boolean('is_parent_registration')->default(false);
            $table->string('parent_verification_status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('learner_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('username');
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('city_code')->nullable();
            $table->string('barangay_code')->nullable();
            $table->string('barangay')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_parent_account')->default(false);
            $table->boolean('requires_parental_consent')->default(false);
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('thumbnail')->nullable();
            $table->unsignedInteger('min_age')->nullable();
            $table->unsignedInteger('max_age')->nullable();
            $table->unsignedInteger('order')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->string('access_type')->nullable();
            $table->decimal('price_amount', 10, 2)->nullable();
            $table->string('price_currency')->nullable();
            $table->string('enrollment_mode')->nullable();
            $table->unsignedBigInteger('final_quiz_id')->nullable();
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->string('content_owner_type')->nullable();
            $table->string('current_review_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('module_learner_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('category');
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->integer('order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
        Schema::create('lesson_topics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->string('title');
            $table->string('type')->default('text');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        Schema::create('lesson_topic_progress', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lesson_topic_id');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('user_progress', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('lesson_id');
            $table->boolean('completed')->default(false);
            $table->integer('progress_percentage')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('module_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->string('status')->default('approved');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->timestamps();
        });
        Schema::create('module_purchases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        (require base_path('database/migrations/2026_01_06_111314_create_permission_tables.php'))->up();
        (require base_path('database/migrations/2026_09_23_000001_create_learning_path_tables.php'))->up();

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type')->nullable();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('instructor_applications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('parent_child_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_user_id');
            $table->unsignedBigInteger('child_user_id');
            $table->string('relationship_status')->nullable();
            $table->string('relationship_verified_status')->nullable();
            $table->timestamp('relationship_verified_at')->nullable();
            $table->string('verification_document_path')->nullable();
            $table->string('verification_status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('user_suspensions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
        Schema::create('user_gamifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('score')->default(0);
            $table->timestamps();
        });

        foreach (['admin', 'learner', 'instructor'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_journey_is_one_semantic_list_with_explicit_states_progress_and_continue_action(): void
    {
        $learner = $this->learner();
        $completed = $this->module('Completed module');
        $inProgress = $this->module('In progress module');
        $available = $this->module('Available module');
        $unavailable = $this->module('Unavailable module', ['is_published' => false]);
        $this->enroll($learner, $completed, ['completion_percentage' => 100, 'completed_at' => now()]);
        $this->enroll($learner, $inProgress);
        $lesson = Lesson::create([
            'module_id' => $inProgress->id,
            'title' => 'First incomplete lesson',
            'order' => 1,
            'is_published' => true,
        ]);
        $doneTopic = $this->topic($lesson, 'Done topic', 1);
        $this->topic($lesson, 'Open topic', 2);
        LessonTopicProgress::create([
            'user_id' => $learner->id,
            'lesson_topic_id' => $doneTopic->id,
            'completed' => true,
        ]);
        $this->enroll($learner, $unavailable);
        $path = $this->path('Healthy Connections Path', [$completed, $inProgress, $available, $unavailable], [
            'thumbnail' => 'learning-paths/healthy.jpg',
        ]);

        $response = $this->actingAs($learner)
            ->get(route('learner.learning-paths.show', $path))
            ->assertOk()
            ->assertSee('<ol', false)
            ->assertSee('aria-label="Learning path modules"', false)
            ->assertSee('Completed', false)
            ->assertSee('In Progress', false)
            ->assertSee('Available', false)
            ->assertSee('Unavailable', false)
            ->assertSee('Continue Learning', false)
            ->assertSee('Module Details', false)
            ->assertSee('aria-valuenow="50"', false)
            ->assertSee('aria-valuenow="33"', false)
            ->assertSee(route('learner.lessons.show', $lesson), false)
            ->assertSee('Continue lesson', false)
            ->assertSee('alt="Healthy Connections Path thumbnail"', false)
            ->assertSee('aria-current="step"', false)
            ->assertDontSee('Enroll in this path', false);

        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, '<ol'));
        $this->assertSame(4, substr_count($content, 'class="learning-path-node learning-path-node--'));
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'aria-hidden="true"'));
        $this->assertStringNotContainsString('learning-path-modal', $content);
        $this->assertStringNotContainsString('duolingo', strtolower($content));
        $this->assertStringNotContainsString('mascot', strtolower($content));
    }

    public function test_first_actionable_module_is_recommended_and_completed_path_has_review_copy(): void
    {
        $learner = $this->learner();
        $first = $this->module('Recommended first');
        $second = $this->module('Available second');
        $path = $this->path('Recommended Path', [$first, $second]);

        $this->actingAs($learner)
            ->get(route('learner.learning-paths.show', $path))
            ->assertOk()
            ->assertSee('Recommended Next', false)
            ->assertSee('Available', false);

        $completedPath = $this->path('Completed Path', [$first]);
        $this->enroll($learner, $first, ['completion_percentage' => 100, 'completed_at' => now()]);

        $this->actingAs($learner)
            ->get(route('learner.learning-paths.show', $completedPath))
            ->assertOk()
            ->assertSee('Path complete', false)
            ->assertSee('Review path', false)
            ->assertDontSee('Recommended Next', false);
    }

    public function test_journey_markup_has_accessible_responsive_and_reduced_motion_contract(): void
    {
        $component = (string) file_get_contents(dirname(__DIR__, 3).'\\resources\\views\\components\\learning-path\\module-node.blade.php');
        $view = (string) file_get_contents(dirname(__DIR__, 3).'\\resources\\views\\learner\\learning-paths\\show.blade.php');
        $css = (string) file_get_contents(dirname(__DIR__, 3).'\\resources\\css\\components.css');

        $this->assertStringContainsString('overflow-wrap-anywhere', $component);
        $this->assertStringContainsString('focus-visible', $component);
        $this->assertStringContainsString('aria-valuemin="0"', $component);
        $this->assertStringContainsString('aria-valuemax="100"', $component);
        $this->assertStringContainsString('min-h-11', $component);
        $this->assertStringContainsString('aria-hidden="true"', $component);
        $this->assertStringContainsString('<svg viewBox="0 0 24 40"', $component);
        $this->assertStringContainsString('aria-label="Learning path modules"', $view);
        $this->assertStringContainsString('overflow-x-hidden', $view);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('learning-path-node--current', $css);
        $this->assertStringContainsString('forced-colors: active', $css);
    }

    private function learner(): User
    {
        $user = User::withoutEvents(fn (): User => User::create([
            'name' => 'Teen Learner',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'learner',
            'status' => 'active',
        ]));
        $user->assignRole('learner');
        LearnerProfile::create([
            'user_id' => $user->id,
            'username' => 'learner_'.$user->id,
            'birthdate' => now()->subYears(15)->toDateString(),
            'city_code' => 'CITY-1',
            'barangay_code' => 'BARANGAY-1',
        ]);

        return $user->fresh(['learnerProfile']);
    }

    private function module(string $title, array $attributes = []): Module
    {
        $module = Module::create(array_replace([
            'title' => $title,
            'description' => $title.' description',
            'min_age' => 13,
            'max_age' => 17,
            'is_published' => true,
            'current_review_status' => null,
            'content_owner_type' => 'admin',
            'access_type' => 'free',
        ], $attributes));
        $module->syncLearnerCategories(['teens']);

        return $module->fresh(['learnerCategories']);
    }

    /** @param array<int, Module> $modules */
    private function path(string $title, array $modules, array $attributes = []): LearningPath
    {
        $path = LearningPath::create(array_replace([
            'title' => $title,
            'description' => $title.' description',
            'status' => LearningPath::STATUS_PUBLISHED,
        ], $attributes));
        $path->learnerCategories()->create(['category' => 'teens']);
        foreach ($modules as $position => $module) {
            $path->pathModules()->create(['module_id' => $module->id, 'position' => $position + 1]);
        }

        return $path->fresh(['learnerCategories', 'pathModules.module.learnerCategories']);
    }

    private function enroll(User $user, Module $module, array $attributes = []): ModuleEnrollment
    {
        return ModuleEnrollment::create(array_replace([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'approved',
            'enrolled_at' => now(),
            'completion_percentage' => 0,
        ], $attributes));
    }

    private function topic(Lesson $lesson, string $title, int $order): LessonTopic
    {
        return LessonTopic::create([
            'lesson_id' => $lesson->id,
            'title' => $title,
            'type' => 'text',
            'order' => $order,
        ]);
    }
}
