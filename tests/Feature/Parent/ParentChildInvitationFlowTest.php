<?php

namespace Tests\Feature\Parent;

use App\Models\LearnerProfile;
use App\Models\ParentChildAccount;
use App\Models\ParentChildInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentChildInvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_parent_can_send_invitation_to_existing_learner(): void
    {
        $this->seedLocationRows();

        $parent = $this->createApprovedParent();
        $child = $this->createLearner('invitedchild', 12);

        $this->actingAs($parent)
            ->from(route('parent.invitations.index'))
            ->post(route('parent.invitations.store'), [
                'identifier' => $child->learnerProfile->username,
                'message' => 'Please accept this invitation so I can guide your learning progress.',
            ])
            ->assertRedirect(route('parent.invitations.index'))
            ->assertSessionHasNoErrors();

        $invitation = ParentChildInvitation::query()->first();

        $this->assertNotNull($invitation);
        $this->assertSame($parent->id, $invitation->inviter_parent_user_id);
        $this->assertSame($child->id, $invitation->child_user_id);
        $this->assertSame('pending', $invitation->status->value);

        $childNotification = $child->fresh()->notifications()->latest()->first();
        $this->assertNotNull($childNotification);
        $this->assertSame('parent_child_invitation_received', data_get($childNotification->data, 'type'));
    }

    public function test_child_can_accept_invitation_and_create_parent_link(): void
    {
        $this->seedLocationRows();

        $parent = $this->createApprovedParent();
        $child = $this->createLearner('acceptchild', 11);

        $invitation = ParentChildInvitation::query()->create([
            'inviter_parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'invite_token' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($child)
            ->post(route('parent.invitations.respond', $invitation), [
                'decision' => 'accept',
            ])
            ->assertRedirect(route('parent.invitations.show', $invitation));

        $invitation->refresh();
        $this->assertSame('accepted', $invitation->status->value);

        $this->assertDatabaseHas('parent_child_accounts', [
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'verification_status' => 'approved',
        ]);

        $link = ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->first();

        $this->assertNotNull($link?->relationship_verified_at);
        $this->assertTrue((bool) $link?->can_approve_content);
    }

    public function test_child_can_reject_invitation(): void
    {
        $this->seedLocationRows();

        $parent = $this->createApprovedParent();
        $child = $this->createLearner('rejectchild', 13);

        $invitation = ParentChildInvitation::query()->create([
            'inviter_parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'invite_token' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
        ]);

        $this->actingAs($child)
            ->post(route('parent.invitations.respond', $invitation), [
                'decision' => 'reject',
                'note' => 'I will keep my current setup.',
            ])
            ->assertRedirect(route('parent.invitations.show', $invitation));

        $invitation->refresh();
        $this->assertSame('rejected', $invitation->status->value);

        $this->assertDatabaseMissing('parent_child_accounts', [
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
        ]);
    }

    public function test_my_children_page_shows_outgoing_invitation_status(): void
    {
        $this->seedLocationRows();

        $parent = $this->createApprovedParent();
        $child = $this->createLearner('statuschild', 10);

        ParentChildInvitation::query()->create([
            'inviter_parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'invite_token' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.index'))
            ->assertOk()
            ->assertSee('Parent Link Invitations')
            ->assertSee($child->name)
            ->assertSee('Pending');
    }

    public function test_parent_cannot_invite_learner_outside_5_to_17_age_range(): void
    {
        $this->seedLocationRows();

        $parent = $this->createApprovedParent();
        $adultLearner = $this->createLearner('adultlearner', 20);

        $this->actingAs($parent)
            ->from(route('parent.invitations.index'))
            ->post(route('parent.invitations.store'), [
                'identifier' => $adultLearner->email,
            ])
            ->assertRedirect(route('parent.invitations.index'))
            ->assertSessionHasErrors(['identifier']);

        $this->assertDatabaseCount('parent_child_invitations', 0);
    }

    private function createApprovedParent(): User
    {
        $parent = User::factory()->create([
            'first_name' => 'Parent',
            'last_name' => 'Account',
            'birthdate' => now()->subYears(35)->toDateString(),
            'email_verified_at' => now(),
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'role' => 'learner',
        ]);
        $parent->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $parent->id,
            'username' => 'parent' . $parent->id,
            'birthdate' => now()->subYears(35)->toDateString(),
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

    private function createLearner(string $username, int $age): User
    {
        $learner = User::factory()->create([
            'first_name' => ucfirst($username),
            'last_name' => 'Learner',
            'birthdate' => now()->subYears($age)->toDateString(),
            'email_verified_at' => now(),
            'role' => 'learner',
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => $username . $learner->id,
            'birthdate' => now()->subYears($age)->toDateString(),
            'gender' => 'male',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'province_code' => '402100000',
            'is_parent_account' => false,
            'requires_parental_consent' => true,
        ]);

        return $learner;
    }

    private function seedLocationRows(): void
    {
        DB::table('provinces')->updateOrInsert(
            ['code' => '402100000'],
            [
                'name' => 'Sample Province',
                'region_code' => '040000000',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('cities')->updateOrInsert(
            ['code' => '402101000'],
            [
                'name' => 'Sample City',
                'region_code' => '040000000',
                'province_code' => '402100000',
                'is_city' => true,
                'city_class' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('barangays')->updateOrInsert(
            ['code' => '402101001'],
            [
                'name' => 'Sample Barangay',
                'city_code' => '402101000',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
