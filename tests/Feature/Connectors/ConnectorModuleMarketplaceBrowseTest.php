<?php

namespace Tests\Feature\Connectors;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\User;
use Tests\TestCase;

class ConnectorModuleMarketplaceBrowseTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_module_published_by_an_instructor_without_a_subscription_is_visible_to_connectors(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'current_review_status' => 'approved',
            'title' => 'Approved Instructor Connector Module',
        ]);
        $this->actingAs($instructor)->put(route('instructor.modules.marketplace.update', $module), [
            'action' => 'publish',
            'price_amount' => 5000,
            'currency' => config('billing.currency.code'),
            'seat_capacity' => 50,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($instructor->subscriptions()->exists());

        $manager = User::factory()->create();
        $connector = $this->createVerifiedConnector($manager);
        $member = $this->createAdultConnectorMember($connector);
        $this->actingAs($member)->get(route('connector.modules', $connector))
            ->assertOk()->assertSee($module->title)->assertSee('50 seats');
    }

    public function test_active_members_can_browse_published_listings_but_only_managers_can_purchase(): void
    {
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $ordinaryMember = $this->createAdultConnectorMember($connector);
        $module = Module::factory()->create([
            'is_published' => true,
            'current_review_status' => 'approved',
            'min_age' => 5,
            'max_age' => 100,
        ]);
        $listing = ModuleMarketplaceListing::create([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
        ]);

        $response = $this->actingAs($ordinaryMember)
            ->get(route('connector.modules', $connector));

        $response->assertOk()
            ->assertSee($module->title)
            ->assertSee('10 seats')
            ->assertDontSee('Purchase for Connector');

        $this->actingAs($owner)
            ->get(route('connector.modules', $connector))
            ->assertOk()
            ->assertSee('Purchase for Connector');

        $this->assertNotNull($listing->id);
    }

    public function test_detail_exposes_lesson_metadata_without_protected_topic_content(): void
    {
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $member = $this->createAdultConnectorMember($connector);
        $module = Module::factory()->create([
            'is_published' => true,
            'current_review_status' => 'approved',
            'min_age' => 5,
            'max_age' => 100,
        ]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Visible Lesson Title',
        ]);
        LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'text_content' => '<p>Protected topic body</p>',
        ]);
        $listing = ModuleMarketplaceListing::create([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
        ]);

        $this->actingAs($member)
            ->get(route('connector.modules.marketplace.show', [$connector, $listing]))
            ->assertOk()
            ->assertSee('Visible Lesson Title')
            ->assertDontSee('Protected topic body', false);
    }
}
