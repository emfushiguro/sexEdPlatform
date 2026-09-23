<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\LearningPath;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use RuntimeException;
use Tests\TestCase;

class AdminLearningPathManagementTest extends TestCase
{
    protected bool $seed = false;

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Admin Learning Path tests require in-memory SQLite.');
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_parent_registration')->default(false);
            $table->string('parent_verification_status')->nullable();
            $table->timestamp('email_verified_at')->nullable();
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
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        (require base_path('database/migrations/2026_01_06_111314_create_permission_tables.php'))->up();
        (require base_path('database/migrations/2026_09_23_000001_create_learning_path_tables.php'))->up();
        Schema::create('module_learner_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('category');
            $table->timestamps();
        });

        foreach (['admin', 'instructor', 'learner'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        foreach (['view modules', 'create modules', 'edit modules', 'publish modules'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['instructor_applications', 'module_review_requests', 'parent_child_accounts', 'content_reports', 'payments', 'subscriptions', 'subscribers', 'subscription_plans', 'notifications', 'user_suspensions'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                if ($name === 'notifications') {
                    $table->uuid('id')->primary();
                } else {
                    $table->id();
                }
                $table->string('status')->nullable();
                if ($name === 'parent_child_accounts') {
                    $table->string('verification_document_path')->nullable();
                    $table->string('verification_status')->nullable();
                    $table->softDeletes();
                }
                if ($name === 'subscriptions') {
                    $table->timestamp('ends_at')->nullable();
                    $table->timestamp('end_date')->nullable();
                }
                if ($name === 'subscribers') {
                    $table->timestamp('ends_at')->nullable();
                    $table->timestamp('end_date')->nullable();
                    $table->softDeletes();
                }
                if ($name === 'user_suspensions') {
                    $table->unsignedBigInteger('user_id');
                    $table->timestamp('ends_at')->nullable();
                    $table->timestamp('starts_at')->nullable();
                }
                if ($name === 'subscription_plans') {
                    $table->boolean('is_active')->default(false);
                    $table->timestamp('archived_at')->nullable();
                }
                if ($name === 'notifications') {
                    $table->string('type')->nullable();
                    $table->string('notifiable_type')->nullable();
                    $table->unsignedBigInteger('notifiable_id')->nullable();
                    $table->text('data')->nullable();
                    $table->timestamp('read_at')->nullable();
                }
                if ($name === 'instructor_applications') {
                    $table->softDeletes();
                }
                $table->timestamps();
            });
        }
        Schema::create('admin_creator_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('public_display_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('affiliation')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('show_individual_attribution')->default(true);
            $table->timestamps();
        });
    }

    public function test_policy_maps_existing_module_permissions_for_a_non_admin_subject(): void
    {
        $user = $this->user('instructor');
        $path = LearningPath::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', LearningPath::class));
        $this->assertFalse(Gate::forUser($user)->allows('create', LearningPath::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', $path));
        $this->assertFalse(Gate::forUser($user)->allows('archive', $path));
        $this->assertFalse(Gate::forUser($user)->allows('publish', LearningPath::class));

        $user->givePermissionTo('view modules', 'create modules', 'edit modules', 'publish modules');
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', LearningPath::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $path));
        $this->assertTrue(Gate::forUser($user)->allows('create', LearningPath::class));
        $this->assertTrue(Gate::forUser($user)->allows('update', $path));
        $this->assertTrue(Gate::forUser($user)->allows('archive', $path));
        $this->assertTrue(Gate::forUser($user)->allows('publish', LearningPath::class));
    }

    public function test_admin_gate_override_and_route_middleware_keep_authoring_admin_only(): void
    {
        $admin = $this->user('admin');
        $path = LearningPath::factory()->create();
        $this->assertFalse($admin->hasPermissionTo('create modules'));
        $this->assertTrue(Gate::forUser($admin)->allows('create', LearningPath::class));
        $this->assertTrue(Gate::forUser($admin)->allows('publish', LearningPath::class));
        $this->actingAs($admin)->get(route('admin.learning-paths.index'))->assertOk();

        foreach (['learner', 'instructor'] as $role) {
            $user = $this->user($role);
            $user->givePermissionTo('view modules', 'create modules', 'edit modules', 'publish modules');
            $this->actingAs($user)->get(route('admin.learning-paths.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.learning-paths.store'), $this->payload())->assertForbidden();
            $this->actingAs($user)->patch(route('admin.learning-paths.archive', $path))->assertForbidden();
        }
    }

    public function test_draft_with_zero_modules_is_saved_and_published_path_requires_a_module(): void
    {
        $this->actingAs($this->user('admin'));
        $this->post(route('admin.learning-paths.store'), $this->payload())->assertRedirect(route('admin.learning-paths.index'));
        $this->assertDatabaseHas('learning_paths', ['title' => 'Healthy Connections', 'status' => 'draft']);
        $this->assertSame(0, LearningPath::query()->firstOrFail()->pathModules()->count());

        $this->post(route('admin.learning-paths.store'), $this->payload(['title' => 'Publish Me', 'status' => 'published']))
            ->assertInvalid(['module_ids']);
        $this->assertDatabaseMissing('learning_paths', ['title' => 'Publish Me']);
    }

    public function test_base_fields_and_thumbnail_are_validated_on_server(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('admin'));
        $this->post(route('admin.learning-paths.store'), $this->payload([
            'title' => '', 'description' => '', 'status' => 'unknown', 'categories' => [],
            'thumbnail' => UploadedFile::fake()->create('document.pdf', 30, 'application/pdf'),
        ]))->assertInvalid(['title', 'description', 'status', 'categories', 'thumbnail']);
        $this->post(route('admin.learning-paths.store'), $this->payload([
            'categories' => ['teens', 'teens'], 'module_ids' => null,
        ]))->assertInvalid(['categories.1', 'module_ids']);
        $this->post(route('admin.learning-paths.store'), $this->payload([
            'title' => str_repeat('x', 256), 'categories' => ['unknown'],
        ]))->assertInvalid(['title', 'categories.0']);
    }

    public function test_invalid_missing_deleted_unpublished_and_duplicate_modules_are_rejected(): void
    {
        $this->actingAs($this->user('admin'));
        $valid = $this->module(['teens']);
        $unpublished = $this->module(['teens'], ['is_published' => false]);
        $deleted = $this->module(['teens']);
        $deleted->delete();

        foreach ([[$valid->id, $valid->id], [999999], [$deleted->id], [$unpublished->id], ['abc']] as $ids) {
            $this->post(route('admin.learning-paths.store'), $this->payload(['module_ids' => $ids]))
                ->assertInvalid();
        }
        $this->assertSame(0, LearningPath::query()->count());
    }

    public function test_publishing_requires_eligible_modules_for_every_path_category(): void
    {
        $this->actingAs($this->user('admin'));
        $teensOnly = $this->module(['teens']);
        $this->post(route('admin.learning-paths.store'), $this->payload([
            'status' => 'published', 'categories' => ['teens', 'adults'], 'module_ids' => [$teensOnly->id],
        ]))->assertInvalid(['module_ids']);
        $this->assertDatabaseMissing('learning_paths', ['title' => 'Healthy Connections']);
    }

    public function test_platform_and_instructor_owned_visible_modules_can_be_selected_in_order(): void
    {
        $admin = $this->user('admin');
        $instructor = $this->user('instructor');
        $platform = $this->module(['teens'], ['created_by' => $admin->id, 'content_owner_type' => 'admin']);
        $instructorModule = $this->module(['teens'], ['created_by' => $instructor->id, 'content_owner_type' => 'instructor']);

        $this->actingAs($admin)->post(route('admin.learning-paths.store'), $this->payload([
            'status' => 'published', 'module_ids' => [$instructorModule->id, $platform->id],
        ]))->assertRedirect(route('admin.learning-paths.index'));
        $path = LearningPath::query()->firstOrFail();
        $this->assertSame('published', $path->status);
        $this->assertSame([$instructorModule->id, $platform->id], $path->pathModules->pluck('module_id')->all());
        $this->assertSame([1, 2], $path->pathModules->pluck('position')->all());
    }

    public function test_invalid_submission_preserves_selected_order_and_safe_old_input(): void
    {
        $this->actingAs($this->user('admin'));
        $first = $this->module(['teens']);
        $second = $this->module(['teens']);
        $this->from(route('admin.learning-paths.create'))->post(route('admin.learning-paths.store'), $this->payload([
            'title' => '', 'module_ids' => [$second->id, 999999, $first->id],
        ]))->assertRedirect(route('admin.learning-paths.create'));

        $response = $this->get(route('admin.learning-paths.create'));
        $response->assertOk()->assertSee('role="alert"', false)->assertSee('aria-invalid="true"', false);
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'name="module_ids[]" value="'.$first->id.'"'), strpos($html, 'name="module_ids[]" value="'.$second->id.'"'));
        $this->assertStringNotContainsString('name="module_ids[]" value="999999"', $html);
    }

    public function test_edit_persists_reordered_modules_and_archive_restore_only_change_status(): void
    {
        $admin = $this->user('admin');
        $first = $this->module(['teens']);
        $second = $this->module(['teens']);
        $this->actingAs($admin)->post(route('admin.learning-paths.store'), $this->payload(['module_ids' => [$first->id, $second->id]]));
        $path = LearningPath::query()->firstOrFail();

        $this->put(route('admin.learning-paths.update', $path), $this->payload([
            'title' => 'Revised', 'module_ids' => [$second->id, $first->id],
        ]))->assertRedirect(route('admin.learning-paths.index'));
        $path->refresh();
        $this->assertSame('Revised', $path->title);
        $this->assertSame([$second->id, $first->id], $path->pathModules->pluck('module_id')->all());
        $before = $path->pathModules->pluck('id')->all();

        $this->patch(route('admin.learning-paths.archive', $path))->assertRedirect();
        $this->assertSame('archived', $path->fresh()->status);
        $this->patch(route('admin.learning-paths.restore', $path))->assertRedirect();
        $this->assertSame('draft', $path->fresh()->status);
        $this->assertSame($before, $path->fresh()->pathModules->pluck('id')->all());
        $this->assertSame('Revised', $path->fresh()->title);
        $this->assertFalse(app('router')->has('admin.learning-paths.destroy'));
    }

    public function test_thumbnail_is_stored_and_replaced_on_update(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('admin'));
        $this->post(route('admin.learning-paths.store'), $this->payload([
            'thumbnail' => $this->fakeImage('first.png'),
        ]))->assertRedirect();
        $path = LearningPath::query()->firstOrFail();
        Storage::disk('public')->assertExists($path->thumbnail);
        $this->assertStringStartsWith('learning-paths/', $path->thumbnail);
        $original = $path->thumbnail;

        $this->put(route('admin.learning-paths.update', $path), $this->payload([
            'thumbnail' => $this->fakeImage('second.png'),
        ]))->assertRedirect();
        $path->refresh();
        $this->assertNotSame($original, $path->thumbnail);
        Storage::disk('public')->assertExists($path->thumbnail);
    }

    public function test_update_without_thumbnail_preserves_existing_thumbnail(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('admin'));
        $this->post(route('admin.learning-paths.store'), $this->payload([
            'thumbnail' => $this->fakeImage('existing.png'),
        ]))->assertRedirect();
        $path = LearningPath::query()->firstOrFail();
        $existing = $path->thumbnail;

        $this->put(route('admin.learning-paths.update', $path), $this->payload([
            'title' => 'Keep image',
        ]))->assertRedirect();

        $this->assertSame($existing, $path->fresh()->thumbnail);
        Storage::disk('public')->assertExists($existing);
    }

    private function user(string $role): User
    {
        $user = User::withoutEvents(fn (): User => User::factory()->create(['role' => $role, 'status' => 'active']));
        $user->assignRole($role);

        return $user;
    }

    private function module(array $categories, array $attributes = []): Module
    {
        $module = Module::factory()->create($attributes);
        $module->syncLearnerCategories($categories);

        return $module;
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'title' => 'Healthy Connections',
            'description' => 'A guided path.',
            'status' => LearningPath::STATUS_DRAFT,
            'categories' => ['teens'],
            'module_ids' => [],
        ], $changes);
    }

    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }
}
