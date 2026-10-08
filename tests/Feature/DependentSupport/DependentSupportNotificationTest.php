<?php

namespace Tests\Feature\DependentSupport;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\DependentSupportInformationChangedNotification;
use App\Notifications\GuardianSupportAccessChangedNotification;
use App\Services\DependentSupportInformationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DependentSupportNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_create_update_and_remove_notify_dependent_after_commit(): void
    {
        [$guardian, $dependent, $relationship] = $this->setupRelationship();
        $service = app(DependentSupportInformationService::class);
        Notification::fake();

        $profile = $service->save($dependent, $guardian, [
            'relevant_health_considerations' => 'PRIVATE-SYNTHETIC-MARKER-42',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ], null);
        $this->commitAfterCommitCallbacks();

        $service->save($dependent, $guardian, [
            'relevant_health_considerations' => 'Updated marker',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ], $profile->fresh()->getRawOriginal('updated_at'));
        $this->commitAfterCommitCallbacks();

        $service->remove($profile->fresh(), $guardian, $profile->fresh()->getRawOriginal('updated_at'));
        $this->commitAfterCommitCallbacks();

        Notification::assertSentTo($dependent, DependentSupportInformationChangedNotification::class, 3);
        $this->assertTrue($relationship->fresh()->can_manage_support_information);
    }

    public function test_dependent_self_change_sends_no_duplicate_notification(): void
    {
        [, $dependent] = $this->setupRelationship();
        Notification::fake();

        app(DependentSupportInformationService::class)->save($dependent, $dependent, [
            'relevant_health_considerations' => 'Self-authored detail',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ], null);
        $this->commitAfterCommitCallbacks();

        Notification::assertNothingSent();
    }

    public function test_permission_changes_notify_only_the_affected_guardian_after_commit(): void
    {
        [$guardian, $dependent, $relationship] = $this->setupRelationship(false);
        Notification::fake();
        $service = app(DependentSupportInformationService::class);

        $service->setGuardianAccess($relationship, $dependent, true);
        $this->commitAfterCommitCallbacks();
        $service->setGuardianAccess($relationship, $dependent, false);
        $this->commitAfterCommitCallbacks();

        Notification::assertSentTo($guardian, GuardianSupportAccessChangedNotification::class, 2);
    }

    public function test_stale_content_transaction_sends_no_notification(): void
    {
        [$guardian, $dependent] = $this->setupRelationship();
        $service = app(DependentSupportInformationService::class);
        $profile = $service->save($dependent, $guardian, [
            'relevant_health_considerations' => 'Initial',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ], null);
        $this->commitAfterCommitCallbacks();
        Notification::fake();
        $staleVersion = $profile->getRawOriginal('updated_at');

        $profile->update(['relevant_health_considerations' => 'Newer']);

        try {
            $service->save($dependent, $guardian, [
                'relevant_health_considerations' => 'Stale',
                'accessibility_support_needs' => null,
                'additional_relevant_information' => null,
            ], $staleVersion);
            $this->fail('Expected stale save to fail.');
        } catch (ValidationException) {
            $this->commitAfterCommitCallbacks();
            Notification::assertNothingSent();
        }
    }

    public function test_registration_submission_notifies_new_dependent_without_content(): void
    {
        [$guardian, $dependent, $relationship] = $this->setupRelationship();
        Notification::fake();

        app(DependentSupportInformationService::class)->saveDuringRegistration($relationship, $guardian, [
            'relevant_health_considerations' => 'PRIVATE-SYNTHETIC-MARKER-42',
            'accessibility_support_needs' => 'accessibility_support_needs',
            'additional_relevant_information' => null,
        ]);
        $this->commitAfterCommitCallbacks();

        Notification::assertSentTo($dependent, DependentSupportInformationChangedNotification::class, function (DependentSupportInformationChangedNotification $notification) use ($dependent): bool {
            $payload = $notification->toDatabase($dependent);
            $serialized = json_encode($payload, JSON_THROW_ON_ERROR);

            return ! str_contains($serialized, 'PRIVATE-SYNTHETIC-MARKER-42')
                && ! str_contains($serialized, 'accessibility_support_needs')
                && $notification->via($dependent) === ['database'];
        });
    }

    public function test_notification_payloads_are_database_only_and_content_free(): void
    {
        $dependent = $this->dependent();
        $guardian = $this->guardian();
        $contentNotification = new DependentSupportInformationChangedNotification($guardian, 'updated');
        $accessNotification = new GuardianSupportAccessChangedNotification($dependent, true);

        foreach ([
            $contentNotification->toDatabase($dependent),
            $accessNotification->toDatabase($guardian),
        ] as $payload) {
            $serialized = json_encode($payload, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('PRIVATE-SYNTHETIC-MARKER-42', $serialized);
            $this->assertStringNotContainsString('accessibility_support_needs', $serialized);
            $this->assertStringNotContainsString($dependent->email, $serialized);
        }

        $this->assertSame(['database'], $contentNotification->via($dependent));
        $this->assertSame(['database'], $accessNotification->via($guardian));
    }

    /** @return array{0: User, 1: User, 2: ParentChildAccount} */
    private function setupRelationship(bool $permission = true): array
    {
        $guardian = $this->guardian();
        $dependent = $this->dependent();
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => 'biological_parent',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'verification_status' => 'approved',
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'can_manage_support_information' => $permission,
        ]);

        return [$guardian, $dependent, $relationship];
    }

    private function guardian(): User
    {
        return User::factory()->create([
            'name' => 'Guardian Sender',
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => false,
            'parent_verification_status' => 'approved',
        ]);
    }

    private function dependent(): User
    {
        return User::factory()->create([
            'name' => 'Dependent Receiver',
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function commitAfterCommitCallbacks(): void
    {
        DB::connection()->commit();
        DB::connection()->beginTransaction();
    }
}
