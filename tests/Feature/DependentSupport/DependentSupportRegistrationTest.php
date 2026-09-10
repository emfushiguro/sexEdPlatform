<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DependentSupportRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_registration_page_explains_optional_purpose_and_is_not_cached(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();

        $response = $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship)])
            ->get(route('parent.create-child.support-information'));

        $response->assertOk();
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response
            ->assertSee('This is optional.')
            ->assertSee('not used for diagnosis, treatment, or Guardian-Dependent approval');
    }

    public function test_support_registration_saves_information_redirects_and_forgets_marker(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();
        $health = 'Needs a quiet space and predictable transitions.';

        $response = $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship)])
            ->post(route('parent.create-child.support-information.store'), [
                'has_relevant_support_information' => '1',
                'relevant_health_considerations' => $health,
                'accessibility_support_needs' => 'Written instructions help with participation.',
                'additional_relevant_information' => null,
                'purpose_acknowledged' => '1',
            ]);

        $response->assertRedirect(route('parent.create-child.done'))
            ->assertSessionHas('support_information_result', 'saved')
            ->assertSessionMissing('pending_child_support_setup');

        $profile = DependentSupportProfile::query()->where('dependent_user_id', $dependent->id)->sole();

        $this->assertSame($health, $profile->relevant_health_considerations);
        $this->assertNotSame($health, DB::table('dependent_support_profiles')->where('id', $profile->id)->value('relevant_health_considerations'));
        $this->assertSame($guardian->id, $profile->created_by_user_id);
    }

    public function test_support_registration_skip_creates_no_record_and_forgets_marker(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();

        $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship)])
            ->post(route('parent.create-child.support-information.skip'))
            ->assertRedirect(route('parent.create-child.done'))
            ->assertSessionHas('support_information_result', 'skipped')
            ->assertSessionMissing('pending_child_support_setup');

        $this->assertDatabaseMissing('dependent_support_profiles', [
            'dependent_user_id' => $dependent->id,
        ]);
    }

    public function test_submitted_support_text_is_not_serialized_into_session(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();
        $secret = 'PRIVATE_SUPPORT_NOTE_'.fake()->uuid();

        $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship)])
            ->post(route('parent.create-child.support-information.store'), [
                'has_relevant_support_information' => '1',
                'relevant_health_considerations' => $secret,
                'purpose_acknowledged' => '1',
            ])
            ->assertRedirect(route('parent.create-child.done'));

        $this->assertStringNotContainsString($secret, serialize(session()->all()));
    }

    public function test_expired_marker_is_denied_without_revealing_setup(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();

        $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship, now()->subSecond()->timestamp)])
            ->get(route('parent.create-child.support-information'))
            ->assertNotFound();
    }

    public function test_mismatched_marker_is_denied_without_revealing_setup(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();
        $otherDependent = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);

        $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => [
                ...$this->marker($dependent, $relationship),
                'dependent_user_id' => $otherDependent->id,
            ]])
            ->get(route('parent.create-child.support-information'))
            ->assertNotFound();
    }

    public function test_unrelated_guardian_cannot_use_setup_marker(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();
        $unrelated = $this->approvedGuardian();

        $this->actingAs($unrelated)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship)])
            ->get(route('parent.create-child.support-information'))
            ->assertNotFound();
    }

    /** @dataProvider unavailableRelationshipStatuses */
    public function test_unavailable_relationship_statuses_are_denied(string $status): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();
        $relationship->update(['relationship_status' => $status]);

        $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship)])
            ->get(route('parent.create-child.support-information'))
            ->assertNotFound();
    }

    public static function unavailableRelationshipStatuses(): array
    {
        return [
            'rejected' => [ParentChildAccount::STATUS_REJECTED],
            'revoked' => [ParentChildAccount::STATUS_REVOKED],
            'inactive' => [ParentChildAccount::STATUS_INACTIVE],
        ];
    }

    public function test_missing_marker_is_denied_without_revealing_existing_rows(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();

        $this->actingAs($guardian)
            ->get(route('parent.create-child.support-information'))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $dependent->id]);
        $this->assertDatabaseHas('parent_child_accounts', ['id' => $relationship->id]);
    }

    public function test_abandoning_optional_route_leaves_dependent_and_relationship_intact(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();

        $this->actingAs($guardian)
            ->withSession(['pending_child_support_setup' => $this->marker($dependent, $relationship)])
            ->get(route('parent.create-child.support-information'))
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $dependent->id]);
        $this->assertDatabaseHas('parent_child_accounts', [
            'id' => $relationship->id,
            'child_user_id' => $dependent->id,
        ]);
    }

    public function test_direct_done_request_clears_leftover_setup_marker(): void
    {
        [$guardian, $dependent, $relationship] = $this->supportSetup();

        $this->actingAs($guardian)
            ->withSession([
                'pending_child_support_setup' => $this->marker($dependent, $relationship),
                'child_created_name' => 'Child',
                'child_registration_result' => ['status' => 'pending'],
            ])
            ->get(route('parent.create-child.done'))
            ->assertOk()
            ->assertSessionMissing('pending_child_support_setup');
    }

    /** @return array{0: User, 1: User, 2: ParentChildAccount} */
    private function supportSetup(): array
    {
        $guardian = $this->approvedGuardian();
        $dependent = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $dependent->assignRole('learner');

        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => 'biological_parent',
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'current_evidence_round' => 0,
            'verification_status' => 'pending',
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'can_manage_support_information' => true,
        ]);

        return [$guardian, $dependent, $relationship];
    }

    private function approvedGuardian(): User
    {
        $guardian = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => false,
            'parent_verification_status' => 'approved',
        ]);
        $guardian->assignRole('learner');

        return $guardian;
    }

    /** @return array{dependent_user_id: int, parent_child_account_id: int, expires_at: int} */
    private function marker(User $dependent, ParentChildAccount $relationship, ?int $expiresAt = null): array
    {
        return [
            'dependent_user_id' => $dependent->id,
            'parent_child_account_id' => $relationship->id,
            'expires_at' => $expiresAt ?? now()->addMinutes(30)->timestamp,
        ];
    }
}
