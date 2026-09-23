<?php

declare(strict_types=1);

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\LearningPath;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Services\LearningPathPresentationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LearningPathChangingContentTest extends TestCase
{
    protected bool $seed = false;

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Learning Path changing-content tests require in-memory SQLite.');
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->date('birthdate')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('learner_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('username');
            $table->date('birthdate')->nullable();
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
            $table->text('description')->nullable();
            $table->text('content')->nullable();
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
            $table->timestamps();
        });
        Schema::create('user_progress', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('lesson_id');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
        Schema::create('module_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->string('status')->default(EnrollmentStatus::Approved->value);
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->timestamps();
        });
        Schema::create('module_purchases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('module_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('PHP');
            $table->string('status')->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        (require base_path('database/migrations/2026_09_23_000001_create_learning_path_tables.php'))->up();
    }

    public function test_changed_visibility_omits_unapproved_modules_and_excludes_unavailable_nodes_from_progress(): void
    {
        $learner = $this->learner();
        $unpublished = $this->module('Later unpublished');
        $wrongCategory = $this->module('Wrong category', ['adults']);
        $historicalUnavailable = $this->module('Historical unavailable');
        $available = $this->module('Still available');
        $this->enroll($learner, $historicalUnavailable);
        $unpublished->update(['is_published' => false]);
        $historicalUnavailable->update(['is_published' => false]);
        $path = $this->pathWithModules([$unpublished, $wrongCategory, $historicalUnavailable, $available]);

        $view = $this->service()->presentFor($learner, $path);

        $this->assertSame([$historicalUnavailable->id, $available->id], collect($view['nodes'])->pluck('module.id')->all());
        $this->assertSame(['unavailable', 'recommended'], collect($view['nodes'])->pluck('state')->all());
        $this->assertFalse($view['nodes'][0]['is_current']);
        $this->assertSame($available->id, $view['current']['module']->id);
        $this->assertSame(1, $view['actionable_modules']);
        $this->assertSame(0, $view['completed_modules']);
        $this->assertSame(0, $view['progress_percentage']);
        $this->assertNull($view['nodes'][0]['action_url']);
    }

    public function test_approved_completed_history_remains_completed_after_category_and_publication_changes(): void
    {
        $learner = $this->learner();
        $historical = $this->module('Completed historical');
        $this->enroll($learner, $historical, ['completion_percentage' => 100]);
        $historical->syncLearnerCategories(['adults']);
        $historical->update(['is_published' => false]);
        $path = $this->pathWithModules([$historical]);

        $view = $this->service()->presentFor($learner, $path);

        $this->assertSame(['completed'], collect($view['nodes'])->pluck('state')->all());
        $this->assertSame(1, $view['completed_modules']);
        $this->assertSame(1, $view['actionable_modules']);
        $this->assertSame(100, $view['progress_percentage']);
        $this->assertFalse($view['nodes'][0]['is_current']);
        $this->assertNull($view['nodes'][0]['action_url']);
    }

    public function test_approved_incomplete_deactivated_module_is_unavailable_and_never_current(): void
    {
        $learner = $this->learner();
        $deactivated = $this->module('Deactivated in progress');
        $available = $this->module('Next available');
        $this->enroll($learner, $deactivated, ['completion_percentage' => 25]);
        $deactivated->update(['is_published' => false]);
        $path = $this->pathWithModules([$deactivated, $available]);

        $view = $this->service()->presentFor($learner, $path);

        $this->assertSame(['unavailable', 'recommended'], collect($view['nodes'])->pluck('state')->all());
        $this->assertFalse($view['nodes'][0]['is_current']);
        $this->assertSame($available->id, $view['current']['module']->id);
        $this->assertSame('This module is no longer available, but its history remains visible.', $view['nodes'][0]['reason']);
        $this->assertSame(1, $view['actionable_modules']);
    }

    public function test_presenter_query_count_does_not_scale_per_module(): void
    {
        $learner = $this->learner();
        Model::preventLazyLoading();

        try {
            $fiveModuleQueries = $this->queryCountForPathOfSize($learner, 5);
            $tenModuleQueries = $this->queryCountForPathOfSize($learner, 10);
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertLessThanOrEqual($fiveModuleQueries + 2, $tenModuleQueries);
    }

    private function service(): LearningPathPresentationService
    {
        return app(LearningPathPresentationService::class);
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
        LearnerProfile::create([
            'user_id' => $user->id,
            'username' => 'learner_'.$user->id,
            'birthdate' => now()->subYears(15)->toDateString(),
        ]);

        return $user->fresh(['learnerProfile']);
    }

    /** @param array<int, string> $categories */
    private function module(string $title, array $categories = ['teens']): Module
    {
        $module = Module::withoutEvents(fn (): Module => Module::create([
            'title' => $title,
            'description' => $title.' description',
            'min_age' => 13,
            'max_age' => 17,
            'is_published' => true,
            'current_review_status' => null,
            'content_owner_type' => 'admin',
            'access_type' => 'free',
        ]));
        $module->syncLearnerCategories($categories);

        return $module->fresh(['learnerCategories']);
    }

    /** @param array<int, Module> $modules */
    private function pathWithModules(array $modules): LearningPath
    {
        $path = LearningPath::create([
            'title' => 'Path '.fake()->unique()->word(),
            'description' => 'Path description',
            'status' => LearningPath::STATUS_PUBLISHED,
        ]);
        $path->learnerCategories()->create(['category' => 'teens']);
        foreach (array_values($modules) as $position => $module) {
            $path->pathModules()->create(['module_id' => $module->id, 'position' => $position + 1]);
        }

        return $path;
    }

    private function enroll(User $user, Module $module, array $attributes = []): ModuleEnrollment
    {
        return ModuleEnrollment::create(array_replace([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved->value,
            'enrolled_at' => now(),
            'completion_percentage' => 0,
        ], $attributes));
    }

    private function queryCountForPathOfSize(User $learner, int $size): int
    {
        $modules = [];
        for ($index = 1; $index <= $size; $index++) {
            $modules[] = $this->module('Bounded '.$size.' '.$index);
        }
        $path = $this->pathWithModules($modules);
        $path->unsetRelations();
        $learner->load('learnerProfile');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->service()->presentFor($learner, $path);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
