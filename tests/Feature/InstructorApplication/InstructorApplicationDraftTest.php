<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Models\User;
use App\Notifications\InstructorApplicationSubmitted;
use App\Services\Admin\RoleSyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorApplicationDraftTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['instructor_application.enabled' => true, 'instructor_application.identity_enforced_from' => now()->subDay()->toIso8601String()]);
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
    }

    public function test_stage_two_is_gated_until_stage_one_is_access_eligible(): void
    {
        $user = $this->applicant();

        $this->actingAs($user)->get(route('instructor-application.application.edit'))->assertRedirect(route('instructor-application.dashboard'));
        $this->actingAs($user)->post(route('instructor-application.application.save-draft'), [
            'bio' => 'not allowed yet',
        ])->assertForbidden();
    }

    public function test_approved_direct_identity_can_save_resume_and_submit_without_second_government_id(): void
    {
        $user = $this->applicant();
        $admin = User::factory()->create(['role' => 'admin']);
        app(RoleSyncService::class)->assignPrimaryRole($admin, 'admin');
        $identity = InstructorIdentityVerification::create([
            'user_id' => $user->id,
            'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
            'status' => InstructorIdentityVerification::STATUS_APPROVED,
            'submission_round' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($user)->get(route('instructor-application.application.edit'))
            ->assertOk()
            ->assertSee('already covered by your approved Stage One identity review');

        $this->actingAs($user)->post(route('instructor-application.application.save-draft'), [
            'bio' => 'Saved before final submission',
        ])->assertRedirect(route('instructor-application.application.edit'));
        Notification::assertNothingSent();

        $draft = InstructorApplication::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('draft', $draft->status);

        $this->actingAs($user)->post(route('instructor-application.application.submit'), [
            'clearance' => UploadedFile::fake()->create('clearance.pdf', 20, 'application/pdf'),
            'cv_resume' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            'educational_background' => 'college_graduate',
            'teaching_credential' => UploadedFile::fake()->create('teaching.pdf', 20, 'application/pdf'),
            'confirmation' => '1',
            'status' => 'approved',
        ])->assertRedirect(route('instructor-application.application.submitted'));

        $this->assertDatabaseHas('instructor_applications', [
            'id' => $draft->id,
            'status' => 'pending',
            'instructor_identity_verification_id' => $identity->id,
            'government_id_path' => null,
        ]);
        $this->assertDatabaseMissing('instructor_applications', ['status' => 'approved', 'user_id' => $user->id]);
        Notification::assertSentTo($admin, InstructorApplicationSubmitted::class);
    }

    public function test_existing_learner_requires_identity_after_cutover(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(24)->format('Y-m-d'),
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, 'learner');

        $this->actingAs($user)->get(route('instructor-application.application.edit'))->assertRedirect(route('instructor-application.dashboard'));
    }

    private function applicant(): User
    {
        $user = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'birthdate' => now()->subYears(24)->format('Y-m-d'),
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, User::ACCOUNT_TYPE_APPLICANT);

        return $user->fresh();
    }
}
