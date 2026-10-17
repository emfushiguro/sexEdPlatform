<?php

namespace Tests\Feature\Connectors;

use App\Events\Chat\MessageSent;
use App\Events\Chat\MessageUpdated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\ChatAuthorizationService;
use App\Services\Chat\ChatService;
use App\Services\Connectors\ConnectorAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectorInstructorMessagingTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_seminar_hosts_can_manage_educators_by_default_but_ordinary_members_cannot(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $member = $this->createAdultConnectorMember($connector);
        $this->assertTrue(app(ConnectorAccessService::class)->hasPermission($host, $connector, 'connector.manage_educators'));
        $this->actingAs($host)->get(route('connector.educators', $connector))
            ->assertOk()->assertSee($instructor->name)->assertSee('Message instructor')->assertDontSee('not enabled yet');
        $this->actingAs($member)->get(route('connector.educators', $connector))->assertForbidden();
        $this->actingAs($member)->post($this->startUrl($connector, $instructor))->assertForbidden();
    }

    public function test_start_is_idempotent_and_both_participants_can_message_without_enrollment_or_subscription(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $conversation = $this->start($connector, $host, $instructor);
        $this->actingAs($host)->post($this->startUrl($connector, $instructor))
            ->assertRedirect(route('connector.educators.messages', [$connector, 'conversation_id' => $conversation->id]));
        $this->assertDatabaseCount('conversations', 1);
        $this->assertSame('connector_negotiation', $conversation->conversation_type);
        $this->assertSame($connector->id, $conversation->connector_id);
        foreach ([$host, $instructor] as $sender) {
            $this->actingAs($sender)->postJson(route('chat.messages.store', $conversation), ['message_body' => 'Discuss the speaker fee.'])
                ->assertCreated();
        }
        $this->assertDatabaseCount('message_requests', 0);
        $this->assertDatabaseCount('module_enrollments', 0);
        $this->assertDatabaseCount('subscribers', 0);
        $this->actingAs($instructor)->getJson(route('chat.conversations.index'))->assertOk()
            ->assertJsonPath('conversations.0.context_label', $connector->name.' · Negotiation')
            ->assertJsonPath('conversations.0.connector_id', $connector->id);
        $this->actingAs($host)->get(route('connector.educators.messages', $connector))->assertOk()
            ->assertSee('Negotiation messages')->assertSee('data-chat-root', false);
        $this->actingAs($host)->get(route('chat.conversation.open', $conversation))
            ->assertRedirect(route('connector.educators.messages', [$connector, 'conversation_id' => $conversation->id]));
        $notification = $host->notifications()->where('data->conversation_id', $conversation->id)->firstOrFail();
        $this->assertSame($connector->id, $notification->data['connector_id']);
    }

    public function test_directory_search_only_exposes_active_instructors_and_accepts_delegated_educator_managers(): void
    {
        [$connector, , $instructor] = $this->context();
        $instructor->update(['name' => 'Negotiation Expert']);
        $inactive = $this->instructor(['name' => 'Unavailable Expert', 'status' => User::STATUS_SUSPENDED]);
        $manager = $this->createAdultConnectorMember($connector, ['connector.manage_educators']);
        $this->actingAs($manager)->get(route('connector.educators', [$connector, 'q' => 'Negotiation']))
            ->assertOk()->assertSee('Negotiation Expert')->assertDontSee('Unavailable Expert')->assertDontSee($instructor->email);
        $this->actingAs($manager)->post($this->startUrl($connector, $inactive))->assertForbidden();
        $this->actingAs($manager)->post($this->startUrl($connector, $manager))->assertForbidden();
        $this->start($connector, $manager, $instructor);
    }

    public function test_threads_are_private_and_separate_from_personal_chats_and_other_connectors_and_organizers(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $first = $this->start($connector, $host, $instructor);
        $otherConnector = $this->createVerifiedConnector($host);
        $second = $this->start($otherConnector, $host, $instructor);
        $owner = $connector->creator;
        $third = $this->start($connector, $owner, $instructor);
        $personal = Conversation::create([
            'participant_one_id' => $host->id, 'participant_two_id' => $instructor->id,
            'pair_key' => Conversation::makePairKey($host->id, $instructor->id),
            'context_key' => 'direct', 'conversation_type' => 'direct', 'status' => 'active',
        ]);
        $this->assertCount(4, array_unique([$first->id, $second->id, $third->id, $personal->id]));
        $this->actingAs($owner)->getJson(route('chat.messages.index', $first))->assertForbidden();
        $this->actingAs($owner)->getJson(route('chat.conversations.index'))->assertOk()
            ->assertJsonCount(1, 'conversations')->assertJsonPath('conversations.0.id', $third->id);
        $this->actingAs($host)->getJson(route('chat.messages.index', $third))->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->getJson(route('chat.messages.index', $first))->assertForbidden();
        $this->actingAs($admin)->getJson(route('chat.conversations.index'))->assertJsonCount(0, 'conversations');
    }

    public function test_revoking_host_membership_or_permission_disables_all_negotiation_access_and_preserves_messages(): void
    {
        foreach (['membership', 'permission', 'connector', 'host', 'instructor', 'instructor_role', 'deleted_host'] as $revocation) {
            [$connector, $host, $instructor] = $this->context();
            $conversation = $this->start($connector, $host, $instructor);
            $message = app(ChatService::class)->sendMessage($host, $conversation, 'Private agreement');
            $membership = $connector->memberships()->where('user_id', $host->id)->firstOrFail();
            match ($revocation) {
                'membership' => $membership->update(['status' => 'inactive']),
                'permission' => $membership->role->permissions()->delete(),
                'connector' => $connector->update(['status' => 'suspended']),
                'host' => $host->update(['status' => User::STATUS_SUSPENDED]),
                'instructor' => $instructor->update(['status' => User::STATUS_SUSPENDED]),
                'instructor_role' => $instructor->update(['role' => 'learner']) && $instructor->syncRoles(['learner']),
                'deleted_host' => $host->delete(),
            };
            foreach ([$host, $instructor] as $viewer) {
                $inbox = $this->actingAs($viewer)->getJson(route('chat.conversations.index'));
                if ($viewer->trashed() || $viewer->fresh()?->status !== User::STATUS_ACTIVE) {
                    $inbox->assertForbidden();
                } else {
                    $inbox->assertOk()->assertJsonCount(0, 'conversations');
                }
                $this->actingAs($viewer)->getJson(route('chat.messages.index', $conversation))->assertForbidden();
                $this->actingAs($viewer)->getJson(route('chat.messages.since', [$conversation, 0]))->assertForbidden();
                $this->actingAs($viewer)->postJson(route('chat.messages.store', $conversation), ['message_body' => 'Blocked'])->assertForbidden();
                $this->actingAs($viewer)->postJson(route('chat.conversations.read', $conversation))->assertForbidden();
                $this->actingAs($viewer)->patchJson(route('chat.messages.update', $message), ['message_body' => 'Blocked edit'])->assertForbidden();
                $this->actingAs($viewer)->deleteJson(route('chat.messages.destroy', $message))->assertForbidden();
                $this->actingAs($viewer)->postJson(route('chat.messages.report', $message), ['reason_code' => 'other', 'custom_reason' => 'Blocked'])->assertForbidden();
                $this->assertFalse(app(ChatAuthorizationService::class)->canSubscribeToConversation($viewer, $conversation));
            }
            $this->assertFalse((new MessageSent($message))->broadcastWhen());
            $this->assertFalse((new MessageUpdated($message, 'edited'))->broadcastWhen());
            $this->assertSame([], (new MessageSent($message))->broadcastOn());
            $this->assertSame([], (new MessageUpdated($message, 'edited'))->broadcastOn());
            $this->assertDatabaseHas('messages', ['id' => $message->id, 'message_body' => 'Private agreement']);
        }
    }

    public function test_generic_chat_start_cannot_bypass_connector_authorization(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $this->actingAs($host)->postJson(route('chat.conversations.start'), [
            'target_user_id' => $instructor->id, 'conversation_type' => 'connector_negotiation', 'connector_id' => $connector->id,
        ])->assertUnprocessable();
        $this->expectException(AuthorizationException::class);
        app(ChatService::class)->createOrGetConversation($host, $instructor, 'connector_negotiation');
    }

    public function test_host_access_does_not_depend_on_global_chat_permissions(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $host->syncRoles([]);
        $host->update(['role' => 'instructor']);
        $this->assertFalse($host->can('access chat'));
        $this->assertTrue($host->canAccessChat());
        $this->start($connector, $host, $instructor);
    }

    public function test_connector_inbox_filters_before_pagination_and_rejects_unrelated_workspace(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $conversation = $this->start($connector, $host, $instructor);
        $otherConnector = $this->createVerifiedConnector($host);
        $this->start($otherConnector, $host, $instructor);
        $this->actingAs($host)->getJson(route('chat.conversations.index', ['connector_id' => $connector->id, 'per_page' => 1]))
            ->assertOk()->assertJsonPath('pagination.total', 1)->assertJsonPath('conversations.0.id', $conversation->id);
        $outsider = $this->createCompletedLearner();
        $this->actingAs($outsider)->getJson(route('chat.conversations.index', ['connector_id' => $connector->id]))->assertForbidden();
        $this->actingAs($host)->get(route('connector.educators.messages', [$otherConnector, 'conversation_id' => $conversation->id]))->assertNotFound();
    }

    public function test_instructors_who_are_hosts_can_reverse_negotiation_roles_after_original_host_leaves(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $host->update(['role' => 'instructor']);
        $host->assignRole('instructor');
        $membership = $connector->memberships()->where('user_id', $host->id)->firstOrFail();
        $connector->memberships()->create([
            'user_id' => $instructor->id, 'connector_role_id' => $membership->connector_role_id, 'status' => 'active',
        ]);
        $first = $this->start($connector, $host, $instructor);
        $membership->update(['status' => 'inactive']);
        $reverse = $this->start($connector, $instructor, $host);
        $this->assertNotSame($first->id, $reverse->id);
        $this->actingAs($instructor)->get(route('connector.educators.messages', [$connector, 'conversation_id' => $reverse->id]))->assertOk();
    }

    public function test_negotiation_attachments_are_private_and_downloads_require_current_participant_access(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$connector, $host, $instructor] = $this->context();
        $conversation = $this->start($connector, $host, $instructor);
        $response = $this->actingAs($host)->postJson(route('chat.messages.store', $conversation), [
            'message_body' => 'Proposed terms', 'attachments' => [UploadedFile::fake()->createWithContent('agreement.txt', 'Private terms')],
        ])->assertCreated();
        $message = Message::findOrFail($response->json('message.id'));
        $attachment = $message->attachments()->sole();
        $this->assertSame('local', $attachment->disk);
        Storage::disk('local')->assertExists($attachment->path);
        Storage::disk('public')->assertMissing($attachment->path);
        $url = $response->json('message.attachments.0.url');
        $this->assertStringNotContainsString('/storage/', $url);
        $this->actingAs($instructor)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($connector->creator)->get($url)->assertForbidden();
        $connector->memberships()->where('user_id', $host->id)->update(['status' => 'inactive']);
        $this->actingAs($instructor)->get($url)->assertForbidden();
        $connector->memberships()->where('user_id', $host->id)->update(['status' => 'active']);
        $message->forceFill(['deleted_at' => now()])->save();
        $this->actingAs($instructor)->get($url)->assertNotFound();
    }

    private function context(): array
    {
        $connector = $this->createVerifiedConnector($this->createCompletedLearner());
        $host = $this->createAdultConnectorMember($connector, ['connector.manage_seminars']);

        return [$connector, $host, $this->instructor()];
    }

    private function instructor(array $attributes = []): User
    {
        $user = User::factory()->create(['role' => 'instructor', ...$attributes]);
        $user->assignRole('instructor');

        return $user->fresh();
    }

    private function startUrl($connector, User $instructor): string
    {
        return route('connector.educators.conversation', [$connector, $instructor]);
    }

    private function start($connector, User $host, User $instructor): Conversation
    {
        $this->actingAs($host)->post($this->startUrl($connector, $instructor))->assertRedirect();

        return Conversation::where('connector_id', $connector->id)->where('participant_one_id', $host->id)
            ->where('participant_two_id', $instructor->id)->sole();
    }
}
