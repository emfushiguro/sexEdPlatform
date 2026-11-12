<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureLearnerIdentityVerified;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_completion_no_longer_redirects_to_legacy_child_document_review(): void
    {
        $guardian = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'guardian_onboarding_status' => 'completed',
        ]);
        $teenager = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(15)->toDateString(),
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => null,
            'minor_account_activation_required' => false,
            'minor_account_activated_at' => now(),
        ]);
        $teenager->assignRole('learner');
        app(LearnerIdentityRequirement::class)->createForNewLearner($teenager)->update(['status' => 'approved']);
        ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $teenager->id,
            'relationship_type' => 'biological_mother',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'verification_status' => 'pending',
            'verification_document_path' => 'legacy/pending.png',
        ]);

        $this->actingAs($teenager)
            ->withoutMiddleware(EnsureLearnerIdentityVerified::class)
            ->get(route('learner.dashboard'))
            ->assertRedirect(route('profile.complete'));
    }
}
