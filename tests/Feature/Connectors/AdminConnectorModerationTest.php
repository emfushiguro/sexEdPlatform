<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminConnectorModerationTest extends TestCase
{
    use ConnectorTestHelpers;

    private const CHECKLIST = [
        'organization_identity_established' => '1',
        'regulatory_evidence_resolved' => '1',
        'representative_identity_verified' => '1',
        'representative_authority_established' => '1',
        'required_evidence_complete' => '1',
        'application_consistent' => '1',
    ];

    public function test_admin_can_approve_reject_and_suspend_connectors(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $owner = User::factory()->create(['role' => 'learner']);
        $connector = $this->submitInitialApplication($owner);
        $this->completeConnectorEvidence($connector);

        $this->actingAs($owner)->get(route('admin.connectors.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.connectors.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($connector->name);

        $this->actingAs($admin)->post(route('admin.connectors.approve', $connector), [
            'submission_round' => 1,
            'checklist' => self::CHECKLIST,
        ])->assertRedirect();
        $this->assertSame('verified', $connector->fresh()->status);
        $this->assertTrue($connector->memberships()->where('status', 'active')->exists());

        $this->actingAs($admin)->post(route('admin.connectors.suspend', $connector), [
            'reason' => 'Policy issue',
        ])->assertRedirect();
        $this->assertSame('suspended', $connector->fresh()->status);

        $this->actingAs($admin)->post(route('admin.connectors.reject', $connector), [
            'submission_round' => 1,
            'reason_code' => 'policy_violation',
            'applicant_reason' => 'Incomplete profile',
        ])->assertRedirect();
        $this->assertSame('rejected', $connector->fresh()->status);
        $this->actingAs($owner)->get(route('connector.status', $connector))
            ->assertOk()
            ->assertSee('Incomplete profile');
    }

    public function test_reject_and_suspend_require_reason(): void
    {
        $this->seedCaviteAddress();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $connector = $this->createVerifiedConnector(User::factory()->create());

        $this->actingAs($admin)
            ->from(route('admin.connectors.show', $connector))
            ->post(route('admin.connectors.reject', $connector), [])
            ->assertSessionHasErrors(['submission_round', 'reason_code', 'applicant_reason']);

        $this->actingAs($admin)
            ->from(route('admin.connectors.show', $connector))
            ->post(route('admin.connectors.suspend', $connector), [])
            ->assertSessionHasErrors('reason');
    }

    private function submitInitialApplication(User $owner): Connector
    {
        $this->seedCaviteAddress();

        $this->actingAs($owner)
            ->post(route('connectors.store'), $this->connectorPayload())
            ->assertRedirect();

        return Connector::query()->latest('id')->firstOrFail();
    }

    private function completeConnectorEvidence(Connector $connector): void
    {
        foreach ($connector->verificationRequirements as $requirement) {
            if ($requirement->requirement_key === 'activity_specific_authority') {
                $requirement->update(['status' => ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE]);

                continue;
            }

            $requirement->documents()->update([
                'status' => ConnectorVerificationDocument::STATUS_ACCEPTED,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            $requirement->update(['status' => ConnectorVerificationRequirement::STATUS_SATISFIED]);
        }
    }
}
