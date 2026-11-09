<?php

namespace Tests\Feature\Onboarding;

use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\UserOnboardingGuideState;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class OnboardingGuideDomainTest extends TestCase
{
    public function test_onboarding_schema_and_relationships_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('onboarding_guides', [
            'guide_key', 'target_role', 'status', 'current_version_id',
            'created_by', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumns('onboarding_guide_versions', [
            'onboarding_guide_id', 'version_number', 'title', 'description',
            'video_disk', 'video_path', 'video_original_name', 'video_mime_type',
            'video_size', 'requires_recompletion', 'published_at', 'created_by',
        ]));
        $this->assertTrue(Schema::hasColumns('user_onboarding_guide_states', [
            'user_id', 'onboarding_guide_version_id', 'dismissed_at', 'completed_at',
        ]));

        $guide = OnboardingGuide::factory()->create();
        $version = OnboardingGuideVersion::factory()->for($guide, 'guide')->create();
        $guide->update(['current_version_id' => $version->id]);
        $state = UserOnboardingGuideState::factory()->for($version, 'version')->create();

        $this->assertTrue($guide->fresh()->currentVersion->is($version));
        $this->assertTrue($version->states->contains($state));
        $this->assertTrue($state->user->onboardingGuideStates->contains($state));
    }

    public function test_user_and_version_pair_is_unique(): void
    {
        $state = UserOnboardingGuideState::factory()->create();

        $this->expectException(QueryException::class);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $state->user_id,
            'onboarding_guide_version_id' => $state->onboarding_guide_version_id,
        ]);
    }

    public function test_permission_migration_is_idempotent_and_preserving(): void
    {
        $migration = require database_path(
            'migrations/2026_11_09_000002_add_manage_onboarding_guides_permission.php'
        );

        $migration->up();
        $migration->up();
        $migration->down();

        $this->assertSame(1, Permission::query()
            ->where('name', 'manage onboarding guides')
            ->where('guard_name', 'web')
            ->count());
        $this->assertTrue(Role::findByName('admin', 'web')
            ->hasPermissionTo('manage onboarding guides'));
    }
}
