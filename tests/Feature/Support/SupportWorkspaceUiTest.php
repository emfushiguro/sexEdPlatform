<?php

namespace Tests\Feature\Support;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SupportWorkspaceUiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_authenticated_help_page_uses_sidebar_support_destinations_without_a_top_switcher(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');

        $this->actingAs($user)
            ->get(route('help.index'))
            ->assertOk()
            ->assertDontSee('data-support-workspace-nav', false)
            ->assertSee(route('help.index'), false)
            ->assertSee(route('feedback.create'), false)
            ->assertSee(route('feedback.index'), false)
            ->assertSee(route('testimonials.create'), false)
            ->assertSee(route('testimonials.index'), false)
            ->assertSeeInOrder(['Help Center', 'Submit a Ticket', 'My Tickets', 'Share Your Experience', 'My Testimonials']);
    }

    public function test_feedback_form_does_not_render_the_top_support_switcher(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');

        $response = $this->actingAs($user)->get(route('feedback.create'))->assertOk();
        $xpath = $this->dom($response->getContent());

        $this->assertSame(0, $xpath->query('//nav[@data-support-workspace-nav]')->length);
    }

    public function test_support_pages_offer_a_return_to_the_help_center(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');

        foreach ([
            route('feedback.create'),
            route('feedback.index'),
            route('testimonials.create'),
            route('testimonials.index'),
        ] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('Back to Help Center');
        }
    }

    private function dom(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
