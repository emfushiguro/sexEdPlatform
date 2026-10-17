<?php

namespace Tests\Feature\Chat;

use App\Enums\EnrollmentStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ChatAccessGateTest extends TestCase
{
    public static function supportedAccounts(): array
    {
        $cases = [];
        foreach (['admin', 'instructor', 'learner', 'parent'] as $role) {
            foreach (['legacy', 'stale_permission', 'missing_permission'] as $state) {
                $cases[$role.' '.$state] = [$role, $state];
            }
        }

        return $cases;
    }

    #[DataProvider('supportedAccounts')]
    public function test_supported_accounts_can_open_chat_and_load_its_apis_without_a_chat_permission(string $role, string $state): void
    {
        $user = $this->account($role);
        if ($state !== 'legacy') {
            $user->assignRole($role);
            if ($role !== 'parent') {
                $user->roles()->firstOrFail()->revokePermissionTo('access chat');
            }
        }
        if ($state === 'missing_permission') {
            Permission::findByName('access chat', 'web')->delete();
        }
        $user->forgetCachedPermissions();
        $user = $user->fresh();

        $this->assertTrue($user->canAccessChat());
        $this->actingAs($user)->get(route('chat.page'))->assertOk()->assertSee('data-chat-root', false);
        $this->actingAs($user)->getJson(route('chat.conversations.index'))->assertOk();
        $this->actingAs($user)->getJson(route('chat.discovery'))->assertOk()
            ->assertJsonPath('role', $role === 'parent' ? 'learner' : $role);
        $this->actingAs($user)->getJson(route('chat.requests.index'))->assertOk();
        if ($role === 'admin' && $state !== 'legacy') {
            $this->actingAs($user)->get(route('admin.messages.index'))->assertOk();
        }
    }

    public static function restrictedAccounts(): array
    {
        $cases = [];
        foreach (['admin', 'instructor', 'learner'] as $role) {
            foreach ([User::STATUS_INACTIVE, User::STATUS_SUSPENDED, User::STATUS_ARCHIVED] as $status) {
                $cases[$role.' '.$status] = [$role, $status];
            }
        }

        return $cases;
    }

    #[DataProvider('restrictedAccounts')]
    public function test_non_active_accounts_cannot_open_chat_or_read_conversation_previews(string $role, string $status): void
    {
        $user = $this->account($role);
        $user->assignRole($role);
        $peer = $this->account('instructor');
        $conversation = Conversation::create([
            'participant_one_id' => $user->id, 'participant_two_id' => $peer->id,
            'pair_key' => Conversation::makePairKey($user->id, $peer->id),
            'context_key' => 'direct', 'conversation_type' => 'direct', 'status' => 'active',
        ]);
        Message::create(['conversation_id' => $conversation->id, 'sender_id' => $peer->id, 'message_body' => 'Private message preview']);
        $user->update(['status' => $status]);

        $this->actingAs($user)->get(route('chat.page'))->assertForbidden();
        if ($role === 'admin') {
            $this->actingAs($user)->get(route('admin.messages.index'))->assertForbidden();
        }
        $this->actingAs($user)->getJson(route('chat.conversations.index'))->assertForbidden()->assertDontSee('Private message preview');
        $this->actingAs($user)->getJson(route('chat.discovery'))->assertForbidden();
        $this->actingAs($user)->getJson(route('chat.messages.index', $conversation))->assertForbidden();
        $this->actingAs($user)->postJson(route('chat.messages.store', $conversation), ['message_body' => 'Blocked'])->assertForbidden();
    }

    public static function unsupportedAccounts(): array
    {
        return [['counselor'], ['clinic'], ['organization']];
    }

    #[DataProvider('unsupportedAccounts')]
    public function test_unrelated_roles_are_not_implicitly_given_chat_access(string $role): void
    {
        $user = $this->account($role);
        $user->assignRole($role);
        $this->assertFalse($user->canAccessChat());
        $this->actingAs($user)->get(route('chat.page'))->assertForbidden();
        $this->actingAs($user)->getJson(route('chat.conversations.index'))->assertForbidden();
    }

    public function test_explicit_chat_permission_still_allows_an_otherwise_unsupported_role(): void
    {
        $user = $this->account('counselor');
        $user->assignRole('counselor');
        $user->givePermissionTo('access chat');
        $this->actingAs($user)->getJson(route('chat.conversations.index'))->assertOk();
    }

    public function test_legacy_instructor_can_receive_and_accept_learner_message_requests_without_permission_catalog_entries(): void
    {
        Permission::findByName('access chat', 'web')->delete();
        Permission::findByName('manage message requests', 'web')->delete();
        $instructor = $this->account('instructor');
        $learner = $this->account('learner');
        $started = $this->actingAs($learner)->postJson(route('chat.conversations.start'), [
            'target_user_id' => $instructor->id, 'conversation_type' => 'direct', 'initial_message' => 'May I ask about your course?',
        ])->assertStatus(202);
        $requestId = $started->json('message_request.id');
        $conversationId = $started->json('conversation.id');
        $this->actingAs($instructor)->getJson(route('chat.requests.index'))->assertOk()
            ->assertJsonCount(1, 'requests')->assertJsonPath('requests.0.id', $requestId);
        $this->actingAs($learner)->postJson(route('chat.requests.accept', $requestId))->assertForbidden();
        $this->actingAs($instructor)->postJson(route('chat.requests.accept', $requestId))->assertOk();
        foreach ([$learner, $instructor] as $sender) {
            $this->actingAs($sender)->postJson(route('chat.messages.store', $conversationId), ['message_body' => 'Hello'])->assertCreated();
        }
    }

    public function test_instructor_message_buttons_use_the_same_access_rule_as_the_chat_page(): void
    {
        $instructor = $this->account('instructor');
        $instructor->assignRole('instructor');
        $instructor->roles()->firstOrFail()->revokePermissionTo('access chat');
        $instructor->forgetCachedPermissions();
        $learner = $this->account('learner');
        $learner->assignRole('learner');
        $module = Module::factory()->create(['created_by' => $instructor->id, 'content_owner_type' => 'instructor', 'is_published' => true]);
        ModuleEnrollment::create(['user_id' => $learner->id, 'module_id' => $module->id, 'status' => EnrollmentStatus::Approved, 'enrolled_at' => now()]);
        $this->actingAs($instructor)->get(route('instructor.users.index'))->assertOk()->assertSee('canUseChat: true', false);
        $this->actingAs($instructor)->get(route('instructor.users.show', $learner))->assertOk()->assertSee('Message Learner');
    }

    public function test_parent_verification_and_onboarding_remain_required(): void
    {
        $parent = $this->account('parent');
        $parent->update(['parent_verification_status' => null, 'guardian_onboarding_status' => null]);
        $this->actingAs($parent)->get(route('chat.page'))->assertRedirect(route('guardian.verification.create'));
        $this->actingAs($parent)->getJson(route('chat.conversations.index'))->assertRedirect(route('guardian.verification.create'));
        $parent->update(['parent_verification_status' => 'approved', 'guardian_onboarding_status' => 'pending']);
        $this->actingAs($parent)->get(route('chat.page'))->assertRedirect(route('guardian.onboarding.show'));
    }

    public function test_guests_must_sign_in_before_opening_chat(): void
    {
        $this->get(route('chat.page'))->assertRedirect(route('login'));
        $this->getJson(route('chat.conversations.index'))->assertUnauthorized();
    }

    private function account(string $role): User
    {
        return User::factory()->create([
            'role' => $role === 'parent' ? 'learner' : $role,
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => $role === 'parent',
            'parent_verification_status' => $role === 'parent' ? 'approved' : null,
            'guardian_onboarding_status' => $role === 'parent' ? 'completed' : null,
        ]);
    }
}
