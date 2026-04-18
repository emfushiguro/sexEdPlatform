<?php

namespace Tests\Feature\Admin;

use App\Models\ParentChildAccount;
use App\Models\User;
use Tests\TestCase;

class AdminParentChildVerificationUiTest extends TestCase
{
    public function test_parent_tab_uses_server_side_status_filtering_for_pending_records(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $pendingParent = User::factory()->create([
            'first_name' => 'Pending',
            'last_name' => 'Guardian',
            'is_parent_registration' => true,
            'parent_verification_status' => 'pending',
            'parent_id_document_path' => 'parent-verifications/temp/pending-id.pdf',
        ]);
        $pendingParent->assignRole('learner');

        $approvedParent = User::factory()->create([
            'first_name' => 'Approved',
            'last_name' => 'Guardian',
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'parent_id_document_path' => 'parent-verifications/temp/approved-id.pdf',
        ]);
        $approvedParent->assignRole('learner');

        $rejectedParent = User::factory()->create([
            'first_name' => 'Rejected',
            'last_name' => 'Guardian',
            'is_parent_registration' => true,
            'parent_verification_status' => 'rejected',
            'parent_id_document_path' => 'parent-verifications/temp/rejected-id.pdf',
        ]);
        $rejectedParent->assignRole('learner');

        $response = $this->actingAs($admin)
            ->get(route('admin.parent-verifications.index', [
                'type' => 'parents',
                'status' => 'pending',
            ]));

        $response->assertOk()
            ->assertSee($pendingParent->full_name, false)
            ->assertDontSee($approvedParent->full_name, false)
            ->assertDontSee($rejectedParent->full_name, false);
    }

    public function test_child_tab_uses_server_side_status_filtering_for_pending_records(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $linkedParent = User::factory()->create([
            'first_name' => 'Linked',
            'last_name' => 'Parent',
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'parent_id_document_path' => 'parent-verifications/approved/linked-id.pdf',
        ]);
        $linkedParent->assignRole('learner');

        $pendingChild = User::factory()->create([
            'first_name' => 'QueuePending',
            'last_name' => 'Learner',
        ]);
        $pendingChild->assignRole('learner');

        $approvedChild = User::factory()->create([
            'first_name' => 'HiddenApproved',
            'last_name' => 'Learner',
        ]);
        $approvedChild->assignRole('learner');

        $rejectedChild = User::factory()->create([
            'first_name' => 'HiddenRejected',
            'last_name' => 'Learner',
        ]);
        $rejectedChild->assignRole('learner');

        ParentChildAccount::create([
            'parent_user_id' => $linkedParent->id,
            'child_user_id' => $pendingChild->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'verification_status' => 'pending',
            'verification_document_path' => 'child-verifications/temp/pending-doc.pdf',
        ]);

        ParentChildAccount::create([
            'parent_user_id' => $linkedParent->id,
            'child_user_id' => $approvedChild->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'verification_status' => 'approved',
            'verification_document_path' => 'child-verifications/temp/approved-doc.pdf',
        ]);

        ParentChildAccount::create([
            'parent_user_id' => $linkedParent->id,
            'child_user_id' => $rejectedChild->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'verification_status' => 'rejected',
            'verification_document_path' => 'child-verifications/temp/rejected-doc.pdf',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.parent-verifications.index', [
                'type' => 'children',
                'status' => 'pending',
            ]));

        $response->assertOk()
            ->assertSee($pendingChild->full_name, false)
            ->assertDontSee($approvedChild->full_name, false)
            ->assertDontSee($rejectedChild->full_name, false);
    }

    public function test_default_verification_view_surfaces_pending_child_requests_without_notification_context(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $linkedParent = User::factory()->create([
            'first_name' => 'Visible',
            'last_name' => 'Parent',
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'parent_id_document_path' => 'parent-verifications/approved/visible-parent-id.pdf',
        ]);
        $linkedParent->assignRole('learner');

        $pendingChild = User::factory()->create([
            'first_name' => 'Visible',
            'last_name' => 'PendingChild',
        ]);
        $pendingChild->assignRole('learner');

        ParentChildAccount::create([
            'parent_user_id' => $linkedParent->id,
            'child_user_id' => $pendingChild->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'verification_status' => 'pending',
            'verification_document_path' => 'child-verifications/temp/visible-pending-doc.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.parent-verifications.index'))
            ->assertOk()
            ->assertSee('Child Verifications', false)
            ->assertSee($pendingChild->full_name, false);
    }

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

    public function test_page_uses_unified_stats_and_standardized_pagination_controls(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.parent-verifications.index'));

        $response->assertOk()
            ->assertSee('Verifications Table', false)
            ->assertSee('Pending', false)
            ->assertSee('Approved', false)
            ->assertSee('Rejected', false)
            ->assertDontSee('Pending Parents', false)
            ->assertDontSee('Approved Parents', false)
            ->assertDontSee('Rejected Parents', false)
            ->assertDontSee('Pending Children', false)
            ->assertDontSee('Approved Children', false)
            ->assertDontSee('Rejected Children', false);
    }
}
