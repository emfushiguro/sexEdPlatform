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
        $seminar = $this->seminar($connector);
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $admin = $this->admin();

        $this->actingAs($learner)
            ->get(route('admin.seminars.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.seminars.index', ['status' => 'published', 'connector_id' => $connector->id]))
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

    private function seminar(Connector $connector): Seminar
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
        ]);
    }
}
