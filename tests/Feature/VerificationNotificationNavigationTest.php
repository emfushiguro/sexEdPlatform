<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\ParentVerificationApprovedNotification;
use App\Notifications\RelationshipVerificationStatusNotification;
use App\Support\NotificationDeepLinkResolver;
use App\Support\NotificationPayloadNormalizer;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VerificationNotificationNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
        config(['app.url' => 'https://notification-test.ngrok-free.app']);
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme('https');
    }

    #[DataProvider('legacyRelationshipLinks')]
    public function test_mark_read_routes_legacy_relationship_links_for_the_authenticated_recipient(string $host, bool $guardianRecipient): void
    {
        $guardian = $this->recipient(true);
        $dependent = $this->recipient(false);
        $recipient = $guardianRecipient ? $guardian : $dependent;
        $notification = $this->notification($recipient, [
            'type' => 'guardian_relationship_verification_resubmission_required',
            'parent_child_account_id' => 42,
            'child_user_id' => $dependent->id,
            // Old notifications sometimes sent the guardian URL to the dependent too.
            'action_url' => $host.'/parent/relationship-verifications/42',
        ]);

        $this->actingAs($recipient)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect($guardianRecipient
                ? route('parent.relationship-verifications.show', 42)
                : route('learner.parent.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public static function legacyRelationshipLinks(): array
    {
        return [
            'localhost guardian' => ['http://localhost:8000', true],
            'loopback guardian' => ['http://127.0.0.1:8000', true],
            'localhost dependent' => ['http://localhost:8000', false],
            'loopback dependent' => ['http://127.0.0.1:8000', false],
        ];
    }

    #[DataProvider('learnerIdentityTypes')]
    public function test_mark_read_routes_legacy_learner_identity_links_to_current_status(string $type): void
    {
        $learner = $this->recipient(false);
        $notification = $this->notification($learner, [
            'type' => $type,
            'action_url' => 'http://localhost:8000/learner/identity/status',
        ]);

        $this->actingAs($learner)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route('learner.identity.status'));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame('/learner/identity/status', app(NotificationDeepLinkResolver::class)->resolve(
            $notification->data, '/fallback', $learner,
        ));
    }

    public static function learnerIdentityTypes(): array
    {
        return [
            'submitted' => ['learner_identity_submitted'],
            'approved' => ['learner_identity_approved'],
            'rejected' => ['learner_identity_rejected'],
        ];
    }

    public function test_learner_identity_recovery_preserves_admin_fallback_and_excludes_guardians(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $guardian = $this->recipient(true);
        $resolver = app(NotificationDeepLinkResolver::class);

        foreach (self::learnerIdentityTypes() as [$type]) {
            $payload = ['type' => $type, 'action_url' => 'http://127.0.0.1:8000/learner/identity/status'];
            $notification = $this->notification($admin, $payload);

            $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $admin));
            $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $guardian));
            $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback'));
            $this->actingAs($admin)
                ->get(route('admin.notifications.read', $notification->id))
                ->assertRedirect(route('admin.notifications.index'));

            $this->assertNotNull($notification->fresh()->read_at);
        }
    }

    public function test_missing_instructor_permission_does_not_break_other_admin_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        Permission::findByName('review instructor identities', 'web')->delete();
        $resolver = app(NotificationDeepLinkResolver::class);

        $this->assertSame(
            route('admin.parent-verifications.learners.show', 42, false),
            $resolver->resolve(['type' => 'learner_identity_submitted', 'verification_id' => 42], '/fallback', $admin),
        );
        $this->assertSame('/fallback', $resolver->resolve([
            'type' => 'instructor_identity_submitted',
            'instructor_identity_verification_id' => 42,
        ], '/fallback', $admin));
    }

    public function test_mark_read_keeps_an_existing_valid_internal_target(): void
    {
        $recipient = $this->recipient(false);
        $notification = $this->notification($recipient, ['action_url' => route('chat.page')]);

        $this->actingAs($recipient)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route('chat.page'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[DataProvider('adminVerificationLinks')]
    public function test_admin_mark_read_recovers_verification_targets_from_resource_ids(string $type, string $key, string $destination, array $parameters): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $notification = $this->notification($admin, [
            'type' => $type,
            $key => 42,
            'action_url' => 'http://localhost:8000/admin/parent-verifications',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notifications.read', $notification->id))
            ->assertRedirect(route($destination, $parameters));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame('http://localhost:8000/admin/parent-verifications', $notification->fresh()->data['action_url']);
    }

    public static function adminVerificationLinks(): array
    {
        return [
            'learner identity' => ['learner_identity_submitted', 'verification_id', 'admin.parent-verifications.learners.show', [42]],
            'guardian identity' => ['parent_verification_request_submitted', 'parent_user_id', 'admin.parent-verifications.parents.show', [42]],
            'relationship' => ['guardian_relationship_verification_submitted', 'parent_child_account_id', 'admin.parent-verifications.relationships.show', [42]],
            'dependent identity' => ['child_verification_request_submitted', 'parent_child_account_id', 'admin.parent-verifications.index', ['type' => 'children', 'status' => 'pending', 'focus' => 42]],
        ];
    }

    #[DataProvider('guardianStatusLinks')]
    public function test_guardian_notifications_without_a_target_open_the_relevant_status(string $type, string $destination): void
    {
        $guardian = $this->recipient(true);
        $notification = $this->notification($guardian, ['type' => $type]);

        $this->actingAs($guardian)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route($destination));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public static function guardianStatusLinks(): array
    {
        return [
            'submitted' => ['parent_verification_submitted', 'guardian.verification.status'],
            'rejected' => ['parent_verification_rejected', 'guardian.verification.status'],
            'dependent approved' => ['child_verification_approved', 'parent.children.index'],
            'dependent rejected' => ['child_verification_rejected', 'parent.children.index'],
        ];
    }

    public function test_admin_verification_recovery_does_not_accept_invalid_ids_or_an_applicant_context(): void
    {
        $resolver = app(NotificationDeepLinkResolver::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $learner = $this->recipient(false);

        foreach (self::adminVerificationLinks() as [$type, $key]) {
            foreach ([null, 0, -1, '../42', '42/extra', []] as $id) {
                $this->assertSame('/fallback', $resolver->resolve(['type' => $type, $key => $id], '/fallback', $admin));
            }
            if ($type !== 'learner_identity_submitted') {
                $this->assertSame('/fallback', $resolver->resolve(['type' => $type, $key => 42], '/fallback', $learner));
            }
        }
    }

    public function test_mark_read_cannot_read_another_recipients_notification(): void
    {
        $owner = $this->recipient(true);
        $other = $this->recipient(false);
        $notification = $this->notification($owner, [
            'type' => 'guardian_relationship_verification_approved',
            'parent_child_account_id' => 42,
            'child_user_id' => $other->id,
        ]);

        $this->actingAs($other)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    #[DataProvider('unsafeUrls')]
    public function test_mark_read_rejects_arbitrary_external_urls(string $url): void
    {
        $recipient = $this->recipient(false);
        $notification = $this->notification($recipient, ['action_url' => $url]);

        $this->actingAs($recipient)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route('learner.notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public static function unsafeUrls(): array
    {
        return [
            'external' => ['https://evil.example/path'],
            'protocol relative' => ['//evil.example/path'],
            'backslash authority' => ['/\\evil.example/path'],
            'non-http scheme' => ['ftp://notification-test.ngrok-free.app/path'],
            'header control character' => ["/\tevil.example/path"],
        ];
    }

    public function test_relationship_recovery_requires_explicit_recipient_context_and_a_resource_id(): void
    {
        $resolver = app(NotificationDeepLinkResolver::class);
        $payload = [
            'type' => 'guardian_relationship_verification_approved',
            'parent_child_account_id' => 42,
            'child_user_id' => 12,
            'action_url' => 'http://localhost/parent/relationship-verifications/42',
        ];
        $guardian = new User(['is_parent_registration' => true]);
        $guardian->id = 11;
        $this->actingAs($guardian);

        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback'));
        $this->assertSame('/parent/relationship-verifications/42', $resolver->resolve($payload, '/fallback', $guardian));

        foreach ([null, 0, -1, '../42', '42/extra', []] as $invalidId) {
            $payload['parent_child_account_id'] = $invalidId;
            $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $guardian));
        }
    }

    public function test_dependent_identity_takes_priority_over_guardian_registration(): void
    {
        $recipient = new User(['is_parent_registration' => true]);
        $recipient->id = 12;

        $this->assertSame('/learn/my-parent', app(NotificationDeepLinkResolver::class)->resolve([
            'type' => 'guardian_relationship_verification_rejected',
            'parent_child_account_id' => 42,
            'child_user_id' => 12,
            'action_url' => '/parent/relationship-verifications/42',
        ], '/fallback', $recipient));
    }

    public function test_relationship_payloads_store_relative_recipient_specific_links(): void
    {
        $guardian = new User(['is_parent_registration' => true]);
        $guardian->id = 11;
        $dependent = new User;
        $dependent->id = 12;
        $dependent->name = 'Dependent';
        $relationship = new ParentChildAccount;
        $relationship->forceFill([
            'id' => 42,
            'parent_user_id' => 11,
            'child_user_id' => 12,
            'relationship_type' => 'parent',
        ])->setRelation('child', $dependent);
        $notification = new RelationshipVerificationStatusNotification($relationship, 'approved');

        $this->assertSame('/parent/relationship-verifications/42', $notification->toDatabase($guardian)['action_url']);
        $this->assertSame('/learn/my-parent', $notification->toDatabase($dependent)['action_url']);
    }

    public function test_approval_payload_has_a_relative_continuation_and_keeps_its_signed_link(): void
    {
        $this->freezeTime();
        $guardian = $this->recipient(true);
        $notification = new ParentVerificationApprovedNotification;
        $payload = $notification->toArray($guardian);

        $this->assertSame(route('guardian.onboarding.show', [], false), $payload['action_url'] ?? null);
        $this->assertTrue(URL::hasValidSignature(\Illuminate\Http\Request::create($payload['approval_url'])));
        $this->assertSame($payload['approval_url'], $notification->toMail($guardian)->viewData['actionUrl']);
    }

    public function test_mark_read_uses_a_safe_continuation_for_a_legacy_stale_signed_approval(): void
    {
        $guardian = $this->recipient(true);
        $staleUrl = 'http://localhost:8000/parent/approval/'.$guardian->id.'/hash?expires=123&signature=old';
        $notification = $this->notification($guardian, [
            'type' => 'parent_verification_approved',
            'approval_url' => $staleUrl,
        ]);

        $this->actingAs($guardian)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route('guardian.onboarding.show'));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame($staleUrl, $notification->fresh()->data['approval_url']);
    }

    public function test_current_signed_approval_is_normalized_and_redirected_without_changes(): void
    {
        $guardian = $this->recipient(true);
        $signedUrl = URL::temporarySignedRoute('parent.verification.approval-link', now()->addDay(), [
            'id' => $guardian->id,
            'hash' => sha1($guardian->getEmailForVerification()),
        ]);
        $payload = ['type' => 'parent_verification_approved', 'approval_url' => $signedUrl];
        $notification = $this->notification($guardian, $payload);

        $this->assertSame($signedUrl, app(NotificationPayloadNormalizer::class)->normalize($payload)['action_url']);
        $this->actingAs($guardian)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect($signedUrl);
    }

    public function test_stale_signed_links_for_unknown_types_and_non_guardians_are_not_rewritten(): void
    {
        $resolver = app(NotificationDeepLinkResolver::class);
        $payload = ['approval_url' => 'http://localhost/parent/approval/11/hash?signature=old'];
        $dependent = new User(['is_parent_registration' => false]);
        $dependent->id = 12;

        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $dependent));
        $payload['type'] = 'parent_verification_approved';
        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $dependent));
    }

    private function recipient(bool $guardian): User
    {
        return User::factory()->create([
            'role' => 'learner',
            'is_parent_registration' => $guardian,
            'parent_verification_status' => $guardian ? 'approved' : null,
            'guardian_onboarding_status' => $guardian ? 'completed' : null,
        ]);
    }

    private function notification(User $recipient, array $payload): DatabaseNotification
    {
        return $recipient->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'verification.navigation',
            'data' => $payload,
        ]);
    }
}
