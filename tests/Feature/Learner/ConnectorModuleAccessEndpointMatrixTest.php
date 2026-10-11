<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectorModuleAccessEndpointMatrixTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorLearnerAccessHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_revoked_connector_access_blocks_learning_mutations_and_preserves_safe_history(): void
    {
        $fixture = $this->connectorLearnerFixture();
        $this->revokeConnectorLearnerAccess($fixture);
        $learner = $fixture['learner'];

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $fixture['module']))
            ->assertOk()
            ->assertSee('Past access');

        $this->actingAs($learner)->get(route('learner.lessons.show', $fixture['lesson']))->assertForbidden();
        $this->actingAs($learner)->post(route('learner.lessons.complete', $fixture['lesson']))->assertForbidden();
        $this->actingAs($learner)->post(route('learner.topics.complete', $fixture['topic']))->assertForbidden();
        $this->actingAs($learner)->post(route('learner.topics.uncomplete', $fixture['topic']))->assertForbidden();
        $this->actingAs($learner)->get(route('quizzes.start', $fixture['quiz']))->assertForbidden();
        $this->actingAs($learner)->post(route('quizzes.submit', $fixture['quiz']), ['answers' => []])->assertForbidden();

        $this->actingAs($learner)
            ->putJson(route('learner.interactive-activities.state', $fixture['activity']), [
                'revision' => 1,
                'state' => ['working' => true],
            ])
            ->assertForbidden();
        $this->actingAs($learner)->postJson(route('learner.checkpoints.skip', $fixture['checkpoint']))->assertForbidden();
        $this->actingAs($learner)
            ->postJson(route('learner.topics.translate', $fixture['topic']), ['target_language' => 'en'])
            ->assertForbidden();
        $this->actingAs($learner)->get(route('learner.modules.reviews', $fixture['module']))->assertForbidden();
        $this->actingAs($learner)
            ->post(route('learner.modules.feedback.store', $fixture['module']), [
                'feedback_type' => 'module',
                'rating' => 5,
                'review_content' => 'Access should be required.',
            ])
            ->assertForbidden();
        $this->actingAs($learner)->get(route('learner.modules.completion', $fixture['module']))->assertForbidden();
        $this->actingAs($learner)->post(route('learner.certificates.check', $fixture['module']))->assertForbidden();
    }
}
