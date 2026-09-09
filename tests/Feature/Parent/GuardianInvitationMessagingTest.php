<?php

namespace Tests\Feature\Parent;

use App\Models\ParentChildInvitation;
use App\Models\User;
use App\Notifications\Learner\ParentChildInvitationReceivedNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

class GuardianInvitationMessagingTest extends TestCase
{
    public function test_invitation_detail_exposes_only_privacy_safe_context(): void
    {
        [$guardian, $child, $invitation] = $this->createInvitation();

        $response = $this->actingAs($child)
            ->get(route('parent.invitations.show', $invitation));

        $response->assertOk()
            ->assertSee($guardian->name)
            ->assertSee('Guardian identity administratively verified')
            ->assertSee('Claimed relationship')
            ->assertSee('View Guardian Information')
            ->assertSee('Message Guardian')
            ->assertDontSee($guardian->email)
            ->assertDontSee((string) $guardian->birthdate)
            ->assertDontSee('relationship_verification_documents')
            ->assertDontSee($child->email)
            ->assertDontSee((string) $child->birthdate);
    }

    public function test_unrelated_account_cannot_view_an_invitation(): void
    {
        [, , $invitation] = $this->createInvitation();
        $unrelated = User::factory()->create(['role' => 'learner']);

        $this->actingAs($unrelated)
            ->get(route('parent.invitations.show', $invitation))
            ->assertForbidden();
    }

    public function test_invitation_notification_contains_no_private_identity_or_evidence_fields(): void
    {
        [$guardian, $child, $invitation] = $this->createInvitation();
        $notification = new ParentChildInvitationReceivedNotification($invitation->load('inviterParent'));

        $payload = $notification->toDatabase(new AnonymousNotifiable());

        $this->assertSame($guardian->name, $payload['parent_name']);
        $this->assertSame('Grandmother', $payload['relationship']);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('birthdate', $payload);
        $this->assertArrayNotHasKey('avatar_path', $payload);
        $this->assertArrayNotHasKey('relationship_verification_documents', $payload);
        $this->assertArrayNotHasKey('message_contents', $payload);
        $this->assertSame($child->id, $invitation->child_user_id);
    }

    private function createInvitation(): array
    {
        $this->seedLocationRows();

        $guardian = User::factory()->create([
            'name' => 'Verified Guardian',
            'email' => 'guardian-private@example.test',
            'birthdate' => '1988-04-05',
            'role' => 'learner',
            'parent_verification_status' => 'approved',
            'is_parent_registration' => true,
        ]);
        $guardian->learnerProfile()->create([
            'username' => 'verifiedguardian'.$guardian->id,
            'birthdate' => '1988-04-05',
            'gender' => 'female',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'province_code' => '402100000',
            'avatar_path' => null,
            'is_parent_account' => true,
            'requires_parental_consent' => false,
        ]);

        $child = User::factory()->create([
            'name' => 'Existing Learner',
            'email' => 'learner-private@example.test',
            'birthdate' => '2012-06-07',
            'role' => 'learner',
        ]);
        $child->learnerProfile()->create([
            'username' => 'existinglearner'.$child->id,
            'birthdate' => '2012-06-07',
            'gender' => 'male',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'province_code' => '402100000',
            'is_parent_account' => false,
            'requires_parental_consent' => true,
        ]);

        $invitation = ParentChildInvitation::query()->create([
            'inviter_parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'grandmother',
            'invite_token' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'message' => 'A safe invitation context message.',
            'expires_at' => now()->addDays(14),
        ]);

        return [$guardian, $child, $invitation];
    }

    private function seedLocationRows(): void
    {
        \Illuminate\Support\Facades\DB::table('provinces')->insert([
            'code' => '402100000',
            'name' => 'Sample Province',
            'region_code' => '040000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('cities')->insert([
            'code' => '402101000',
            'name' => 'Sample City',
            'region_code' => '040000000',
            'province_code' => '402100000',
            'is_city' => true,
            'city_class' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('barangays')->insert([
            'code' => '402101001',
            'name' => 'Sample Barangay',
            'city_code' => '402101000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
