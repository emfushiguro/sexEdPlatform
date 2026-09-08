<?php

namespace Tests\Feature\Admin;

use App\Models\ParentChildAccount;
use App\Models\GuardianRelationshipVerificationAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserRelationshipMutationTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
    {
        $permissions = ['view users', 'manage user relationships'];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($permissions);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_can_attach_pending_relationship_and_detach_it_without_direct_verification_toggle(): void
    {
        Notification::fake();
        $admin = $this->createAdminUser();
        $parent = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $child = User::factory()->create(['role' => 'learner', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.users.relationships.attach'), [
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
                'relationship_type' => 'aunt',
                'can_view_progress' => 1,
                'can_view_quiz_answers' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('parent_child_accounts', [
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'aunt',
            'verification_pathway' => 'non_parental_care',
            'relationship_status' => 'pending',
            'relationship_verified_status' => 'pending',
            'can_approve_content' => false,
        ]);

        $this->actingAs($admin)
            ->patch('/admin/users/relationships/verification', [
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
                'is_verified' => true,
            ])
            ->assertNotFound();

        $relationship = ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.users.relationships.detach'), [
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('parent_child_accounts', [
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
        ]);
    }

    public function test_admin_attachment_restores_soft_deleted_pair_and_preserves_history(): void
    {
        Notification::fake();
        $admin = $this->createAdminUser();
        $parent = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $child = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'aunt',
            'verification_pathway' => 'non_parental_care',
            'relationship_status' => 'rejected',
            'relationship_verified_status' => 'rejected',
            'current_evidence_round' => 2,
        ]);
        $audit = GuardianRelationshipVerificationAudit::query()->create([
            'parent_child_account_id' => $relationship->id,
            'actor_user_id' => $admin->id,
            'action' => 'rejected',
            'previous_status' => 'under_review',
            'new_status' => 'rejected',
            'submission_round' => 2,
        ]);
        $relationship->delete();

        $this->actingAs($admin)
            ->post(route('admin.users.relationships.attach'), [
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
                'relationship_type' => 'aunt',
                'relationship_notes' => 'Restored for a new review round.',
            ])
            ->assertRedirect();

        $restored = ParentChildAccount::withTrashed()->findOrFail($relationship->id);
        $this->assertFalse($restored->trashed());
        $this->assertSame($relationship->id, $restored->id);
        $this->assertSame(2, $restored->current_evidence_round);
        $this->assertSame('pending', $restored->relationship_status);
        $this->assertSame('pending', $restored->relationship_verified_status);
        $this->assertTrue($restored->verificationAudits()->whereKey($audit->id)->exists());
    }

    public function test_admin_can_update_only_selected_relationship_permissions_and_audits_change(): void
    {
        $admin = $this->createAdminUser();
        $parent = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $child = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'aunt',
            'verification_pathway' => 'non_parental_care',
            'relationship_status' => 'active',
            'relationship_verified_status' => 'verified',
            'relationship_verified_at' => now(),
            'current_evidence_round' => 1,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.relationships.permissions'), [
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
                'can_view_progress' => false,
                'can_view_quiz_answers' => true,
                'can_approve_content' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('parent_child_accounts', [
            'id' => $relationship->id,
            'can_view_progress' => false,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
        ]);
        $this->assertDatabaseHas('guardian_relationship_verification_audits', [
            'parent_child_account_id' => $relationship->id,
            'actor_user_id' => $admin->id,
            'action' => 'permissions_updated',
            'previous_status' => 'verified',
            'new_status' => 'verified',
            'submission_round' => 1,
        ]);
    }
}
