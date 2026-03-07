<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RewardLog;
use App\Models\User;
use App\Models\UserGamification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentChildMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_enrollments_accepts_pending_parent_approval_status(): void
    {
        $parent = User::factory()->create(['email_verified_at' => now()]);
        $parent->assignRole('learner');

        $child = User::factory()->create(['email_verified_at' => now()]);
        $child->assignRole('learner');

        $module = Module::factory()->create();

        $enrollment = ModuleEnrollment::create([
            'user_id'    => $child->id,
            'module_id'  => $module->id,
            'status'     => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->assertDatabaseHas('module_enrollments', [
            'id'     => $enrollment->id,
            'status' => 'pending_parent_approval',
        ]);
    }
}
