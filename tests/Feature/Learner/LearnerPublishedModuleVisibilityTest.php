<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class LearnerPublishedModuleVisibilityTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_learner_only_sees_approved_published_module_versions(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        $instructor = $this->createUserWithRole('instructor');
        $learner = $this->createLearner();

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'in_review',
            'min_age' => 13,
            'max_age' => 17,
            'title' => 'Pending Revision Title',
        ]);

        $approvedRevision = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => [
                'module' => [
                    'id' => $module->id,
                    'title' => 'Approved Title',
                    'description' => 'Approved description',
                    'thumbnail' => null,
                    'min_age' => 13,
                    'max_age' => 17,
                    'age_specific_content' => null,
                    'order' => $module->order,
                    'duration_minutes' => $module->duration_minutes,
                    'is_published' => true,
                    'is_premium' => false,
                    'enrollment_mode' => 'auto',
                    'final_quiz_id' => null,
                    'certificate_pass_score' => 70,
                    'created_by' => $instructor->id,
                    'content_owner_type' => 'instructor',
                ],
                'lessons' => [],
                'quizzes' => [],
            ],
            'submitted_by' => $instructor->id,
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subDay(),
            'reviewed_by' => $instructor->id,
        ]);

        $module->update([
            'published_revision_id' => $approvedRevision->id,
            'published_by_admin_id' => $instructor->id,
        ]);

        Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'draft',
            'min_age' => 13,
            'max_age' => 17,
            'title' => 'Invisible Draft',
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertSee('Approved Title', false)
            ->assertDontSee('Pending Revision Title', false)
            ->assertDontSee('Invisible Draft', false);

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee('Approved Title', false)
            ->assertDontSee('Pending Revision Title', false);
    }

    public function test_learner_can_access_published_approved_module_without_revision_snapshot(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        $instructor = $this->createUserWithRole('instructor');
        $learner = $this->createLearner();

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Approved Without Snapshot',
            'is_published' => true,
            'current_review_status' => 'approved',
            'published_revision_id' => null,
            'min_age' => 13,
            'max_age' => 17,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertSee('Approved Without Snapshot', false);

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee('Approved Without Snapshot', false);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function createLearner(): User
    {
        $learner = $this->createUserWithRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'learner_' . $learner->id,
            'birthdate' => now()->subYears(15)->toDateString(),
            'age_range' => 'adult_18_plus',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Bio',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        return $learner;
    }
}
