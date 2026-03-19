<?php

namespace Tests\Feature\Instructor;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Learner\EnrollmentRejectedNotification;
use Tests\TestCase;

class EnrollmentDecisionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reject_requires_reason_payload(): void
    {
        $instructor = $this->createInstructor();
        [$module, $enrollment] = $this->createPendingEnrollmentForInstructor($instructor);

        $response = $this->actingAs($instructor)->patch(route('instructor.enrollments.reject', $enrollment), []);

        $response->assertSessionHasErrors(['rejection_reason_code']);
        $this->assertSame(EnrollmentStatus::Pending, $enrollment->fresh()->status);
    }

    public function test_reject_emits_learner_notification(): void
    {
        Notification::fake();

        $instructor = $this->createInstructor();
        [$module, $enrollment, $learner] = $this->createPendingEnrollmentForInstructor($instructor, withLearner: true);

        $response = $this->actingAs($instructor)->patch(route('instructor.enrollments.reject', $enrollment), [
            'rejection_reason_code' => 'prerequisite_missing',
            'rejection_reason_note' => 'Please complete Intro first.',
        ]);

        $response->assertRedirect();

        Notification::assertSentTo($learner, EnrollmentRejectedNotification::class);

        $notificationPayload = $learner->fresh()->notifications()->latest()->first()?->data ?? [];

        $this->assertStringContainsString($module->title, (string) ($notificationPayload['message'] ?? ''));
        $this->assertStringContainsString('Please complete Intro first.', (string) ($notificationPayload['message'] ?? ''));
        $this->assertStringContainsString((string) ($instructor->full_name ?: $instructor->name), (string) ($notificationPayload['message'] ?? ''));
    }

    private function createInstructor(): User
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        return $instructor;
    }

    /**
     * @return array{0: Module, 1: ModuleEnrollment, 2?: User}
     */
    private function createPendingEnrollmentForInstructor(User $instructor, bool $withLearner = false): array
    {
        $module = Module::factory()->create([
            'title' => 'Boundaries 101',
            'created_by' => $instructor->id,
        ]);

        $learner = User::factory()->create([
            'first_name' => 'Luna',
            'last_name' => 'Santos',
            'email' => 'luna@example.test',
            'role' => 'learner',
        ]);
        $learner->assignRole('learner');

        $enrollment = ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        if ($withLearner) {
            return [$module, $enrollment, $learner];
        }

        return [$module, $enrollment];
    }
}
