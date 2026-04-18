<?php

namespace Tests\Feature\Parent;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
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
        $this->assertSame(
            route('parent.children.enrollments.show', [$child, $enrollment, 'from' => 'notification']),
            data_get($parentNotification->data, 'action_url')
        );
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
                'reason_code' => 'others',
                'custom_reason' => $reason,
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
        $this->assertSame(
            route('parent.children.enrollments.show', [$child, $enrollment, 'from' => 'notification']),
            data_get($parentNotification->data, 'action_url')
        );
    }

    public function test_child_enrollment_request_notifies_parent_when_parent_approval_is_required(): void
    {
        [$parent, $child] = $this->createParentChildPair();

        LearnerProfile::updateOrCreate(
            ['user_id' => $child->id],
            [
                'username' => 'child-parent-approval-' . $child->id,
                'birthdate' => now()->subYears(10)->toDateString(),
                'requires_parental_consent' => true,
            ]
        );

        $module = Module::factory()->create([
            'enrollment_mode' => 'auto',
            'access_type' => 'free',
            'is_published' => true,
            'min_age' => 5,
            'max_age' => 13,
        ]);

        $this->withoutMiddleware(EnsureProfileCompleted::class)
            ->actingAs($child)
            ->post(route('learner.modules.enroll', $module))
            ->assertRedirect(route('learner.modules.index'));

        $notification = $parent->fresh()->notifications()->latest()->first();
        $enrollment = ModuleEnrollment::query()
            ->where('user_id', $child->id)
            ->where('module_id', $module->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotNull($notification);
        $this->assertSame('child_enrollment_approval_requested', data_get($notification->data, 'type'));
        $this->assertSame($child->id, data_get($notification->data, 'child_user_id'));
        $this->assertSame($module->id, data_get($notification->data, 'module_id'));
        $this->assertSame(
            route('parent.children.enrollments.show', [$child, $enrollment, 'from' => 'notification']),
            data_get($notification->data, 'action_url')
        );
    }

    public function test_parent_reject_custom_reason_must_be_3000_chars_or_less(): void
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

        $tooLongCustomReason = str_repeat('a', 3001);

        $this->actingAs($parent)
            ->from(route('parent.children.enrollments.show', [$child, $enrollment]))
            ->post(route('parent.children.enrollments.reject', [$child, $enrollment]), [
                'reason_code' => 'others',
                'custom_reason' => $tooLongCustomReason,
            ])
            ->assertSessionHasErrors(['custom_reason']);

        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'status' => 'pending_parent_approval',
        ]);
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
            'verification_status' => 'approved',
            'relationship_verified_at' => now(),
        ]);

        return [$parent, $child];
    }
}
