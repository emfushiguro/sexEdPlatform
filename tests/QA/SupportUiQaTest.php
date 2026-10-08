<?php

namespace Tests\QA;

use App\Models\PlatformFeedback;
use App\Models\User;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class SupportUiQaTest extends TestCase
{
    private function user(string $role = 'learner'): User
    {
        $user = User::factory()->create(['role' => $role, 'account_type' => $role === 'learner' ? 'learner-adult' : $role, 'age' => 25, 'birthdate' => now()->subYears(25)]);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function dom(string $html): \DOMXPath
    {
        $doc = new \DOMDocument;
        @$doc->loadHTML($html);

        return new \DOMXPath($doc);
    }

    public function test_ui01_feedback_controls_have_labels(): void
    {
        $this->user();
        $response = $this->get(route('feedback.create'))->assertOk();
        $xpath = $this->dom($response->getContent());
        foreach (['type', 'subject', 'description', 'rating', 'attachment', 'may_contact'] as $name) {
            $control = $xpath->query('//form[@data-platform-feedback-form]//*[@name="'.$name.'"]')->item(0);
            $this->assertNotNull($control);
            $id = $control->getAttribute('id');
            $this->assertTrue($control->hasAttribute('aria-label') || $control->hasAttribute('aria-labelledby') || ($id !== '' && $xpath->query('//label[@for="'.$id.'"]')->length > 0), "$name lacks a label");
        }
    }

    public function test_ui02_validation_messages_are_visible_by_field(): void
    {
        $this->user();
        $this->withVite();
        Vite::useHotFile(storage_path('framework/qa-unused-hot-file'));
        $this->post(route('feedback.store'), ['type' => 'bug_report', 'subject' => 'Keep my subject', 'description' => 'Keep my description', 'rating' => 8, 'testimonial_show_role' => 1])->assertSessionHasErrors('rating');
        $message = session('errors')->first('rating');
        $response = $this->get(route('feedback.create'))->assertOk();
        $response->assertSee($message);
        $xpath = $this->dom($response->getContent());
        $this->assertGreaterThan(0, $xpath->query('//form[@data-platform-feedback-form]//input[@name="rating"][@aria-describedby or @aria-errormessage]')->length, 'Validation exists in shell toast data, but is not associated with the invalid field.');
    }

    public function test_ui03_failed_form_keeps_category_and_permissions(): void
    {
        $this->user();
        $this->post(route('feedback.store'), ['type' => 'bug_report', 'subject' => 'Preserved', 'description' => 'Preserved', 'rating' => 8, 'testimonial_consent' => 1, 'testimonial_display_name' => 'QA', 'testimonial_show_role' => 1, 'testimonial_show_profile_image' => 1])->assertSessionHasErrors('rating');
        $html = $this->get(route('feedback.create'))->assertOk()->getContent();
        $xpath = $this->dom($html);
        $this->assertSame(1, $xpath->query('//input[@name="type"][@value="bug_report"][@checked]')->length, 'Feedback type resets to General.');
        $this->assertSame(0, $xpath->query('//input[@name="testimonial_show_role"]')->length);
    }

    public function test_ui04_admin_distinguishes_private_notes_from_public_response(): void
    {
        $this->user('admin');
        $feedback = PlatformFeedback::factory()->create();
        $xpath = $this->dom($this->get(route('admin.feedback.show', $feedback))->assertOk()->getContent());
        foreach (['internal_note', 'staff_response'] as $name) {
            $control = $xpath->query('//textarea[@name="'.$name.'"]')->item(0);
            $id = $control->getAttribute('id');
            $this->assertTrue($control->hasAttribute('aria-label') || ($id !== '' && $xpath->query('//label[@for="'.$id.'"]')->length > 0), "$name is an unlabeled textarea; staff cannot distinguish private and public fields.");
        }
    }

    public function test_ui05_admin_support_navigation_exists(): void
    {
        $this->user('admin');
        $xpath = $this->dom($this->get(route('admin.help.articles.index'))->assertOk()->getContent());
        $this->assertSame(0, $xpath->query('//*[@data-support-section="user"]')->length, 'Personal Support should not be duplicated in the admin sidebar.');
        foreach (['admin.help.articles.index', 'admin.feedback.index', 'admin.testimonials.index'] as $name) {
            $this->assertGreaterThan(0, $xpath->query('//aside//a[@href="'.route($name).'"]')->length, "$name missing from Support Management");
        }
        foreach (['admin.feedback.index', 'admin.testimonials.index'] as $name) {
            $this->assertSame(0, $xpath->query('//nav[@aria-label="Support management"]//a[@href="'.route($name).'"]')->length, "$name should stay in the sidebar, not the duplicate top tabs");
        }
    }

    public function test_ui06_authenticated_support_navigation_exists(): void
    {
        $this->user();
        $xpath = $this->dom($this->get(route('help.index'))->assertOk()->getContent());
        foreach (['help.index', 'feedback.create'] as $name) {
            $this->assertGreaterThan(0, $xpath->query('//aside//a[@href="'.route($name).'"]')->length, "$name missing from authenticated sidebar");
        }
    }

    public function test_ui07_testimonial_edit_and_order_actions_exist(): void
    {
        $routes = app('router')->getRoutes();
        foreach (['admin.testimonials.update', 'admin.testimonials.order', 'admin.testimonials.publish', 'admin.testimonials.withdraw'] as $name) {
            $this->assertNotNull($routes->getByName($name), "$name missing; the consent-aware curation workflow is incomplete");
        }
    }

    public function test_ui08_full_insights_and_filters_are_exposed(): void
    {
        $this->user('admin');
        $html = $this->get(route('admin.feedback.index'))->assertOk()->getContent();
        $xpath = $this->dom($html);
        foreach (['search', 'status', 'type', 'rating', 'from', 'to'] as $name) {
            $control = $xpath->query('//form[@data-feedback-filters]//*[@name="'.$name.'"]')->item(0);
            $this->assertNotNull($control, "Missing $name filter control");
            $this->assertGreaterThan(0, $xpath->query('//label[@for="'.$control->getAttribute('id').'"]')->length, "$name filter lacks a visible label");
        }
    }
}
