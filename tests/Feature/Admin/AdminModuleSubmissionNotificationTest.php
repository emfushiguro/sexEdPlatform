<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\User;
use App\Notifications\Admin\NewModuleSubmissionNotification;
use App\Services\ContentGovernanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

class AdminModuleSubmissionNotificationTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_submit_for_review_notifies_admins_with_review_queue_link(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('admin');
        $instructor = $this->createUserWithRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => null,
        ]);

        $reviewRequest = app(ContentGovernanceService::class)->submitForReview($module, $instructor);

        Notification::assertSentTo(
            [$admin],
            NewModuleSubmissionNotification::class,
            fn (NewModuleSubmissionNotification $notification) => data_get($notification->toDatabase($admin), 'review_request_id') === $reviewRequest->id
                && str_contains((string) data_get($notification->toDatabase($admin), 'action_url'), 'content-reviews')
        );
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
