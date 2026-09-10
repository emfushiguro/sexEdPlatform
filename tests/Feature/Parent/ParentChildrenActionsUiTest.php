<?php

namespace Tests\Feature\Parent;

use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentChildrenActionsUiTest extends TestCase
{
    public function test_my_children_page_keeps_single_primary_action_and_concise_pending_rejected_regions(): void
    {
        $parent = $this->createApprovedParent();

        $approvedChild = $this->createChildForParent($parent, 'approved', 'Approved Child', 'Learner');
        $this->createChildForParent($parent, 'pending', 'Pending Child', 'Learner');
        $this->createChildForParent($parent, 'rejected', 'Rejected Child', 'Learner', 'Document is blurry.');

        $response = $this->actingAs($parent)->get(route('parent.children.index'));

        $response->assertOk()
            ->assertSee('View Dependent Dashboard', false)
            ->assertSee('Message '.$approvedChild->full_name, false)
            ->assertSee('open-global-chat', false)
            ->assertDontSee('>Manage</a>', false)
            ->assertDontSee('Quiz Results', false)
            ->assertSee('Pending verification', false)
            ->assertSee('Verification needs correction', false)
            ->assertDontSee('>Approved<', false);

        $approvedUrl = route('parent.children.show', $approvedChild->id);
        $this->assertSame(1, substr_count($response->getContent(), $approvedUrl));
    }

    public function test_my_children_page_shows_support_action_only_for_exact_verified_permission_relationship(): void
    {
        $parent = $this->createApprovedParent();
        $allowedChild = $this->createChildForParent($parent, 'approved', 'Allowed Child', 'Learner');
        $allowed = ParentChildAccount::query()->where('child_user_id', $allowedChild->id)->firstOrFail();
        $allowed->update([
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'can_manage_support_information' => true,
        ]);

        $deniedChild = $this->createChildForParent($parent, 'approved', 'Denied Child', 'Learner');
        $denied = ParentChildAccount::query()->where('child_user_id', $deniedChild->id)->firstOrFail();
        $denied->update([
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'can_manage_support_information' => false,
        ]);

        $response = $this->actingAs($parent)->get(route('parent.children.index'));

        $response->assertOk()
            ->assertSee(route('parent.children.support-information.edit', $allowedChild), false)
            ->assertDontSee(route('parent.children.support-information.edit', $deniedChild), false);
    }

    private function createApprovedParent(): User
    {
        $this->seedLocationRows();

        $parent = User::factory()->create([
            'first_name' => 'Action',
            'last_name' => 'Parent',
            'birthdate' => now()->subYears(30)->toDateString(),
            'email_verified_at' => now(),
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $parent->assignRole('learner');

        $parent->learnerProfile()->create([
            'username' => 'actionparent'.$parent->id,
            'birthdate' => now()->subYears(30)->toDateString(),
            'gender' => 'female',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'province_code' => '402100000',
            'is_parent_account' => true,
            'requires_parental_consent' => false,
        ]);

        return $parent;
    }

    private function createChildForParent(
        User $parent,
        string $status,
        string $firstName,
        string $lastName,
        ?string $rejectionReason = null
    ): User {
        $child = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birthdate' => now()->subYears(10)->toDateString(),
            'email_verified_at' => now(),
        ]);
        $child->assignRole('learner');

        $child->learnerProfile()->create([
            'username' => strtolower($firstName).$child->id,
            'birthdate' => now()->subYears(10)->toDateString(),
            'gender' => 'male',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'province_code' => '402100000',
            'is_parent_account' => false,
            'requires_parental_consent' => true,
        ]);

        ParentChildAccount::create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'verification_status' => $status,
            'verification_document_path' => 'child-verifications/temp/child-'.$child->id.'.pdf',
            'verification_rejection_reason' => $rejectionReason,
        ]);

        return $child;
    }

    private function seedLocationRows(): void
    {
        DB::table('provinces')->insert([
            'code' => '402100000',
            'name' => 'Sample Province',
            'region_code' => '040000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cities')->insert([
            'code' => '402101000',
            'name' => 'Sample City',
            'region_code' => '040000000',
            'province_code' => '402100000',
            'is_city' => true,
            'city_class' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('barangays')->insert([
            'code' => '402101001',
            'name' => 'Sample Barangay',
            'city_code' => '402101000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
