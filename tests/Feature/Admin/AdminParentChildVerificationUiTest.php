<?php

namespace Tests\Feature\Admin;

use App\Models\ParentChildAccount;
use App\Models\User;
use Tests\TestCase;

class AdminParentChildVerificationUiTest extends TestCase
{
    public function test_verification_preview_details_use_standardized_copy_and_hide_removed_fields(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $parentApplicant = User::factory()->create([
            'first_name' => 'Pat',
            'last_name' => 'Guardian',
            'is_parent_registration' => true,
            'parent_verification_status' => 'pending',
            'parent_id_document_path' => 'parent-verifications/temp/pat-id.pdf',
        ]);
        $parentApplicant->assignRole('learner');

        $childParent = User::factory()->create([
            'first_name' => 'Mara',
            'last_name' => 'Parent',
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'parent_id_document_path' => 'parent-verifications/approved/mara-id.pdf',
        ]);
        $childParent->assignRole('learner');

        $childApplicant = User::factory()->create([
            'first_name' => 'Nico',
            'last_name' => 'Kid',
        ]);
        $childApplicant->assignRole('learner');

        ParentChildAccount::create([
            'parent_user_id' => $childParent->id,
            'child_user_id' => $childApplicant->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'verification_status' => 'pending',
            'verification_document_path' => 'child-verifications/temp/nico-birth-cert.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.parent-verifications.index'));

        $response->assertOk()
            ->assertSee('Parent Verification - '.$parentApplicant->full_name, false)
            ->assertSee('Child Verification - '.$childApplicant->full_name, false)
            ->assertSee('Verification Details', false)
            ->assertDontSee('Verification Transparency Details', false)
            ->assertDontSee('Reviewed At', false)
            ->assertDontSee('Document Type', false)
            ->assertDontSee('Parent Document Available', false);
    }

    public function test_parent_and_child_rows_render_shared_moderation_modal_landmarks(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $parentApplicant = User::factory()->create([
            'first_name' => 'Ari',
            'last_name' => 'Parent',
            'is_parent_registration' => true,
            'parent_verification_status' => 'pending',
            'parent_id_document_path' => 'parent-verifications/temp/ari-id.pdf',
        ]);
        $parentApplicant->assignRole('learner');

        $childParent = User::factory()->create([
            'first_name' => 'Lani',
            'last_name' => 'Guardian',
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'parent_id_document_path' => 'parent-verifications/approved/lani-id.pdf',
        ]);
        $childParent->assignRole('learner');

        $childApplicant = User::factory()->create([
            'first_name' => 'Mico',
            'last_name' => 'Kid',
        ]);
        $childApplicant->assignRole('learner');

        ParentChildAccount::create([
            'parent_user_id' => $childParent->id,
            'child_user_id' => $childApplicant->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'verification_status' => 'pending',
            'verification_document_path' => 'child-verifications/temp/mico-birth-cert.pdf',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.parent-verifications.index'));

        $response->assertOk()
            ->assertSee('data-testid="verification-moderation-modal-shell"', false)
            ->assertSee('data-testid="verification-rejection-form-fields"', false);
    }
}
