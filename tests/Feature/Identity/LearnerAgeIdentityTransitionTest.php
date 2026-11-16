<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerAgeIdentityTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_pathway_transitions_preserve_historical_cases_and_evidence(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(17)->toDateString(),
            'email_verified_at' => now(),
        ]);
        $learner->assignRole('learner');
        $requirement = app(LearnerIdentityRequirement::class);
        $teenCase = $requirement->createForNewLearner($learner);
        $teenCase->evidence()->create([
            'slot' => 'identity_front',
            'storage_path' => 'historical/teen-front.jpg',
            'mime_type' => 'image/jpeg',
            'byte_size' => 100,
            'width' => 320,
            'height' => 320,
            'submitted_at' => now(),
        ]);

        $learner->forceFill(['birthdate' => now()->subYears(18)->toDateString()])->save();
        $adultCase = $requirement->current($learner->fresh());

        $this->assertNotSame($teenCase->id, $adultCase->id);
        $this->assertSame('adult', $adultCase->pathway);
        $this->assertNotNull($teenCase->fresh()->superseded_at);
        $this->assertDatabaseHas('learner_identity_evidence', [
            'verification_id' => $teenCase->id,
            'storage_path' => 'historical/teen-front.jpg',
        ]);

        $learner->forceFill(['birthdate' => now()->subYears(17)->toDateString()])->save();
        $newTeenCase = $requirement->current($learner->fresh());

        $this->assertNotSame($teenCase->id, $newTeenCase->id);
        $this->assertSame('teen', $newTeenCase->pathway);
        $this->assertSame(3, $learner->identityVerifications()->count());
    }

    public function test_approved_case_only_counts_for_the_current_live_pathway(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(17)->toDateString(),
            'email_verified_at' => now(),
        ]);
        $learner->assignRole('learner');
        $requirement = app(LearnerIdentityRequirement::class);
        $teenCase = $requirement->createForNewLearner($learner);
        $teenCase->update(['status' => 'approved']);

        $learner->forceFill(['birthdate' => now()->subYears(18)->toDateString()])->save();
        $adultCase = $requirement->current($learner->fresh());

        $this->assertSame('adult', $adultCase->pathway);
        $this->assertNull($adultCase->status);
        $this->assertSame('approved', $teenCase->fresh()->status);
    }
}
