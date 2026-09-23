<?php

declare(strict_types=1);

namespace Tests\Feature\LearningPath;

use App\Models\LearningPath;
use App\Models\Module;
use App\Models\User;
use App\Services\LearningPathAuthoringService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class LearningPathAuthoringServiceTest extends TestCase
{
    protected bool $seed = false;

    private const LEARNER_TABLES = [
        'module_enrollments', 'module_purchases', 'user_progress',
        'lesson_topic_progress', 'interactive_activity_progress',
        'interactive_checkpoint_progress', 'quiz_attempts', 'certificates',
    ];

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Learning Path service tests require in-memory SQLite.');
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->softDeletes();
            $table->timestamps();
        });

        (require base_path('database/migrations/2026_09_23_000001_create_learning_path_tables.php'))->up();

        foreach (self::LEARNER_TABLES as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('module_id');
                $table->string('marker');
            });
        }
    }

    public function test_create_saves_path_categories_and_ordered_memberships(): void
    {
        $actor = $this->actor();
        [$first, $second] = $this->modules(2);

        $path = $this->service()->save(null, $this->attributes([$second->id, $first->id]), $actor);

        $this->assertSame($actor->id, $path->created_by);
        $this->assertSame('A path', $path->title);
        $this->assertSame(['teens'], $path->learnerCategories->pluck('category')->all());
        $this->assertSame([$second->id, $first->id], $path->pathModules->pluck('module_id')->all());
        $this->assertSame([1, 2], $path->pathModules->pluck('position')->all());
    }

    public function test_append_keeps_existing_membership_id_and_contiguous_positions(): void
    {
        [$path, $modules] = $this->pathWithModules(1);
        $oldId = $path->pathModules()->first()->id;
        $new = $this->modules(1)[0];

        $saved = $this->service()->save($path, $this->attributes([$modules[0]->id, $new->id]), $path->creator);

        $this->assertSame([$modules[0]->id, $new->id], $saved->pathModules->pluck('module_id')->all());
        $this->assertSame([1, 2], $saved->pathModules->pluck('position')->all());
        $this->assertSame($oldId, $saved->pathModules->first()->id);
    }

    public function test_remove_and_reorder_preserve_learner_records_and_retained_membership_ids(): void
    {
        [$path, $modules] = $this->pathWithModules(3);
        [$first, $second, $third] = $modules;
        $ids = $path->pathModules()->pluck('id', 'module_id');
        $before = $this->seedLearnerRecords($second->id);
        $moduleBefore = DB::table('modules')->orderBy('id')->get()->toJson();

        $saved = $this->service()->save($path, $this->attributes([$third->id, $first->id]), $path->creator);

        $this->assertSame([$third->id, $first->id], $saved->pathModules->pluck('module_id')->all());
        $this->assertSame([1, 2], $saved->pathModules->pluck('position')->all());
        $this->assertSame([$ids[$third->id], $ids[$first->id]], $saved->pathModules->pluck('id')->all());
        $this->assertSame($moduleBefore, DB::table('modules')->orderBy('id')->get()->toJson());
        $this->assertLearnerRecordsUnchanged($before);
    }

    public function test_reorder_all_memberships_handles_unique_position_constraint(): void
    {
        [$path, $modules] = $this->pathWithModules(3);
        [$first, $second, $third] = $modules;
        $ids = $path->pathModules()->pluck('id', 'module_id');
        $before = $this->seedLearnerRecords($first->id);
        $moduleBefore = DB::table('modules')->orderBy('id')->get()->toJson();

        $saved = $this->service()->save($path, $this->attributes([$third->id, $first->id, $second->id]), $path->creator);

        $this->assertSame([$third->id, $first->id, $second->id], $saved->pathModules->pluck('module_id')->all());
        $this->assertSame([1, 2, 3], $saved->pathModules->pluck('position')->all());
        $this->assertSame([$ids[$third->id], $ids[$first->id], $ids[$second->id]], $saved->pathModules->pluck('id')->all());
        $this->assertSame($moduleBefore, DB::table('modules')->orderBy('id')->get()->toJson());
        $this->assertLearnerRecordsUnchanged($before);
    }

    public function test_categories_are_replaced_when_path_is_updated(): void
    {
        [$path, $modules] = $this->pathWithModules(1);
        $attributes = $this->attributes([$modules[0]->id]);
        $attributes['categories'] = ['kids', 'adults'];
        $attributes['title'] = 'Updated';

        $saved = $this->service()->save($path, $attributes, $path->creator);

        $this->assertSame('Updated', $saved->title);
        $this->assertEqualsCanonicalizing(['kids', 'adults'], $saved->learnerCategories->pluck('category')->all());
        $this->assertDatabaseMissing('learning_path_learner_categories', ['learning_path_id' => $path->id, 'category' => 'teens']);
    }

    public function test_empty_module_list_removes_only_memberships(): void
    {
        [$path, $modules] = $this->pathWithModules(1);
        $before = $this->seedLearnerRecords($modules[0]->id);

        $saved = $this->service()->save($path, $this->attributes([]), $path->creator);

        $this->assertCount(0, $saved->pathModules);
        $this->assertDatabaseHas('modules', ['id' => $modules[0]->id]);
        $this->assertLearnerRecordsUnchanged($before);
    }

    public function test_force_deleting_a_module_cascades_only_its_path_membership(): void
    {
        [$path, $modules] = $this->pathWithModules(2);
        [$deleted, $retained] = $modules;

        $deleted->forceDelete();

        $this->assertDatabaseMissing('modules', ['id' => $deleted->id]);
        $this->assertDatabaseMissing('learning_path_modules', [
            'learning_path_id' => $path->id,
            'module_id' => $deleted->id,
        ]);
        $this->assertDatabaseHas('learning_path_modules', [
            'learning_path_id' => $path->id,
            'module_id' => $retained->id,
        ]);
    }

    public function test_duplicate_module_ids_are_rejected_without_changes(): void
    {
        [$path, $modules] = $this->pathWithModules(1);
        $before = $this->pathSnapshot($path);

        try {
            $this->service()->save($path, $this->attributes([$modules[0]->id, $modules[0]->id]), $path->creator);
            $this->fail('Expected duplicate module validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('module_ids', $exception->errors());
        }

        $this->assertSame($before, $this->pathSnapshot($path));
    }

    public function test_failed_membership_insert_rolls_back_path_categories_and_memberships(): void
    {
        [$path, $modules] = $this->pathWithModules(1);
        $before = $this->pathSnapshot($path);
        $attributes = $this->attributes([$modules[0]->id, 999999]);
        $attributes['title'] = 'Must roll back';
        $attributes['categories'] = ['adults'];

        try {
            $this->service()->save($path, $attributes, $path->creator);
            $this->fail('Expected foreign key violation.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }

        $this->assertSame($before, $this->pathSnapshot($path));
    }

    private function service(): LearningPathAuthoringService
    {
        return app(LearningPathAuthoringService::class);
    }

    private function actor(): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create());
    }

    private function modules(int $count): array
    {
        $modules = [];
        for ($index = 0; $index < $count; $index++) {
            $modules[] = Module::withoutEvents(fn () => Module::create([
                'title' => "Module $index", 'description' => 'Description',
            ]));
        }

        return $modules;
    }

    private function pathWithModules(int $count): array
    {
        $actor = $this->actor();
        $modules = $this->modules($count);
        $path = $this->service()->save(null, $this->attributes(array_map(fn (Module $module): int => $module->id, $modules)), $actor);

        return [$path, $modules];
    }

    private function attributes(array $moduleIds): array
    {
        return [
            'title' => 'A path', 'description' => 'Description',
            'thumbnail' => null, 'status' => LearningPath::STATUS_DRAFT,
            'categories' => ['teens'], 'module_ids' => $moduleIds,
        ];
    }

    private function seedLearnerRecords(int $moduleId): array
    {
        foreach (self::LEARNER_TABLES as $table) {
            DB::table($table)->insert(['module_id' => $moduleId, 'marker' => $table]);
        }

        return $this->learnerSnapshot();
    }

    private function learnerSnapshot(): array
    {
        $snapshot = [];
        foreach (self::LEARNER_TABLES as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        return $snapshot;
    }

    private function assertLearnerRecordsUnchanged(array $before): void
    {
        $this->assertSame($before, $this->learnerSnapshot());
    }

    private function pathSnapshot(LearningPath $path): array
    {
        return [
            DB::table('learning_paths')->where('id', $path->id)->get()->toJson(),
            DB::table('learning_path_learner_categories')->where('learning_path_id', $path->id)->orderBy('id')->get()->toJson(),
            DB::table('learning_path_modules')->where('learning_path_id', $path->id)->orderBy('id')->get()->toJson(),
        ];
    }
}
