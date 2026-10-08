<?php

namespace Tests\Feature\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use App\Models\PlatformFeedback;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class PlatformFeedbackUiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_feedback_form_ignores_legacy_page_prefill_parameters(): void
    {
        $user = $this->adultLearner();

        $this->actingAs($user)->get(route('feedback.create', [
            'type' => 'help_content_issue',
            'affected_path' => '/help/example?token=secret#private',
        ]))->assertOk()
            ->assertSee('value="help_content_issue"', false)
            ->assertDontSee('name="affected_path"', false)
            ->assertDontSee('token=secret')
            ->assertDontSee('#private');

        $this->get(route('feedback.create', ['affected_path' => 'https://example.com/private']))
            ->assertOk()
            ->assertDontSee('https://example.com/private');
    }

    public function test_feedback_form_has_visible_labels_and_preserves_every_choice(): void
    {
        $user = $this->adultLearner();
        $response = $this->actingAs($user)->withSession([
            '_old_input' => [
                'type' => 'bug_report',
                'subject' => 'Keep this subject',
                'description' => 'Keep these details',
                'rating' => '4',
                'may_contact' => '1',
            ],
        ])->get(route('feedback.create'))->assertOk();

        $xpath = $this->dom($response->getContent());
        foreach (['type', 'subject', 'description', 'rating', 'attachment', 'may_contact'] as $name) {
            $control = $xpath->query('//form//*[@name="'.$name.'"]')->item(0);
            $this->assertNotNull($control, $name.' is missing');
            $id = $control->getAttribute('id');
            $this->assertGreaterThan(0, $xpath->query('//label[@for="'.$id.'"]')->length, $name.' lacks a visible label');
        }

        $this->assertSame(1, $xpath->query('//select[@name="type"]/option[@value="bug_report"][@selected]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="rating"][@value="4"][@checked]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="may_contact"][@value="1"][@checked]')->length);
    }

    public function test_feedback_form_uses_the_six_option_dropdown_and_accessible_hearts(): void
    {
        $response = $this->actingAs($this->adultLearner())->get(route('feedback.create'))->assertOk();
        $xpath = $this->dom($response->getContent());

        $expected = [
            'general' => 'General Inquiry',
            'bug_report' => 'Report a Problem',
            'feature_suggestion' => 'Suggestion',
            'accessibility_issue' => 'Technical Issue',
            'help_content_issue' => 'Content Concern',
            'account_payment_issue' => 'Account or Payment Issue',
        ];

        $this->assertSame(1, $xpath->query('//select[@name="type"]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="type"]')->length);
        foreach ($expected as $value => $label) {
            $option = $xpath->query('//select[@name="type"]/option[@value="'.$value.'"]')->item(0);
            $this->assertNotNull($option);
            $this->assertSame($label, trim($option->textContent));
        }

        $this->assertSame(5, $xpath->query('//input[@name="rating"]')->length);
        foreach (range(1, 5) as $rating) {
            $this->assertSame(1, $xpath->query('//input[@name="rating"][@value="'.$rating.'"][@aria-label="'.$rating.' out of 5 hearts"]')->length);
        }

        $this->assertStringContainsString('/500', $response->getContent());
    }

    public function test_rating_hearts_start_neutral_and_a_selected_value_is_saved(): void
    {
        $user = $this->adultLearner();
        $response = $this->actingAs($user)->get(route('feedback.create'))->assertOk();
        $xpath = $this->dom($response->getContent());

        $this->assertSame(5, $xpath->query('//span[@data-feedback-rating-heart]')->length);
        $this->assertSame(5, $xpath->query('//span[@data-feedback-rating-heart][contains(@class, "text-gray-300")]')->length);
        $this->assertSame(0, $xpath->query('//span[@data-feedback-rating-heart][contains(@class, "text-rose-500")]')->length);

        $this->actingAs($user)->post(route('feedback.store'), [
            'type' => 'general',
            'subject' => 'The second heart is my rating',
            'description' => 'I chose the second heart.',
            'rating' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('platform_feedback', [
            'user_id' => $user->id,
            'rating' => 2,
        ]);
    }

    public function test_feedback_forms_preview_images_and_do_not_show_safety_warning_cards(): void
    {
        $user = $this->adultLearner();

        $this->actingAs($user)
            ->get(route('feedback.create'))
            ->assertOk()
            ->assertSee('data-feedback-attachment-preview', false)
            ->assertSee('data-feedback-attachment-preview-image', false)
            ->assertDontSee('Reporting unsafe content?')
            ->assertDontSee('Need to report something unsafe?');

        $this->get(route('help.index'))
            ->assertOk()
            ->assertDontSee('Need to report something unsafe?');
    }

    public function test_instructor_feedback_form_uses_the_same_image_preview(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'account_type' => 'instructor',
            'age' => 30,
            'birthdate' => now()->subYears(30),
        ]);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('feedback.create'))
            ->assertOk()
            ->assertSee('data-feedback-attachment-preview', false)
            ->assertSee('data-feedback-attachment-preview-image', false);
    }

    public function test_history_filters_only_the_owners_active_feedback(): void
    {
        $owner = $this->adultLearner();
        $other = $this->adultLearner();
        $active = PlatformFeedback::factory()->for($owner)->create([
            'subject' => 'Active feedback item',
            'status' => PlatformFeedbackStatus::Reviewed,
            'type' => PlatformFeedbackType::BugReport,
        ]);
        $closed = PlatformFeedback::factory()->for($owner)->create([
            'subject' => 'Closed feedback item',
            'status' => PlatformFeedbackStatus::Resolved,
        ]);
        PlatformFeedback::factory()->for($other)->create(['subject' => 'Private other feedback']);

        $this->actingAs($owner)->get(route('feedback.index', ['view' => 'active']))
            ->assertOk()
            ->assertSee($active->subject)
            ->assertSee('Report a Problem')
            ->assertSee('In Review')
            ->assertDontSee($closed->subject)
            ->assertDontSee('Private other feedback');
    }

    public function test_history_has_distinct_resolved_and_closed_filters(): void
    {
        $owner = $this->adultLearner();
        $resolved = PlatformFeedback::factory()->for($owner)->create([
            'subject' => 'Resolved feedback item',
            'status' => PlatformFeedbackStatus::Resolved,
        ]);
        $closed = PlatformFeedback::factory()->for($owner)->create([
            'subject' => 'Closed feedback item',
            'status' => PlatformFeedbackStatus::Closed,
        ]);
        $withdrawn = PlatformFeedback::factory()->for($owner)->create([
            'subject' => 'Withdrawn feedback item',
            'status' => PlatformFeedbackStatus::Withdrawn,
        ]);

        $this->actingAs($owner)->get(route('feedback.index', ['view' => 'resolved']))
            ->assertOk()
            ->assertSee($resolved->subject)
            ->assertDontSee($closed->subject)
            ->assertDontSee($withdrawn->subject);

        $this->get(route('feedback.index', ['view' => 'closed']))
            ->assertOk()
            ->assertDontSee($resolved->subject)
            ->assertSee($closed->subject)
            ->assertSee($withdrawn->subject);
    }

    public function test_withdraw_action_is_visible_only_for_new_tickets(): void
    {
        $owner = $this->adultLearner();

        foreach (PlatformFeedbackStatus::cases() as $status) {
            $ticket = PlatformFeedback::factory()->for($owner)->create(['status' => $status]);
            $response = $this->actingAs($owner)->get(route('feedback.show', $ticket))->assertOk();

            if ($status === PlatformFeedbackStatus::New) {
                $response->assertSee('data-confirm-submit', false);
            } else {
                $response->assertDontSee('Withdraw ticket');
            }
        }
    }

    public function test_feedback_detail_explains_the_current_status_and_conversation(): void
    {
        $owner = $this->adultLearner();
        $feedback = PlatformFeedback::factory()->for($owner)->create([
            'status' => PlatformFeedbackStatus::Reviewed,
            'type' => PlatformFeedbackType::AccessibilityIssue,
            'may_contact' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $feedback->messages()->create(['sender_id' => $admin->id, 'sender_role' => 'admin', 'body' => 'We are reviewing the contrast issue.']);

        $response = $this->actingAs($owner)
            ->withSession(['success' => 'Thanks for your feedback.'])
            ->get(route('feedback.show', $feedback))
            ->assertOk()
            ->assertSee('Thanks for your feedback.')
            ->assertSee('Technical Issue')
            ->assertSee('We are reviewing the contrast issue.')
            ->assertSee('data-feedback-current-status="reviewed"', false)
            ->assertSee('Messages');

        $xpath = $this->dom($response->getContent());
        $this->assertSame(1, $xpath->query('//*[@data-feedback-current-status]')->length);
    }

    public function test_feedback_detail_renders_the_submitter_attachment_inline(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('feedback/learner.png', 'png bytes');
        $owner = $this->adultLearner();
        $feedback = PlatformFeedback::factory()->for($owner)->create([
            'attachment_path' => 'feedback/learner.png',
        ]);

        $this->actingAs($owner)->get(route('feedback.show', $feedback))
            ->assertOk()
            ->assertSee('data-feedback-attachment-preview', false)
            ->assertSee('data-feedback-attachment-preview-image', false)
            ->assertSee(route('feedback.attachment.show', $feedback), false);
    }

    private function adultLearner(): User
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');

        return $user;
    }

    private function dom(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
