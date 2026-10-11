<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectorProvidedModuleAccessTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorLearnerAccessHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_active_connector_assignment_authorizes_learning_endpoints_and_shows_source(): void
    {
        $fixture = $this->connectorLearnerFixture();
        $learner = $fixture['learner'];

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $fixture['module']))
            ->assertOk()
            ->assertSee('Provided by '.$fixture['connector']->name, false);

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $fixture['lesson']))
            ->assertOk();

        $this->actingAs($learner)
            ->get(route('quizzes.start', $fixture['quiz']))
            ->assertOk();

        $this->actingAs($learner)
            ->getJson(route('learner.interactive-activities.show', $fixture['activity']))
            ->assertOk();

        $this->actingAs($learner)
            ->postJson(route('learner.checkpoints.skip', $fixture['checkpoint']))
            ->assertOk()
            ->assertJsonPath('status', 'skipped');
    }
}
