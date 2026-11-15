<?php

namespace Tests\Unit;

use App\Http\Controllers\Learner\NotificationController;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\AdultAuthorityReviewStatusNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;

class AdultAuthorityNotificationClickTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('parent_child_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_user_id');
            $table->softDeletes();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        DB::table('parent_child_accounts')->insert(['id' => 7, 'parent_user_id' => 1]);
    }

    public function test_current_and_old_host_notifications_open_the_owned_relationship_and_mark_read(): void
    {
        $guardian = $this->guardian(1);
        $relationship = (new ParentChildAccount)->forceFill(['id' => 7]);
        $current = (new AdultAuthorityReviewStatusNotification($relationship, 'approved'))->toDatabase($guardian);

        foreach ([$current, ['type' => 'adult_authority_review_status', 'action_url' => 'https://old-host.example/parent/relationship-verifications/7']] as $payload) {
            $notification = $this->notification($guardian, $payload);
            Auth::shouldReceive('user')->andReturn($guardian);

            $response = app(NotificationController::class)->markRead($notification->id);

            $this->assertSame(route('parent.relationship-verifications.show', 7), $response->getTargetUrl());
            $this->assertNotNull($notification->fresh()->read_at);
        }
    }

    public function test_click_cannot_open_another_guardians_relationship(): void
    {
        $guardian = $this->guardian(2);
        $notification = $this->notification($guardian, [
            'type' => 'adult_authority_review_status', 'parent_child_account_id' => 7,
            'action_url' => 'https://old-host.example/parent/relationship-verifications/7',
        ]);
        Auth::shouldReceive('user')->andReturn($guardian);

        $response = app(NotificationController::class)->markRead($notification->id);

        $this->assertSame(route('learner.notifications.index'), $response->getTargetUrl());
    }

    private function guardian(int $id): User
    {
        $guardian = Mockery::mock(User::class)->makePartial();
        $guardian->id = $id;
        $guardian->shouldReceive('isAdmin')->andReturnFalse();
        $guardian->shouldReceive('isParentRegistration')->andReturnTrue();

        return $guardian;
    }

    private function notification(User $guardian, array $payload): \Illuminate\Notifications\DatabaseNotification
    {
        return $guardian->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => AdultAuthorityReviewStatusNotification::class, 'data' => $payload,
        ]);
    }
}
