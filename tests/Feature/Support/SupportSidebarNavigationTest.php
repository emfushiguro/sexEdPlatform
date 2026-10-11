<?php

namespace Tests\Feature\Support;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SupportSidebarNavigationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_global_role_sidebars_keep_support_actions_inside_help_center(): void
    {
        $iconMarkupByRole = [];

        foreach (['learner', 'parent', 'instructor'] as $role) {
            $storedRole = $role === 'parent' ? 'learner' : $role;
            $user = User::factory()->create([
                'role' => $storedRole,
                'account_type' => $role === 'parent' ? 'parent' : ($role === 'learner' ? 'learner-adult' : $role),
                'age' => 25,
                'birthdate' => now()->subYears(25),
            ]);
            $user->assignRole($storedRole);

            $response = $this->actingAs($user)->get(route('help.index'))->assertOk();
            $xpath = $this->dom($response->getContent());

            $this->assertSame(1, $xpath->query('//aside//*[@data-support-section="user"]')->length, "$role sidebar is missing its dedicated Support section.");

            $helpLink = $xpath->query('//aside//a[@data-support-nav="help"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $helpLink, "$role sidebar is missing the Help Center link.");
            $this->assertSame(route('help.index'), $helpLink->getAttribute('href'));
            $this->assertSame('Help Center', $helpLink->getAttribute('aria-label'));
            $this->assertSame('Help Center', $helpLink->getAttribute('title'));
            $this->assertStringContainsString('focus-visible:ring-2', $helpLink->getAttribute('class'), "$role Help Center link has no visible keyboard-focus treatment.");
            $this->assertSame(1, $xpath->query('//aside//*[@data-support-section="user"]//a[@data-support-nav="help"]')->length);
            $this->assertSame(0, $xpath->query('//aside//*[@data-support-section="user"]//a[@data-support-nav="feedback"]')->length);
            $this->assertSame(0, $xpath->query('//aside//*[@data-support-section="user"]//a[@data-support-nav="my-feedback"]')->length);
            $this->assertSame(0, $xpath->query('//aside//*[@data-support-section="user"]//a[@data-support-nav="testimonial"]')->length);

            $icon = $xpath->query('.//svg[@data-support-icon="help"]', $helpLink)->item(0);
            $this->assertInstanceOf(DOMElement::class, $icon, "$role sidebar is not using the shared Help Center icon.");
            $iconMarkupByRole['help'][$role] = $icon->C14N();
        }

        $this->assertCount(1, array_unique($iconMarkupByRole['help']), 'The Help Center icon differs between role sidebars.');
    }

    public function test_connector_support_navigation_preserves_workspace_context_and_shared_icons(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);

        $globalXpath = $this->dom(
            $this->actingAs($owner)->get(route('help.index'))->assertOk()->getContent()
        );
        $connectorXpath = $this->dom(
            $this->get(route('connector.help.index', $connector))->assertOk()->getContent()
        );

        $this->assertSame(1, $connectorXpath->query('//aside//*[@data-support-section="user"]')->length);

        foreach (['help' => route('connector.help.index', $connector)] as $name => $expectedUrl) {
            $link = $connectorXpath->query('//aside//a[@data-support-nav="'.$name.'"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $link);
            $this->assertSame($expectedUrl, $link->getAttribute('href'));

            $connectorIcon = $connectorXpath->query('.//svg[@data-support-icon="'.$name.'"]', $link)->item(0);
            $globalIcon = $globalXpath->query('//aside//a[@data-support-nav="'.$name.'"]//svg[@data-support-icon="'.$name.'"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $connectorIcon);
            $this->assertInstanceOf(DOMElement::class, $globalIcon);
            $this->assertSame($globalIcon->C14N(), $connectorIcon->C14N(), "The connector $name icon differs from the shared role icon.");
        }
    }

    public function test_support_active_state_separates_feedback_creation_and_history_for_every_role_shell(): void
    {
        foreach (['learner', 'parent', 'instructor'] as $role) {
            $storedRole = $role === 'parent' ? 'learner' : $role;
            $user = User::factory()->create([
                'role' => $storedRole,
                'account_type' => $role === 'parent' ? 'parent' : ($role === 'learner' ? 'learner-adult' : $role),
                'age' => 25,
                'birthdate' => now()->subYears(25),
            ]);
            $user->assignRole($storedRole);

            $xpath = $this->dom(
                $this->actingAs($user)->get(route('feedback.index'))->assertOk()->getContent()
            );

            $helpLink = $xpath->query('//aside//a[@data-support-nav="help"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $helpLink);
            $this->assertSame('', $helpLink->getAttribute('aria-current'));
            $this->assertSame(0, $xpath->query('//aside[@data-support-section="user"]//a[@data-support-nav="feedback"]')->length);
            $this->assertSame(0, $xpath->query('//aside[@data-support-section="user"]//a[@data-support-nav="my-feedback"]')->length);
        }
    }

    public function test_connector_feedback_history_keeps_the_my_feedback_item_active(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);

        $xpath = $this->dom(
            $this->actingAs($owner)->get(route('connector.feedback.index', $connector))->assertOk()->getContent()
        );

        $this->assertSame(1, $xpath->query('//aside//a[@data-support-nav="help"]')->length);
        $this->assertSame(0, $xpath->query('//aside//a[@data-support-nav="feedback"]')->length);
        $this->assertSame(0, $xpath->query('//aside//a[@data-support-nav="my-feedback"]')->length);
    }

    public function test_admin_management_active_state_is_separate_from_personal_platform_feedback(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_type' => 'admin',
            'age' => 30,
            'birthdate' => now()->subYears(30),
        ]);
        $admin->assignRole('admin');

        $xpath = $this->dom(
            $this->actingAs($admin)->get(route('admin.feedback.index'))->assertOk()->getContent()
        );

        $managementLink = $xpath->query('//aside//*[@data-support-section="management"]//a[@href="'.route('admin.feedback.index').'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $managementLink);
        $this->assertSame('page', $managementLink->getAttribute('aria-current'));
        $this->assertSame(0, $xpath->query('//aside[@data-support-section="user"]')->length);
    }

    private function dom(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
