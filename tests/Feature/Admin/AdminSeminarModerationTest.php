<?php

namespace Tests\Feature\Admin;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class AdminSeminarModerationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_non_admin_is_denied_and_admin_can_view_list_and_detail(): void
    {
        $connector = $this->connector();
        $seminar = $this->seminar($connector, ['status' => 'pending_review']);
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $admin = $this->admin();

        $this->actingAs($learner)
            ->get(route('admin.seminars.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.seminars.index'))
            ->assertOk()
            ->assertSee($seminar->title)
            ->assertSee($connector->name);

        $this->actingAs($admin)
            ->get(route('admin.seminars.show', $seminar))
            ->assertOk()
            ->assertSee($seminar->title)
            ->assertSee($connector->name);
    }

    public function test_admin_can_cancel_and_moderate_interactions(): void
    {
        $admin = $this->admin();
        $connector = $this->connector();
        $seminar = $this->seminar($connector);
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $comment = $seminar->comments()->create([
            'user_id' => $learner->id,
            'body' => 'Needs review',
            'status' => 'visible',
        ]);
        $question = $seminar->questions()->create([
            'user_id' => $learner->id,
            'question' => 'Question',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.seminars.cancel', $seminar), ['reason' => 'Policy issue'])
            ->assertRedirect();

        $this->assertDatabaseHas('seminars', [
            'id' => $seminar->id,
            'status' => 'cancelled',
            'cancelled_by' => $admin->id,
            'admin_moderation_reason' => 'Policy issue',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.seminars.comments.hide', [$seminar, $comment]), ['reason' => 'Off topic'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.seminars.questions.answer', [$seminar, $question]), ['answer' => 'Answered'])
            ->assertRedirect();

        $this->assertDatabaseHas('seminar_comments', ['id' => $comment->id, 'status' => 'hidden', 'hidden_by' => $admin->id]);
        $this->assertDatabaseHas('seminar_questions', ['id' => $question->id, 'status' => 'answered', 'answered_by' => $admin->id]);
    }

    public function test_admin_can_approve_pending_review_seminar(): void
    {
        $admin = $this->admin();
        $connector = $this->connector();
        $seminar = $this->seminar($connector, [
            'status' => 'pending_review',
            'submitted_for_review_by' => $connector->created_by,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.seminars.approve', $seminar))
            ->assertRedirect();

        $seminar->refresh();
        $this->assertSame('approved', $seminar->status);
        $this->assertSame($admin->id, $seminar->approved_by);
        $this->assertNotNull($seminar->approved_at);
        $this->assertDatabaseHas('seminar_moderation_reviews', [
            'seminar_id' => $seminar->id,
            'moderator_id' => $admin->id,
            'from_status' => 'pending_review',
            'to_status' => 'approved',
        ]);
        $this->assertSame('seminar_moderation_decision', $connector->creator->notifications()->firstOrFail()->data['type']);
    }

    public function test_admin_can_reject_pending_review_seminar_with_reason_and_note(): void
    {
        $admin = $this->admin();
        $connector = $this->connector();
        $seminar = $this->seminar($connector, [
            'status' => 'pending_review',
            'submitted_for_review_by' => $connector->created_by,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.seminars.reject', $seminar), [
                'reason' => 'incomplete_information',
                'note' => 'Add the venue accessibility details.',
            ])
            ->assertRedirect();

        $seminar->refresh();
        $this->assertSame('rejected', $seminar->status);
        $this->assertSame($admin->id, $seminar->rejected_by);
        $this->assertSame('incomplete_information', $seminar->rejection_reason);
        $this->assertSame('Add the venue accessibility details.', $seminar->moderator_note);
        $this->assertDatabaseHas('seminar_moderation_reviews', [
            'seminar_id' => $seminar->id,
            'moderator_id' => $admin->id,
            'from_status' => 'pending_review',
            'to_status' => 'rejected',
            'reason' => 'incomplete_information',
            'note' => 'Add the venue accessibility details.',
        ]);
        $notification = $connector->creator->notifications()->firstOrFail();
        $this->assertSame('seminar_moderation_decision', $notification->data['type']);
        $this->assertSame('incomplete_information', $notification->data['reason']);
    }

    public function test_admin_moderation_history_is_preserved(): void
    {
        $admin = $this->admin();
        $seminar = $this->seminar($this->connector(), ['status' => 'pending_review']);

        $this->actingAs($admin)
            ->post(route('admin.seminars.reject', $seminar), [
                'reason' => 'eligibility_unclear',
                'note' => 'Clarify age categories.',
            ])
            ->assertRedirect();

        $seminar->refresh()->update(['status' => 'pending_review']);

        $this->actingAs($admin)
            ->post(route('admin.seminars.approve', $seminar))
            ->assertRedirect();

        $this->assertSame(2, $seminar->moderationReviews()->count());

        $this->actingAs($admin)
            ->get(route('admin.seminars.show', $seminar))
            ->assertOk()
            ->assertSee('eligibility_unclear')
            ->assertSee('Clarify age categories.')
            ->assertSee('approved');
    }

    public function test_moderation_table_only_lists_pending_review_and_filters_by_search_and_type(): void
    {
        $admin = $this->admin();
        $connector = $this->connector();
        $matching = $this->seminar($connector, [
            'title' => 'Accessible Parenting Webinar',
            'status' => 'pending_review',
            'type' => 'webinar',
        ]);
        $this->seminar($connector, [
            'title' => 'Archived Wellness Meetup',
            'status' => 'approved',
            'type' => 'physical',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.seminars.index', [
                'search' => 'Parenting',
                'type' => 'webinar',
            ]))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee('Archived Wellness Meetup');
    }

    public function test_connector_submission_notifies_admin_once(): void
    {
        $admin = $this->admin();
        $connector = $this->connector();
        $owner = $connector->creator;
        $seminar = $this->seminar($connector, ['status' => 'draft']);

        $this->actingAs($owner)
            ->post(route('connector.seminars.submit-review', [$connector, $seminar]))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame('new_seminar_submission', $notification->data['type']);
        $this->assertSame($seminar->id, $notification->data['seminar_id']);
        $this->assertSame(route('admin.seminars.show', $seminar), $notification->data['action_url']);

        $seminar->forceFill(['status' => 'rejected'])->save();

        $this->actingAs($owner)
            ->post(route('connector.seminars.submit-review', [$connector, $seminar]))
            ->assertRedirect();

        $this->assertSame(1, $admin->notifications()->where('data->seminar_id', $seminar->id)->count());
    }

    private function admin(): User
    {
        $role = Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole($role);

        return $admin;
    }

    private function connector(): Connector
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');

        return $this->createVerifiedConnector($owner);
    }

    private function seminar(Connector $connector, array $overrides = []): Seminar
    {
        return Seminar::query()->create([
            'connector_id' => $connector->id,
            'type' => 'webinar',
            'title' => 'Moderated Webinar',
            'description' => 'A free community session.',
            'purpose' => 'Support learner wellness.',
            'category' => 'health',
            'status' => 'published',
            'schedule' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'capacity' => 50,
            'target_participants' => 'learners_and_instructors',
            'learner_age_categories' => ['adult'],
            'livestream_channel' => 'seminar-test-channel-'.str()->random(6),
            ...$overrides,
        ]);
    }
}
