<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectorModuleCertificateHistoryTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorLearnerAccessHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_issued_certificate_remains_viewable_and_downloadable_after_connector_access_ends(): void
    {
        $fixture = $this->connectorLearnerFixture();
        $this->revokeConnectorLearnerAccess($fixture);

        $this->actingAs($fixture['learner'])
            ->get(route('learner.certificates.show', $fixture['certificate']))
            ->assertOk()
            ->assertSee($fixture['certificate']->certificate_number);

        $this->actingAs($fixture['learner'])
            ->get(route('learner.certificates.download', $fixture['certificate']))
            ->assertOk();

        $this->actingAs($fixture['learner'])
            ->post(route('learner.certificates.check', $fixture['module']))
            ->assertForbidden();
    }
}
