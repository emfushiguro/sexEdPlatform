<?php

declare(strict_types=1);

namespace Tests\Feature\LearningPath;

use App\Models\LearningPath;
use App\Models\LearningPathLearnerCategory;
use App\Models\LearningPathModule;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LearningPathSchemaTest extends TestCase
{
    protected bool $seed = false;

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Learning Path schema tests require an in-memory SQLite database.');
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
            $table->string('thumbnail')->nullable();
            $table->unsignedInteger('min_age')->nullable();
            $table->unsignedInteger('max_age')->nullable();
            $table->text('age_specific_content')->nullable();
            $table->unsignedInteger('order')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->string('enrollment_mode')->nullable();
            $table->unsignedBigInteger('final_quiz_id')->nullable();
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->unsignedBigInteger('published_by_admin_id')->nullable();
            $table->string('content_owner_type')->nullable();
            $table->string('current_review_status')->nullable();
            $table->unsignedInteger('certificate_pass_score')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $migration = base_path('database/migrations/2026_09_23_000001_create_learning_path_tables.php');
        if (is_file($migration)) {
            (require $migration)->up();
        }
    }

    public function test_catalog_tables_have_the_required_columns_and_types(): void
    {
        $this->assertTrue(Schema::hasColumns('learning_paths', [
            'id', 'title', 'description', 'thumbnail', 'status', 'created_by', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('learning_path_modules', [
            'id', 'learning_path_id', 'module_id', 'position', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('learning_path_learner_categories', [
            'id', 'learning_path_id', 'category', 'created_at', 'updated_at',
        ]));
        $this->assertSame('text', Schema::getColumnType('learning_paths', 'description'));
        $this->assertSame('varchar', Schema::getColumnType('learning_paths', 'status'));
        $this->assertSame('integer', Schema::getColumnType('learning_path_modules', 'position'));
        $this->assertSame('varchar', Schema::getColumnType('learning_path_learner_categories', 'category'));
    }

    public function test_pending_migration_repairs_existing_tables_without_losing_rows(): void
    {
        $path = LearningPath::factory()->create();
        LearningPathLearnerCategory::create([
            'learning_path_id' => $path->id,
            'category' => 'kids',
        ]);

        Schema::table('learning_path_learner_categories', function (Blueprint $table): void {
            $table->dropUnique('lplc_path_category_unique');
            $table->dropIndex('lplc_category_path_index');
        });

        (require base_path('database/migrations/2026_09_23_000001_create_learning_path_tables.php'))->up();

        $this->assertDatabaseHas('learning_path_learner_categories', [
            'learning_path_id' => $path->id,
            'category' => 'kids',
        ]);
        $this->assertTrue(Schema::hasIndex(
            'learning_path_learner_categories',
            'lplc_path_category_unique',
        ));
        $this->assertTrue(Schema::hasIndex(
            'learning_path_learner_categories',
            'lplc_category_path_index',
        ));
    }

    public function test_path_defaults_to_draft_and_creator_can_be_deleted(): void
    {
        $creator = User::withoutEvents(fn (): User => User::factory()->create());
        $path = LearningPath::factory()->create(['created_by' => $creator->id]);

        $this->assertSame(LearningPath::STATUS_DRAFT, $path->fresh()->status);
        $this->assertTrue($path->creator->is($creator));

        User::withoutEvents(fn () => $creator->forceDelete());

        $this->assertNull($path->fresh()->created_by);
    }

    public function test_foreign_keys_reference_catalog_parents_with_expected_delete_actions(): void
    {
        $foreignKeys = array_merge(
            Schema::getForeignKeys('learning_paths'),
            Schema::getForeignKeys('learning_path_modules'),
            Schema::getForeignKeys('learning_path_learner_categories'),
        );
        $references = array_map(
            fn (array $key): array => [$key['columns'], $key['foreign_table'], $key['foreign_columns'], $key['on_delete']],
            $foreignKeys,
        );

        $this->assertCount(4, $references);
        $this->assertContains([['created_by'], 'users', ['id'], 'set null'], $references);
        $this->assertContains([['learning_path_id'], 'learning_paths', ['id'], 'cascade'], $references);
        $this->assertContains([['module_id'], 'modules', ['id'], 'cascade'], $references);
    }

    public function test_path_module_membership_is_unique(): void
    {
        $path = LearningPath::factory()->create();
        $module = Module::factory()->create();
        LearningPathModule::create(['learning_path_id' => $path->id, 'module_id' => $module->id, 'position' => 1]);

        $this->expectException(QueryException::class);
        LearningPathModule::create(['learning_path_id' => $path->id, 'module_id' => $module->id, 'position' => 2]);
    }

    public function test_path_position_is_unique(): void
    {
        $path = LearningPath::factory()->create();
        LearningPathModule::create(['learning_path_id' => $path->id, 'module_id' => Module::factory()->create()->id, 'position' => 1]);

        $this->expectException(QueryException::class);
        LearningPathModule::create(['learning_path_id' => $path->id, 'module_id' => Module::factory()->create()->id, 'position' => 1]);
    }

    public function test_path_category_is_unique(): void
    {
        $path = LearningPath::factory()->create();
        LearningPathLearnerCategory::create(['learning_path_id' => $path->id, 'category' => 'kids']);

        $this->expectException(QueryException::class);
        LearningPathLearnerCategory::create(['learning_path_id' => $path->id, 'category' => 'kids']);
    }

    public function test_category_foreign_key_rejects_missing_path_parent(): void
    {
        $this->expectException(QueryException::class);
        LearningPathLearnerCategory::create(['learning_path_id' => 999999, 'category' => 'kids']);
    }

    public function test_membership_foreign_key_rejects_missing_module_parent(): void
    {
        $path = LearningPath::factory()->create();

        $this->expectException(QueryException::class);
        LearningPathModule::create([
            'learning_path_id' => $path->id,
            'module_id' => 999999,
            'position' => 1,
        ]);
    }

    public function test_memberships_and_categories_cascade_when_path_is_deleted(): void
    {
        $path = LearningPath::factory()->forCategories(['kids'])->create();
        $membership = LearningPathModule::create([
            'learning_path_id' => $path->id,
            'module_id' => Module::factory()->create()->id,
            'position' => 1,
        ]);

        $path->delete();

        $this->assertDatabaseMissing('learning_path_modules', ['id' => $membership->id]);
        $this->assertDatabaseMissing('learning_path_learner_categories', ['learning_path_id' => $path->id]);
    }

    public function test_relationships_return_modules_in_position_order_and_preserve_deleted_module(): void
    {
        $path = LearningPath::factory()->create();
        $first = Module::factory()->create();
        $second = Module::factory()->create();
        LearningPathModule::create(['learning_path_id' => $path->id, 'module_id' => $second->id, 'position' => 2]);
        LearningPathModule::create(['learning_path_id' => $path->id, 'module_id' => $first->id, 'position' => 1]);

        $this->assertSame([$first->id, $second->id], $path->fresh()->pathModules->pluck('module_id')->all());
        $this->assertSame([$first->id, $second->id], $path->fresh()->modules->pluck('id')->all());
        $this->assertSame([1, 2], $path->fresh()->modules->pluck('pivot.position')->all());

        $first->delete();
        $this->assertTrue($path->fresh()->pathModules->first()->module->trashed());
    }

    public function test_same_module_can_belong_to_two_paths(): void
    {
        $module = Module::factory()->create();
        $paths = LearningPath::factory()->count(2)->create();

        foreach ($paths as $path) {
            LearningPathModule::create(['learning_path_id' => $path->id, 'module_id' => $module->id, 'position' => 1]);
        }

        $this->assertCount(2, $module->fresh()->learningPathMemberships);
    }

    public function test_published_and_category_scopes_filter_paths(): void
    {
        $matching = LearningPath::factory()->published()->forCategories(['kids'])->create();
        LearningPath::factory()->published()->forCategories(['teens'])->create();
        LearningPath::factory()->forCategories(['kids'])->create();

        $this->assertSame([$matching->id], LearningPath::published()->forLearnerCategory('kids')->pluck('id')->all());
        $this->assertSame(['kids'], $matching->learnerCategories->pluck('category')->all());
    }
}
