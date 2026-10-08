<?php

namespace Tests\Feature\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Enums\TestimonialStatus;
use App\Models\PlatformFeedback;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SupportTicketSeparationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_ticket_form_has_no_affected_page_or_testimonial_fields_and_submission_stays_private(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');

        $this->actingAs($user)
            ->get(route('feedback.create'))
            ->assertOk()
            ->assertDontSee('name="affected_path"', false)
            ->assertDontSee('name="testimonial_consent"', false)
            ->assertDontSee('name="testimonial_display_name"', false);

        $this->post(route('feedback.store'), [
            'type' => 'bug_report',
            'subject' => 'Ticket subject',
            'description' => 'Private ticket details.',
            'rating' => 4,
            'affected_path' => '/should-not-be-stored',
            'testimonial_consent' => 1,
            'testimonial_display_name' => 'Should not become a testimonial',
        ])->assertRedirect();

        $ticket = PlatformFeedback::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNull($ticket->affected_path);
        $this->assertSame(0, Testimonial::query()->count());
    }

    public function test_adult_learner_and_instructor_can_submit_independent_testimonials(): void
    {
        foreach (['learner', 'instructor'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'account_type' => $role === 'learner' ? 'learner-adult' : 'instructor',
                'age' => 30,
                'birthdate' => now()->subYears(30),
            ]);
            $user->assignRole($role);

            $this->actingAs($user)
                ->get('/testimonials/create')
                ->assertOk()
                ->assertDontSee('name="affected_path"', false);

            $this->post('/testimonials', [
                'display_name' => ucfirst($role),
                'quotation' => 'A public experience shared voluntarily.',
                'show_role' => 1,
                'show_profile_image' => 0,
                'consent' => 1,
            ])->assertRedirect();

            $testimonial = Testimonial::query()->where('user_id', $user->id)->firstOrFail();
            $this->assertSame(TestimonialStatus::Draft, $testimonial->status);
            $this->assertNull($testimonial->platform_feedback_id);
        }

        $this->assertSame(0, PlatformFeedback::query()->count());
    }

    public function test_minor_cannot_submit_a_testimonial_even_with_consent(): void
    {
        $minor = User::factory()->create([
            'role' => 'learner',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_TEEN,
            'age' => 16,
            'birthdate' => now()->subYears(16),
        ]);
        $minor->assignRole('learner');

        $this->actingAs($minor)
            ->post('/testimonials', [
                'display_name' => 'Minor',
                'quotation' => 'Should not be accepted.',
                'consent' => 1,
            ])
            ->assertForbidden();

        $this->assertSame(0, Testimonial::query()->count());
    }

    public function test_ticket_withdrawal_does_not_change_an_independent_testimonial(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');
        $testimonial = Testimonial::factory()->for($user)->create(['status' => TestimonialStatus::Draft]);
        $ticket = PlatformFeedback::factory()->for($user)->create(['status' => PlatformFeedbackStatus::New]);

        $this->actingAs($user)
            ->delete(route('feedback.withdraw', $ticket))
            ->assertRedirect();

        $this->assertSame(PlatformFeedbackStatus::Withdrawn, $ticket->fresh()->status);
        $this->assertSame(TestimonialStatus::Draft, $testimonial->fresh()->status);
        $this->assertDatabaseHas('platform_feedback_histories', [
            'platform_feedback_id' => $ticket->id,
            'from_status' => 'new',
            'to_status' => 'withdrawn',
        ]);
    }

    public function test_only_owner_can_withdraw_a_new_ticket(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $other = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $ticket = PlatformFeedback::factory()->for($owner)->create(['status' => PlatformFeedbackStatus::New]);

        $this->actingAs($other)->delete(route('feedback.withdraw', $ticket))->assertForbidden();
        $this->actingAs($admin)->delete(route('feedback.withdraw', $ticket))->assertForbidden();
        $this->assertSame(PlatformFeedbackStatus::New, $ticket->fresh()->status);

        $this->actingAs($owner)->delete(route('feedback.withdraw', $ticket))->assertRedirect();
        $this->assertSame(PlatformFeedbackStatus::Withdrawn, $ticket->fresh()->status);
    }

    public function test_owner_cannot_withdraw_after_review_begins(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $ticket = PlatformFeedback::factory()->for($owner)->create(['status' => PlatformFeedbackStatus::Reviewed]);

        $this->actingAs($owner)->delete(route('feedback.withdraw', $ticket))->assertForbidden();

        $this->assertSame(PlatformFeedbackStatus::Reviewed, $ticket->fresh()->status);
        $this->assertDatabaseCount('platform_feedback_histories', 0);
    }

    public function test_ticket_status_updates_leave_a_private_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $ticket = PlatformFeedback::factory()->create(['status' => PlatformFeedbackStatus::New]);

        $this->actingAs($admin)->put(route('admin.feedback.update', $ticket), ['status' => 'reviewed'])->assertSessionHasNoErrors();
        $ticket->messages()->create(['sender_id' => $admin->id, 'sender_role' => 'admin', 'body' => 'We have reviewed this ticket.']);
        $this->put(route('admin.feedback.update', $ticket), ['status' => 'resolved'])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('platform_feedback_histories', 2);
        $this->assertDatabaseHas('platform_feedback_histories', ['platform_feedback_id' => $ticket->id, 'to_status' => 'reviewed']);
        $this->assertDatabaseHas('platform_feedback_histories', ['platform_feedback_id' => $ticket->id, 'to_status' => 'resolved']);
    }

    public function test_private_ticket_content_never_reaches_public_landing_page(): void
    {
        $user = User::factory()->create(['role' => 'learner', 'account_type' => 'learner-adult', 'age' => 25, 'birthdate' => now()->subYears(25)]);
        $user->assignRole('learner');
        $ticket = PlatformFeedback::factory()->for($user)->create([
            'subject' => 'Private billing details',
            'description' => 'This must remain visible only to the sender and administrators.',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee($ticket->subject)
            ->assertDontSee($ticket->description);
    }
}
