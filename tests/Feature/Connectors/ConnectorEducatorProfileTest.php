<?php

namespace Tests\Feature\Connectors;

use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectorEducatorProfileTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_directory_has_labeled_view_and_message_icon_actions(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $this->actingAs($host)->get(route('connector.educators', $connector))->assertOk()
            ->assertSee(route('connector.educators.show', [$connector, $instructor]), false)
            ->assertSee('aria-label="View instructor '.e($instructor->name).'"', false)
            ->assertSee('aria-label="Message instructor '.e($instructor->name).'"', false)
            ->assertSee('title="View profile"', false)->assertSee('title="Message instructor"', false)
            ->assertSee('data-educator-action="view"', false)->assertSee('data-educator-action="message"', false);
    }

    public function test_host_views_saved_profile_in_connector_workspace_without_private_account_or_verification_data(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $this->profile($instructor);
        $this->actingAs($host)->get(route('connector.educators.show', [$connector, $instructor]))
            ->assertOk()->assertViewIs('learner.instructors.show')
            ->assertSee('Back to Educators')->assertSee(route('connector.educators', $connector), false)
            ->assertSee($instructor->name)->assertSee('My instructor biography')->assertSee('Youth Education')
            ->assertSee('7 years of teaching experience')->assertSee('consent')->assertSee('School facilitator')
            ->assertSee('Certified Educator')->assertSee('Community Teaching Certificate')->assertSee('Training Council')
            ->assertSee('Example University')->assertSee('College Graduate')->assertDontSee('college_graduate')
            ->assertSee('/storage/avatars/instructor-profile.png', false)
            ->assertSee('/storage/instructor-certifications/public-certificate.pdf', false)
            ->assertSee(route('connector.educators.conversation', [$connector, $instructor]), false)
            ->assertSee('data-educator-action="message"', false)
            ->assertDontSee($instructor->email)->assertDontSee('instructor-applications/private-id.pdf')
            ->assertDontSee('private-clearance.pdf')->assertDontSee('Edit Profile')->assertDontSee('Impact Overview');
    }

    public function test_profile_changes_from_existing_instructor_management_are_visible_to_organizers(): void
    {
        Storage::fake('public');
        [$connector, $host, $instructor] = $this->context();
        $profile = $this->profile($instructor);
        $this->actingAs($instructor)->put(route('instructor.profile.update'), [
            'bio' => 'Updated by the instructor', 'primary_expertise' => 'Updated specialty',
        ])->assertRedirect(route('instructor.profile.show'));
        $this->actingAs($host)->get(route('connector.educators.show', [$connector, $instructor]))->assertOk()
            ->assertSee('Updated by the instructor')->assertSee('Updated specialty')->assertDontSee('My instructor biography');
        $this->assertSame($profile->id, $instructor->instructorProfile()->sole()->id);
    }

    public function test_view_uses_existing_manager_access_and_rejects_unavailable_targets(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $url = route('connector.educators.show', [$connector, $instructor]);
        $member = $this->createAdultConnectorMember($connector);
        $outsider = $this->createCompletedLearner();
        $this->actingAs($member)->get($url)->assertForbidden();
        $this->actingAs($outsider)->get($url)->assertForbidden();
        $manager = $this->createAdultConnectorMember($connector, ['connector.manage_educators']);
        $this->actingAs($manager)->get($url)->assertOk();
        $this->actingAs($host)->get(route('connector.educators.show', [$connector, $member]))->assertNotFound();
        $instructor->update(['status' => User::STATUS_SUSPENDED]);
        $this->actingAs($host)->get($url)->assertNotFound();
        $instructor->update(['status' => User::STATUS_ACTIVE]);
        $connector->update(['status' => 'suspended']);
        $this->actingAs($host)->get($url)->assertForbidden();
    }

    public function test_missing_profile_uses_empty_states_without_creating_a_record(): void
    {
        [$connector, $host, $instructor] = $this->context();
        $this->assertDatabaseCount('instructor_profiles', 0);
        $this->actingAs($host)->get(route('connector.educators.show', [$connector, $instructor]))->assertOk()
            ->assertSee($instructor->name)->assertSee('Bio not provided yet.')->assertSee('No certifications added yet.')
            ->assertSee('No educational background details provided yet.');
        $this->assertDatabaseCount('instructor_profiles', 0);
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_shared_learner_profile_keeps_its_existing_layout_and_privacy_rules(): void
    {
        [, , $instructor] = $this->context();
        $this->profile($instructor);
        $learner = $this->createCompletedLearner();
        $this->actingAs($learner)->get(route('learner.instructors.show', $instructor))->assertOk()
            ->assertViewIs('learner.instructors.show')->assertSee('My instructor biography')->assertSee('Certified Educator')
            ->assertSee('College Graduate')->assertDontSee('Back to Educators')
            ->assertDontSee('data-educator-action="message"', false)->assertDontSee('private-id.pdf');
    }

    private function context(): array
    {
        $connector = $this->createVerifiedConnector($this->createCompletedLearner());
        $host = $this->createAdultConnectorMember($connector, ['connector.manage_seminars']);
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => User::STATUS_ACTIVE]);
        $instructor->assignRole('instructor');

        return [$connector, $host, $instructor];
    }

    private function profile(User $instructor): InstructorProfile
    {
        return InstructorProfile::create([
            'user_id' => $instructor->id,
            'bio' => 'My instructor biography',
            'primary_expertise' => 'Youth Education',
            'expertise_tags' => ['consent'],
            'professional_background' => 'School facilitator',
            'years_experience' => 7,
            'profile_photo_path' => 'avatars/instructor-profile.png',
            'educational_background_entries' => [['school_name' => 'Example University', 'degree_program' => 'college_graduate', 'graduation_date' => '2020-05-01']],
            'credentials' => ['government_id_path' => 'instructor-applications/private-id.pdf', 'clearance_path' => 'private-clearance.pdf', 'Certified Educator'],
            'certifications' => [['title' => 'Community Teaching Certificate', 'organization' => 'Training Council', 'completion_date' => '2023-08-01', 'attachment_path' => 'instructor-certifications/public-certificate.pdf']],
        ]);
    }
}
