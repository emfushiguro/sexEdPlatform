<?php

declare(strict_types=1);

namespace Tests\Feature\Learner;

use App\Models\LearnerProfile;
use App\Models\LearningPath;
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

class LearnerLearningPathIndexTest extends TestCase
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
            throw new RuntimeException('Learner Learning Path index tests require in-memory SQLite.');
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

    public function test_learner_sees_only_published_paths_for_their_existing_category(): void
    {
        $learner = $this->learner();
        $visible = $this->path('Teen Path', ['teens']);
        $this->path('Adult Path', ['adults']);
        $this->path('Draft Path', ['teens'], LearningPath::STATUS_DRAFT);

        $this->actingAs($learner)
            ->get(route('learner.learning-paths.index'))
            ->assertOk()
            ->assertSee($visible->title, false)
            ->assertDontSee('Adult Path', false)
            ->assertDontSee('Draft Path', false);
    }

    public function test_index_paginates_falls_back_for_missing_thumbnails_and_keeps_module_navigation(): void
    {
        $learner = $this->learner();
        $first = $this->path('First Path', ['teens'], LearningPath::STATUS_PUBLISHED, ['thumbnail' => 'learning-paths/first.jpg']);
        $this->path('Fallback Path', ['teens']);
        for ($index = 3; $index <= 13; $index++) {
            $this->path('Path '.$index, ['teens']);
        }

        $response = $this->actingAs($learner)
            ->get(route('learner.learning-paths.index'))
            ->assertOk()
            ->assertSee($first->title, false)
            ->assertSee('Learning path placeholder', false)
            ->assertSee('Learning Paths', false)
            ->assertSee('My Modules', false)
            ->assertSee(route('learner.modules.index'), false);

        $this->assertSame(12, $response->viewData('paths')->count());
        $this->assertSame(2, $response->viewData('paths')->lastPage());

        $this->actingAs($learner)
            ->get(route('learner.learning-paths.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Path 13', false);
    }

    public function test_index_shows_completion_summary_and_empty_state(): void
    {
        $learner = $this->learner();
        $module = $this->module('Completed module');
        $path = $this->path('Completed Path', ['teens'], LearningPath::STATUS_PUBLISHED, [], [$module]);
        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
            'completion_percentage' => 100,
            'completed_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.learning-paths.index'))
            ->assertOk()
            ->assertSee('1 of 1 modules completed', false)
            ->assertSee('100%', false);

        $this->assertSame(1, LearningPath::query()->count());
    }

    public function test_empty_state_links_to_the_existing_module_browser(): void
    {
        $learner = $this->learner();
        $this->path('Adult Only Path', ['adults']);

        $this->actingAs($learner)
            ->get(route('learner.learning-paths.index'))
            ->assertOk()
            ->assertSee('No learning paths yet', false)
            ->assertSee(route('learner.modules.index'), false);
    }

    public function test_draft_archived_and_wrong_category_paths_return_not_found(): void
    {
        $learner = $this->learner();
        $draft = $this->path('Draft Direct', ['teens'], LearningPath::STATUS_DRAFT);
        $archived = $this->path('Archived Direct', ['teens'], LearningPath::STATUS_ARCHIVED);
        $adult = $this->path('Adult Direct', ['adults']);

        $this->actingAs($learner)->get(route('learner.learning-paths.show', $draft))->assertNotFound();
        $this->actingAs($learner)->get(route('learner.learning-paths.show', $archived))->assertNotFound();
        $this->actingAs($learner)->get(route('learner.learning-paths.show', $adult))->assertNotFound();
    }

    public function test_authentication_and_profile_completion_still_guard_the_index(): void
    {
        $this->get(route('learner.learning-paths.index'))->assertRedirect(route('login'));

        $incomplete = $this->learner(['city_code' => null, 'barangay_code' => null]);

        $this->actingAs($incomplete)
            ->get(route('learner.learning-paths.index'))
            ->assertRedirect(route('profile.complete'));
    }

    private function learner(array $profile = []): User
    {
        $user = User::withoutEvents(fn (): User => User::create([
            'name' => 'Teen Learner',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'learner',
            'status' => 'active',
        ]));
        $user->assignRole('learner');
        LearnerProfile::create(array_replace([
            'user_id' => $user->id,
            'username' => 'learner_'.$user->id,
            'birthdate' => now()->subYears(15)->toDateString(),
            'city_code' => 'CITY-1',
            'barangay_code' => 'BARANGAY-1',
        ], $profile));

        return $user->fresh(['learnerProfile']);
    }

    /** @param array<int, User> $modules */
    private function path(string $title, array $categories, string $status = LearningPath::STATUS_PUBLISHED, array $attributes = [], array $modules = []): LearningPath
    {
        $path = LearningPath::create(array_replace([
            'title' => $title,
            'description' => $title.' description',
            'status' => $status,
        ], $attributes));
        foreach ($categories as $category) {
            $path->learnerCategories()->create(['category' => $category]);
        }
        foreach ($modules as $position => $module) {
            $path->pathModules()->create(['module_id' => $module->id, 'position' => $position + 1]);
        }

        return $path->fresh(['learnerCategories', 'pathModules.module']);
    }

    private function module(string $title): Module
    {
        $module = Module::create([
            'title' => $title,
            'description' => $title.' description',
            'min_age' => 13,
            'max_age' => 17,
            'is_published' => true,
            'current_review_status' => null,
            'content_owner_type' => 'admin',
            'access_type' => 'free',
        ]);
        $module->syncLearnerCategories(['teens']);

        return $module->fresh(['learnerCategories']);
    }
}
