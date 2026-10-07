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
