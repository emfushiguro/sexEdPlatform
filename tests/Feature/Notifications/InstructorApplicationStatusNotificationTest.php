<?php

namespace Tests\Feature\Notifications;

use App\Models\InstructorApplication;
use App\Models\User;
use App\Notifications\InstructorApplicationStatusUpdate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InstructorApplicationStatusNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reject_notification_contains_structured_reason_payload(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');
        $application = $this->createPendingApplication($learner);

        $this->actingAs($admin)
            ->post(route('admin.instructor-applications.reject', $application), [
                'rejection_reason_code' => 'invalid_credentials',
                'rejection_reason_note' => 'Professional license could not be verified from the issuing body.',
                'admin_message' => '<p>Professional license could not be verified from the issuing body.</p>',
            ])
            ->assertRedirect();

        Notification::assertSentTo($learner, InstructorApplicationStatusUpdate::class, function (InstructorApplicationStatusUpdate $notification, array $channels) use ($learner): bool {
            $payload = $notification->toArray($learner);

            return in_array('database', $channels, true)
                && $payload['status'] === 'rejected'
                && $payload['reason_code'] === 'invalid_credentials'
                && $payload['reason_label'] === 'Invalid or unverifiable credentials'
                && $payload['reason_note'] === 'Professional license could not be verified from the issuing body.'
                && str_contains((string) $payload['readable_reason'], 'Professional license could not be verified');
        });
    }

    private function createPendingApplication(User $learner): InstructorApplication
    {
        return InstructorApplication::query()->create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'Bachelor of Education',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'bio' => 'Experienced educator focused on adolescent development.',
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
