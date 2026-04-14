<?php

namespace Tests\Feature\Instructor;

use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorProfileUpdateSecurityTest extends TestCase
{
    public function test_instructor_can_update_whitelisted_profile_fields(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        InstructorProfile::create([
            'user_id' => $instructor->id,
            'bio' => 'Old bio',
        ]);

        $this->actingAs($instructor)
            ->put(route('instructor.profile.update'), [
                'bio' => 'Updated bio',
                'educational_background' => 'BS Psychology',
                'professional_background' => 'Facilitator',
                'primary_expertise' => 'Adolescent Education',
                'years_experience' => 5,
            ])
            ->assertRedirect(route('instructor.profile.show'));

        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $instructor->id,
            'bio' => 'Updated bio',
            'educational_background' => 'BS Psychology',
            'professional_background' => 'Facilitator',
            'primary_expertise' => 'Adolescent Education',
            'years_experience' => 5,
        ]);
    }

    public function test_restricted_fields_are_ignored_and_role_is_not_mutated(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        InstructorProfile::create([
            'user_id' => $instructor->id,
            'bio' => 'Initial bio',
        ]);

        $this->actingAs($instructor)
            ->put(route('instructor.profile.update'), [
                'bio' => 'Still editable',
                'role' => 'admin',
                'approved_by' => 999,
                'modules_created' => 999,
            ])
            ->assertRedirect(route('instructor.profile.show'));

        $this->assertDatabaseHas('users', [
            'id' => $instructor->id,
            'role' => 'instructor',
        ]);

        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $instructor->id,
            'bio' => 'Still editable',
        ]);
    }

    public function test_instructor_can_update_avatar_and_array_fields(): void
    {
        Storage::fake('public');

        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        // Instead of ->image(), use create to avoid GD extension dependency locally
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $certificateProof = UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf');

        $response = $this->actingAs($instructor)
            ->put(route('instructor.profile.update'), [
                'bio' => 'New bio',
                'profile_photo' => $file,
                'expertise_tags' => ['Laravel', 'PHP'],
                'certifications' => [[
                    'title' => 'AWS Certified Educator',
                    'organization' => 'Amazon Web Services',
                    'completion_date' => '2024-07-15',
                    'attachment' => $certificateProof,
                ]],
                'educational_background_entries' => [
                    [
                        'school_name' => 'University of the Philippines',
                        'degree_program' => 'BSc Computer Science',
                        'graduation_date' => '2020-04-15',
                    ],
                    [
                        'school_name' => 'Ateneo de Manila University',
                        'degree_program' => 'Master of Education',
                        'graduation_date' => '2024-06-01',
                    ],
                ],
                'credentials' => ['BSc Computer Science'],
            ]);

        $response->assertRedirect(route('instructor.profile.show'));

        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $instructor->id,
            'bio' => 'New bio',
        ]);

        $profile = InstructorProfile::where('user_id', $instructor->id)->first();
        $this->assertNotNull($profile);

        $this->assertEquals(['Laravel', 'PHP'], $profile->expertise_tags);
        $this->assertCount(1, $profile->certifications);
        $this->assertSame('AWS Certified Educator', $profile->certifications[0]['title']);
        $this->assertSame('Amazon Web Services', $profile->certifications[0]['organization']);
        $this->assertSame('2024-07-15', $profile->certifications[0]['completion_date']);
        $this->assertNotNull($profile->certifications[0]['attachment_path']);
        $this->assertCount(2, $profile->educational_background_entries);
        $this->assertStringContainsString('BSc Computer Science - University of the Philippines (2020-04-15)', (string) $profile->educational_background);
        $this->assertNotNull($profile->profile_photo_path);
        $this->assertTrue(Storage::disk('public')->exists((string) $profile->profile_photo_path));
        $this->assertTrue(Storage::disk('public')->exists((string) $profile->certifications[0]['attachment_path']));
    }
}
