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
        
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        
        // Instead of ->image(), use create to avoid GD extension dependency locally
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($instructor)
            ->put(route('instructor.profile.update'), [
                'bio' => 'New bio',
                'profile_photo' => $file,
                'expertise_tags' => ['Laravel', 'PHP'],
                'certifications' => ['AWS Certified'],
                'credentials' => ['BSc Computer Science']
            ]);

        $response->assertRedirect(route('instructor.profile.show'));
        
        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $instructor->id,
            'bio' => 'New bio',
        ]);
        
        $profile = InstructorProfile::where('user_id', $instructor->id)->first();
        
        $this->assertEquals(['Laravel', 'PHP'], $profile->expertise_tags);
        $this->assertNotNull($profile->profile_photo_path);
        Storage::disk('public')->assertExists($profile->profile_photo_path);
    }
}
