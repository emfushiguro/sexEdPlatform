<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearnerIdentityRequirementTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_age_boundaries_use_birthdate_and_covered_children_are_held(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        $requirement = app(LearnerIdentityRequirement::class);

        foreach (['2013-09-24' => 'teen', '2009-09-25' => 'teen', '2008-09-24' => 'adult'] as $birthdate => $pathway) {
            $user = $this->learner($birthdate);
            $case = $requirement->createForNewLearner($user);
            $this->assertSame($pathway, $case->pathway);
            $this->assertNull($case->status);
            $this->assertSame(['created'], $case->audits()->pluck('action')->all());
            $this->assertSame($case->id, $requirement->current($user)->id);
        }

        $child = $this->learner('2013-09-25');
        $this->expectException(DomainException::class);
        $requirement->createForNewLearner($child);
    }

    public function test_legacy_learner_and_exempt_roles_have_no_requirement(): void
    {
        $requirement = app(LearnerIdentityRequirement::class);
        $this->assertNull($requirement->current($this->learner('2008-09-24')));
        $this->assertNull($requirement->current($this->learner('2008-09-24', ['is_parent_registration' => true])));
        $this->assertNull($requirement->current(User::factory()->create(['role' => 'instructor', 'birthdate' => '2008-09-24'])));
    }

    public function test_eighteenth_birthday_opens_fresh_adult_case_once(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        $teen = $this->learner('2008-09-25');
        $requirement = app(LearnerIdentityRequirement::class);
        $first = $requirement->createForNewLearner($teen);
        $first->update(['status' => 'approved', 'approved_at' => now()]);

        Carbon::setTestNow('2026-09-25 10:00:00');
        $adult = $requirement->current($teen->fresh());
        $this->assertSame('adult', $adult->pathway);
        $this->assertNull($adult->status);
        $this->assertNotNull($first->fresh()->superseded_at);
        $this->assertCount(2, $teen->identityVerifications()->get());
        $this->assertSame($adult->id, $requirement->current($teen->fresh())->id);
        $this->assertSame(1, $teen->identityVerifications()->whereNull('superseded_at')->count());
        $this->assertSame(['created', 'superseded'], $first->audits()->pluck('action')->all());
    }

    public function test_dob_corrections_reactivate_without_reusing_approval_or_evidence(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        Storage::fake('local');
        $user = $this->learner('2009-09-24');
        $requirement = app(LearnerIdentityRequirement::class);
        $teen = $requirement->createForNewLearner($user);
        $teen->update(['status' => 'approved', 'approved_at' => now()]);
        $this->addEvidence($teen, 'teen.jpg');

        $user->update(['birthdate' => '2008-09-24']);
        $adult = $requirement->current($user->fresh());
        $adult->update(['status' => 'approved', 'approved_at' => now()]);
        $this->addEvidence($adult, 'adult.jpg');

        $user->update(['birthdate' => '2009-09-24']);
        $reactivatedTeen = $requirement->current($user->fresh());
        $this->assertSame($teen->id, $reactivatedTeen->id);
        $this->assertNull($reactivatedTeen->status);
        $this->assertNull($reactivatedTeen->approved_at);
        $this->assertCount(0, $reactivatedTeen->evidence);
        $this->assertFalse(Storage::disk('local')->exists('teen.jpg'));

        $user->update(['birthdate' => '2008-09-24']);
        $reactivatedAdult = $requirement->current($user->fresh());
        $this->assertSame($adult->id, $reactivatedAdult->id);
        $this->assertNull($reactivatedAdult->status);
        $this->assertNull($reactivatedAdult->approved_at);
        $this->assertCount(0, $reactivatedAdult->evidence);
        $this->assertFalse(Storage::disk('local')->exists('adult.jpg'));
        $this->assertSame(1, $user->identityVerifications()->whereNull('superseded_at')->count());
        $this->assertCount(2, $user->identityVerifications()->get());
        $this->assertSame(['created', 'superseded', 'reactivated', 'superseded'], $teen->audits()->pluck('action')->all());
        $this->assertSame(['created', 'superseded', 'reactivated'], $adult->audits()->pluck('action')->all());
    }

    public function test_covered_learner_corrected_below_thirteen_is_held_without_exemption(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        $user = $this->learner('2013-09-24');
        $requirement = app(LearnerIdentityRequirement::class);
        $requirement->createForNewLearner($user);
        $user->update(['birthdate' => '2014-09-24']);

        $this->expectException(DomainException::class);
        $requirement->current($user->fresh());
    }

    private function learner(string $birthdate, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'learner', 'birthdate' => $birthdate], $attributes));
        $user->assignRole('learner');

        return $user;
    }

    private function addEvidence($case, string $path): void
    {
        Storage::disk('local')->put($path, 'private');
        $case->evidence()->create([
            'slot' => 'identity_front', 'storage_path' => $path, 'mime_type' => 'image/jpeg',
            'byte_size' => 7, 'width' => 100, 'height' => 100, 'submitted_at' => now(),
        ]);
    }
}
