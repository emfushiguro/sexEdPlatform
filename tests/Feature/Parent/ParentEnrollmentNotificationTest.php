<?php

namespace Tests\Feature\Parent;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentEnrollmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_approval_notifies_child_and_parent_accounts(): void
    {
        [$parent, $child] = $this->createParentChildPair();

        $module = Module::factory()->create([
            'enrollment_mode' => 'auto',
            'access_type' => 'free',
        ]);

        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
            ->post(route('parent.children.enrollments.approve', [$child, $enrollment]))
            ->assertRedirect(route('parent.children.show', $child));

        $childNotification = $child->fresh()->notifications()->latest()->first();
        $parentNotification = $parent->fresh()->notifications()->latest()->first();

        $this->assertNotNull($childNotification);
        $this->assertNotNull($parentNotification);
        $this->assertSame('parent_enrollment_approved', data_get($childNotification->data, 'type'));
        $this->assertSame('child_enrollment_approved', data_get($parentNotification->data, 'type'));
    }

    public function test_parent_rejection_includes_reason_when_available(): void
    {
        [$parent, $child] = $this->createParentChildPair();

        $module = Module::factory()->create([
            'enrollment_mode' => 'manual',
            'access_type' => 'free',
        ]);

        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $reason = 'Please complete current module first.';

        $this->actingAs($parent)
            ->post(route('parent.children.enrollments.reject', [$child, $enrollment]), [
                'reason' => $reason,
            ])
            ->assertRedirect(route('parent.children.show', $child));

        $childNotification = $child->fresh()->notifications()->latest()->first();
        $parentNotification = $parent->fresh()->notifications()->latest()->first();

        $this->assertNotNull($childNotification);
        $this->assertNotNull($parentNotification);
        $this->assertSame('parent_enrollment_rejected', data_get($childNotification->data, 'type'));
        $this->assertSame($reason, data_get($childNotification->data, 'reason'));
        $this->assertStringContainsString($reason, (string) data_get($childNotification->data, 'message'));

        $this->assertSame('child_enrollment_rejected', data_get($parentNotification->data, 'type'));
        $this->assertSame($reason, data_get($parentNotification->data, 'reason'));
    }

    private function createParentChildPair(): array
    {
        $parent = User::factory()->create(['email_verified_at' => now(), 'role' => 'learner']);
        $parent->assignRole('learner');

        $child = User::factory()->create(['email_verified_at' => now(), 'role' => 'learner']);
        $child->assignRole('learner');

        ParentChildAccount::create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
        ]);

        return [$parent, $child];
    }
}
