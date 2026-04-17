<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLearnerPlanEntitlementDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_feature_catalog_api_includes_translator_first_core_defaults(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->getJson(route('admin.api.features', ['audience' => 'learner']));

        $response->assertOk();

        $featureKeys = collect($response->json('features'))
            ->pluck('key')
            ->all();

        $this->assertContains('unlimited_username_change', $featureKeys);
        $this->assertContains('unlimited_quiz_shields', $featureKeys);
        $this->assertContains('text_translator', $featureKeys);
        $this->assertContains('voice_speech_translator', $featureKeys);
    }
}
