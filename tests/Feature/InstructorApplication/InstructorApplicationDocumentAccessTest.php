<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Admin\RoleSyncService;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InstructorApplicationDocumentAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['instructor_application.enabled' => true]);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_owner_and_authorized_admin_can_stream_private_documents_with_no_store_headers(): void
    {
        [$owner, $application] = $this->application();
        Storage::disk('local')->put($application->government_id_path, 'synthetic evidence');
        $admin = $this->admin();

        $ownerResponse = $this->actingAs($owner)->get(route('instructor-application.application.documents.show', [$application, 'government_id']));
        $ownerResponse->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($admin)
            ->get(route('admin.instructor-applications.documents.show', [$application, 'government_id']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_other_applicant_and_unallowlisted_slots_are_denied(): void
    {
        [$owner, $application] = $this->application();
        Storage::disk('local')->put($application->government_id_path, 'synthetic evidence');
        $other = User::factory()->create(['role' => User::ACCOUNT_TYPE_APPLICANT]);

        $this->actingAs($other)
            ->get(route('instructor-application.application.documents.show', [$application, 'government_id']))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('instructor-application.application.documents.show', [$application, 'secret']))
            ->assertNotFound();
    }

    private function application(): array
    {
        $owner = User::factory()->create(['role' => User::ACCOUNT_TYPE_APPLICANT, 'email_verified_at' => now()]);
        app(RoleSyncService::class)->assignPrimaryRole($owner, User::ACCOUNT_TYPE_APPLICANT);
        $application = InstructorApplication::create([
            'user_id' => $owner->id,
            'status' => 'pending',
            'document_disk' => 'local',
            'government_id_path' => 'instructor-applications/'.$owner->id.'/id.pdf',
            'clearance_path' => 'instructor-applications/'.$owner->id.'/clearance.pdf',
            'cv_resume_path' => 'instructor-applications/'.$owner->id.'/cv.pdf',
            'bio' => 'Synthetic application',
            'application_metadata' => [
                'documents' => [
                    'government_id' => [
                        'disk' => 'local',
                        'original_name' => 'id.pdf',
                    ],
                ],
            ],
        ]);

        return [$owner->fresh(), $application];
    }

    private function admin(): User
    {
        Permission::findOrCreate('review instructor applications', 'web');
        $admin = User::factory()->create(['role' => 'admin']);
        app(RoleSyncService::class)->assignPrimaryRole($admin, 'admin');
        $admin->givePermissionTo('review instructor applications');

        return $admin->fresh();
    }
}
