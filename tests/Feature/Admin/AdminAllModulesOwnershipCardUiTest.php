<?php

namespace Tests\Feature\Admin;

use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminAllModulesOwnershipCardUiTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_modules_cards_render_compact_owner_avatar_row_and_hide_instructor_mutation_actions(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        InstructorProfile::query()->create([
            'user_id' => $instructor->id,
            'bio' => 'Instructor profile bio',
            'profile_photo_path' => 'instructors/demo-avatar.jpg',
        ]);

        $platformModule = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'title' => 'Platform Visible Actions',
        ]);

        $instructorModule = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor View Only Module',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.modules.index'));

        $response->assertOk();
        $response->assertSee('data-testid="module-owner-avatar"', false);
        $response->assertSee('data-owner-type="instructor"', false);
        $response->assertSee('data-owner-type="admin"', false);

        $response->assertSee(route('admin.modules.destroy', $platformModule), false);

        $content = $response->getContent();
        $instructorCardOffset = strpos($content, 'data-owner-type="instructor"');

        $this->assertNotFalse($instructorCardOffset);

        $instructorCardFragment = substr($content, (int) $instructorCardOffset, 1800);
        $this->assertStringNotContainsString(route('admin.modules.destroy', $instructorModule), $instructorCardFragment);

    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}