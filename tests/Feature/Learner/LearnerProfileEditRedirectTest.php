<?php

namespace Tests\Feature\Learner;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerProfileEditRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_profile_edit_route_redirects_to_dashboard_with_modal_flag(): void
    {
        $learner = User::factory()->create();
        $learner->assignRole('learner');

        $response = $this->actingAs($learner)
            ->get(route('profile.learner.edit'));

        $response->assertRedirect(
            route('learner.dashboard', ['open_edit_profile' => 1], false)
        );
    }
}
