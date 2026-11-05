<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Models\User;
use App\Services\Admin\RoleSyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorApplicationTwoPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'instructor_application.enabled' => true,
            'instructor_application.identity_enforced_from' => now()->subDay()->toIso8601String(),
        ]);
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
    }

    public function test_document_step_requires_missing_required_files(): void
    {
        $user = $this->approvedApplicant();

        $this->actingAs($user)
            ->post(route('instructor-application.application.documents.save'), [])
            ->assertSessionHasErrors(['clearance', 'cv_resume']);

        $this->assertDatabaseMissing('instructor_applications', ['user_id' => $user->id]);
    }

    public function test_document_step_saves_files_and_redirects_to_expertise(): void
    {
        $user = $this->approvedApplicant();

        $this->actingAs($user)
            ->post(route('instructor-application.application.documents.save'), [
                'clearance' => UploadedFile::fake()->create('clearance.pdf', 20, 'application/pdf'),
                'cv_resume' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('instructor-application.application.expertise'));

        $this->assertDatabaseHas('instructor_applications', [
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
    }

    public function test_document_step_accepts_webp_evidence_files(): void
    {
        $user = $this->approvedApplicant();

        $this->actingAs($user)
            ->post(route('instructor-application.application.documents.save'), [
                'clearance' => UploadedFile::fake()->create('clearance.webp', 20, 'image/webp'),
                'cv_resume' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('instructor-application.application.expertise'));

        $this->assertDatabaseHas('instructor_applications', [
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
    }

    public function test_expertise_requires_a_saved_document_draft(): void
    {
        $user = $this->approvedApplicant();

        $this->actingAs($user)
            ->get(route('instructor-application.application.expertise'))
            ->assertRedirect(route('instructor-application.application.edit'))
            ->assertSessionHas('status');
    }

    public function test_expertise_page_can_save_and_finally_submit_using_saved_documents(): void
    {
        $user = $this->approvedApplicant();
        $admin = User::factory()->create(['role' => 'admin']);
        app(RoleSyncService::class)->assignPrimaryRole($admin, 'admin');

        $this->actingAs($user)->post(route('instructor-application.application.documents.save'), [
            'clearance' => UploadedFile::fake()->create('clearance.pdf', 20, 'application/pdf'),
            'cv_resume' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('instructor-application.application.expertise'));

        $this->actingAs($user)->post(route('instructor-application.application.expertise.save-draft'), [
            'educational_background' => 'college_graduate',
            'bio' => 'Saved teaching background',
        ])->assertRedirect(route('instructor-application.application.expertise'));

        $this->actingAs($user)->post(route('instructor-application.application.submit'), [
            'educational_background' => 'college_graduate',
            'teaching_credential' => UploadedFile::fake()->create('teaching.webp', 20, 'image/webp'),
            'confirmation' => '1',
        ])->assertRedirect(route('instructor-application.application.submitted'));

        $this->assertDatabaseHas('instructor_applications', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('instructor-application.application.submitted'))
            ->assertOk()
            ->assertSee('data-testid="withdraw-application-trigger"', false)
            ->assertSee('data-testid="withdraw-application-dialog"', false)
            ->assertSee('Withdraw your application?');
    }

    private function approvedApplicant(): User
    {
        $user = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'birthdate' => now()->subYears(24)->format('Y-m-d'),
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, User::ACCOUNT_TYPE_APPLICANT);

        InstructorIdentityVerification::create([
            'user_id' => $user->id,
            'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
            'status' => InstructorIdentityVerification::STATUS_APPROVED,
            'submission_round' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        return $user->fresh();
    }
}
