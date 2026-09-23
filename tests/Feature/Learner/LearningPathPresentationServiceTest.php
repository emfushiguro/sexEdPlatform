<?php

declare(strict_types=1);

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\InteractiveActivity;
use App\Models\InteractiveActivityProgress;
use App\Models\InteractiveCheckpointProgress;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\LessonTopicProgress;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\LearningPathPresentationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LearningPathPresentationServiceTest extends TestCase
{
    protected bool $seed = false;

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Learning Path presentation tests require in-memory SQLite.');
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
            $table->string('gender')->nullable();
            $table->string('city_code')->nullable();
            $table->string('barangay')->nullable();
            $table->string('barangay_code')->nullable();
            $table->string('municipality')->nullable();
            $table->string('age_range')->nullable();
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
            $table->integer('duration')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
        Schema::create('lesson_topics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->string('title');
            $table->string('type')->default('text');
            $table->integer('order')->default(0);
            $table->text('text_content')->nullable();
            $table->integer('duration')->nullable();
            $table->boolean('is_prerequisite')->default(false);
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
        Schema::create('interactive_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_topic_id');
            $table->string('placement');
            $table->string('block_uuid')->nullable();
            $table->string('activity_type')->nullable();
            $table->string('title')->nullable();
            $table->text('instructions')->nullable();
            $table->text('explanation')->nullable();
            $table->json('configuration')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
        Schema::create('interactive_activity_progress', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('interactive_activity_id');
            $table->unsignedInteger('activity_revision');
            $table->string('status')->default('in_progress');
            $table->json('working_state')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();
        });
        Schema::create('interactive_checkpoint_progress', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lesson_topic_id');
            $table->unsignedBigInteger('quiz_question_id')->nullable();
            $table->string('checkpoint_block_uuid')->nullable();
            $table->string('status')->default('not_attempted');
            $table->json('latest_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
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
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('PHP');
            $table->string('status')->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        (require base_path('database/migrations/2026_09_23_000001_create_learning_path_tables.php'))->up();
    }

    public function test_not_started_paths_recommend_the_first_actionable_module(): void
    {
        $learner = $this->learner();
        [$path, $modules] = $this->pathWithModules(3);

        $view = $this->service()->presentFor($learner, $path);

        $this->assertSame(['recommended', 'available', 'available'], collect($view['nodes'])->pluck('state')->all());
        $this->assertSame($modules[0]->id, $view['current']['module']->id);
        $this->assertSame(0, $view['progress_percentage']);
    }

    public function test_progress_uses_topic_completion_and_excludes_unavailable_incomplete_nodes(): void
    {
        $learner = $this->learner();
        $completed = $this->module('Completed');
        $inProgress = $this->module('In progress');
        $unavailable = $this->module('Unavailable', ['is_published' => false]);
        $this->enroll($learner, $completed, ['completion_percentage' => 100]);
        $this->enroll($learner, $inProgress);
        $this->enroll($learner, $unavailable);
        $this->topicsWithProgress($learner, $inProgress, 2, 1);
        [$path] = $this->pathWithModules(0, [$completed, $inProgress, $unavailable]);

        $view = $this->service()->presentFor($learner, $path);

        $this->assertSame(['completed', 'in_progress', 'unavailable'], collect($view['nodes'])->pluck('state')->all());
        $this->assertSame(50, $view['nodes'][1]['progress_percentage']);
        $this->assertSame(1, $view['completed_modules']);
        $this->assertSame(2, $view['actionable_modules']);
        $this->assertSame(50, $view['progress_percentage']);
        $this->assertFalse($view['nodes'][2]['is_current']);
    }

    public function test_checkpoint_and_interactive_completion_are_counted_with_instructional_topics(): void
    {
        $learner = $this->learner();
        $module = $this->module('Mixed completion');
        $this->enroll($learner, $module);
        [$lesson] = $this->lessons($module, 1);
        $ordinaryDone = $this->topic($lesson, 'text', 'Ordinary done');
        $this->topic($lesson, 'text', 'Ordinary open');
        $checkpoint = $this->topic($lesson, 'interactive_checkpoint', 'Checkpoint');
        $activityTopic = $this->topic($lesson, 'interactive', 'Activity');
        $activity = InteractiveActivity::create([
            'lesson_topic_id' => $activityTopic->id,
            'placement' => 'between_topics',
            'activity_type' => 'sequencing',
            'revision' => 1,
        ]);
        LessonTopicProgress::create(['user_id' => $learner->id, 'lesson_topic_id' => $ordinaryDone->id, 'completed' => true]);
        InteractiveCheckpointProgress::create([
            'user_id' => $learner->id,
            'lesson_topic_id' => $checkpoint->id,
            'status' => 'correct',
        ]);
        InteractiveActivityProgress::create([
            'user_id' => $learner->id,
            'interactive_activity_id' => $activity->id,
            'activity_revision' => 1,
            'status' => 'completed',
        ]);
        [$path] = $this->pathWithModules(0, [$module]);

        $view = $this->service()->presentFor($learner, $path);

        $this->assertSame(50, $view['nodes'][0]['progress_percentage']);
        $this->assertSame('in_progress', $view['nodes'][0]['state']);
    }

    public function test_lesson_fallback_and_continue_use_the_first_incomplete_published_lesson(): void
    {
        $learner = $this->learner();
        $module = $this->module('Lesson fallback');
        $this->enroll($learner, $module);
        [$first, $second] = $this->lessons($module, 2);
        UserProgress::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $first->id,
            'completed' => true,
        ]);
        [$path] = $this->pathWithModules(0, [$module]);

        $node = $this->service()->presentFor($learner, $path)['nodes'][0];

        $this->assertSame(50, $node['progress_percentage']);
        $this->assertSame(1, $node['completed_lessons']);
        $this->assertSame(2, $node['total_lessons']);
        $this->assertSame(route('learner.lessons.show', $second), $node['action_url']);
    }

    public function test_pending_rejected_paid_and_historical_access_use_safe_states(): void
    {
        $learner = $this->learner();
        $pending = $this->module('Pending');
        $rejected = $this->module('Rejected');
        $paid = $this->module('Paid', ['access_type' => 'paid', 'price_amount' => 100]);
        $historical = $this->module('Historical', ['is_published' => false]);
        $hidden = $this->module('Hidden', ['is_published' => false]);
        $this->enroll($learner, $pending, ['status' => EnrollmentStatus::Pending->value]);
        $this->enroll($learner, $rejected, ['status' => EnrollmentStatus::Rejected->value]);
        $this->enroll($learner, $historical);
        [$path] = $this->pathWithModules(0, [$pending, $rejected, $paid, $historical, $hidden]);

        $nodes = $this->service()->presentFor($learner, $path)['nodes'];

        $this->assertSame(['unavailable', 'unavailable', 'recommended', 'unavailable'], collect($nodes)->pluck('state')->all());
        $this->assertNotSame($hidden->id, collect($nodes)->pluck('module.id')->last());
        $this->assertStringContainsString('/learn/modules/'.$paid->id, $nodes[2]['action_url']);
    }

    public function test_completed_out_of_order_is_current_safe_and_all_complete_has_review_action(): void
    {
        $learner = $this->learner();
        $first = $this->module('First');
        $second = $this->module('Second');
        $this->enroll($learner, $first, ['completion_percentage' => 25]);
        $this->enroll($learner, $second, ['completion_percentage' => 100]);
        [$firstLesson] = $this->lessons($first, 2);
        UserProgress::create([
            'user_id' => $learner->id,
            'module_id' => $first->id,
            'lesson_id' => $firstLesson->id,
            'completed' => true,
        ]);
        [$path] = $this->pathWithModules(0, [$first, $second]);

        $view = $this->service()->presentFor($learner, $path);
        $this->assertSame(['in_progress', 'completed'], collect($view['nodes'])->pluck('state')->all());
        $this->assertTrue($view['nodes'][0]['is_current']);
        $this->assertFalse($view['nodes'][1]['is_current']);

        ModuleEnrollment::where('user_id', $learner->id)->where('module_id', $first->id)->update(['completion_percentage' => 100]);
        $complete = $this->service()->presentFor($learner, $path);
        $this->assertSame(100, $complete['progress_percentage']);
        $this->assertNull($complete['current']);
        $this->assertSame('Review path', $complete['action_label']);
    }

    public function test_summaries_are_keyed_by_path_and_preview_is_user_free(): void
    {
        $learner = $this->learner();
        [$firstPath] = $this->pathWithModules(0, [$this->module('First path')]);
        [$secondPath] = $this->pathWithModules(0, [$this->module('Second path')]);

        $summaries = $this->service()->summariesFor($learner, collect([$firstPath, $secondPath]));
        $preview = $this->service()->preview($firstPath);

        $this->assertArrayHasKey($firstPath->id, $summaries);
        $this->assertArrayHasKey($secondPath->id, $summaries);
        $this->assertSame('recommended', $preview['nodes'][0]['state']);
        $this->assertSame(0, $preview['progress_percentage']);
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

    private function module(string $title, array $attributes = []): Module
    {
        $module = Module::withoutEvents(fn (): Module => Module::create(array_replace([
            'title' => $title,
            'description' => $title.' description',
            'min_age' => 13,
            'max_age' => 17,
            'is_published' => true,
            'current_review_status' => null,
            'content_owner_type' => 'admin',
            'access_type' => 'free',
        ], $attributes)));
        $module->syncLearnerCategories(['teens']);

        return $module->fresh(['learnerCategories']);
    }

    /** @param array<int, Module> $modules */
    private function pathWithModules(int $count, array $modules = []): array
    {
        if ($modules === []) {
            for ($index = 1; $index <= $count; $index++) {
                $modules[] = $this->module('Module '.$index);
            }
        }

        $path = LearningPath::create([
            'title' => 'Path '.fake()->unique()->word(),
            'description' => 'Path description',
            'status' => LearningPath::STATUS_PUBLISHED,
        ]);
        $path->learnerCategories()->create(['category' => 'teens']);
        foreach (array_values($modules) as $position => $module) {
            $path->pathModules()->create(['module_id' => $module->id, 'position' => $position + 1]);
        }

        return [$path->fresh(['pathModules.module.learnerCategories']), $modules];
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

    /** @return array<int, Lesson> */
    private function lessons(Module $module, int $count): array
    {
        $lessons = [];
        for ($index = 1; $index <= $count; $index++) {
            $lessons[] = Lesson::create([
                'module_id' => $module->id,
                'title' => $module->title.' lesson '.$index,
                'description' => 'Lesson',
                'order' => $index,
                'is_published' => true,
            ]);
        }

        return $lessons;
    }

    private function topic(Lesson $lesson, string $type, string $title): LessonTopic
    {
        return LessonTopic::create([
            'lesson_id' => $lesson->id,
            'title' => $title,
            'type' => $type,
            'order' => 1,
        ]);
    }

    private function topicsWithProgress(User $user, Module $module, int $count, int $completed): void
    {
        [$lesson] = $this->lessons($module, 1);
        for ($index = 1; $index <= $count; $index++) {
            $topic = $this->topic($lesson, 'text', 'Topic '.$index);
            if ($index <= $completed) {
                LessonTopicProgress::create([
                    'user_id' => $user->id,
                    'lesson_topic_id' => $topic->id,
                    'completed' => true,
                ]);
            }
        }
    }
}
