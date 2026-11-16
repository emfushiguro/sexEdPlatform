<?php

namespace Tests\Feature\Admin\Moderation;

use App\Models\AdminActivityLog;
use App\Models\Conversation;
use App\Models\EnforcementAction;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\Moderation\SourceAdapters\ChatReportModerationAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class SuspensionDurationChangeTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected function report(): array
    {
        Notification::fake();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-16 12:00:00', 'UTC'));
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $reporter = User::factory()->create(['role' => 'learner']);
        $reporter->assignRole('learner');
        $user = User::factory()->create(['role' => 'instructor']);
        $user->assignRole('instructor');
        $conversation = Conversation::create(['participant_one_id' => $reporter->id, 'participant_two_id' => $user->id,
            'pair_key' => Conversation::makePairKey($reporter->id, $user->id), 'conversation_type' => 'direct', 'status' => 'active',
            'context_key' => Conversation::makeContextKey('direct', null)]);
        $message = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $user->id, 'message_body' => 'Test report context.']);
        $report = MessageReport::create(['message_id' => $message->id, 'conversation_id' => $conversation->id,
            'reporter_id' => $reporter->id, 'status' => 'open', 'reason_code' => 'harassment']);
        app(ChatReportModerationAdapter::class)->syncReport($report);
        $case = ModerationCase::where('content_type', 'message_report')->where('content_id', $report->id)->firstOrFail();
        $this->actingAs($admin);

        return [$admin, $user, $case, $report];
    }

    protected function issue(ModerationCase $case, int $amount = 8, string $unit = 'hours')
    {
        return $this->put(route('admin.moderation-suspensions.reports.update', $case), [
            'status' => 'resolved', 'action_taken' => 'temporary_suspension', 'duration_amount' => $amount,
            'duration_unit' => $unit, 'moderation_notes' => 'Private report notes.']);
    }

    public function test_temporary_duration_survives_redirect_and_reopen(): void
    {
        [, , $case] = $this->report();
        $this->issue($case)->assertSessionHasNoErrors();
        $this->get(route('admin.moderation-suspensions.reports.show', $case))->assertOk()
            ->assertSee('value="8"', false)->assertSee('Hours')->assertSeeText('Change duration');
        $this->assertSame(['amount' => 8, 'unit' => 'hours'], data_get($case->fresh()->metadata, 'chat_temporary_suspension.initial'));
        $this->assertSame('2026-11-16 20:00:00', UserSuspension::first()->ends_at->toDateTimeString());
        $this->issue($case)->assertSessionHasNoErrors();
        $this->assertSame(1, UserSuspension::count());
        $this->assertSame(1, EnforcementAction::count());
        $this->issue($case, 12)->assertSessionHasErrors('duration_amount');
        $this->assertSame('2026-11-16 20:00:00', UserSuspension::first()->ends_at->toDateTimeString());
    }

    public function test_days_and_validation_errors_preserve_the_selected_values(): void
    {
        [, , $case] = $this->report();
        $this->from(route('admin.moderation-suspensions.reports.show', $case))->put(route('admin.moderation-suspensions.reports.update', $case),
            ['status' => 'invalid', 'action_taken' => 'temporary_suspension', 'duration_amount' => 12, 'duration_unit' => 'hours', 'moderation_notes' => 'Retain my unsaved note.'])->assertSessionHasErrors('status');
        $this->get(route('admin.moderation-suspensions.reports.show', $case))->assertSee('value="12"', false)->assertSeeText('Retain my unsaved note.');
        $this->issue($case, 2, 'days')->assertSessionHasNoErrors();
        $this->assertSame(['amount' => 2, 'unit' => 'days'], data_get($case->fresh()->metadata, 'chat_temporary_suspension.current'));
        $this->get(route('admin.moderation-suspensions.reports.show', $case))->assertSee('value="2"', false);
    }

    public function test_legacy_case_uses_issued_window_instead_of_default(): void
    {
        [, , $case] = $this->report();
        $this->issue($case);
        $case->refresh()->update(['metadata' => []]);
        $this->get(route('admin.moderation-suspensions.reports.show', $case))->assertOk()->assertSee('value="8"', false);
        $this->assertSame([], $case->fresh()->metadata);
        $suspension = UserSuspension::first();
        $suspension->update(['ends_at' => $suspension->starts_at->copy()->addMinutes(30)]);
        $suspension->enforcementAction->update(['ends_at' => $suspension->ends_at]);
        $payload = app(\App\Services\Admin\ModerationSuspensionDashboardService::class)->buildReportPayload($case->fresh());
        $this->assertNull($payload['temporarySuspensionDuration']['amount']);
    }

    public function test_explicit_duration_change_updates_both_records_and_audit_once(): void
    {
        [$admin, $user, $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        $this->travel(2)->hours();
        Notification::fake();
        $data = ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Private administrative review reason.'];
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2026-11-17 00:00:00', $suspension->fresh()->ends_at->toDateTimeString());
        $this->assertSame('2026-11-17 00:00:00', $suspension->enforcementAction->fresh()->ends_at->toDateTimeString());
        $this->assertSame('2026-11-16 12:00:00', $suspension->fresh()->starts_at->toDateTimeString());
        $this->assertSame(['amount' => 8, 'unit' => 'hours'], data_get($case->fresh()->metadata, 'chat_temporary_suspension.initial'));
        $this->assertSame(['amount' => 12, 'unit' => 'hours'], data_get($case->fresh()->metadata, 'chat_temporary_suspension.current'));
        $audit = AdminActivityLog::where('action', 'moderation.suspension.duration_changed')->firstOrFail();
        $this->assertSame($admin->id, $audit->admin_user_id);
        $this->assertSame($data['reason'], $audit->meta_json['reason']);
        Notification::assertSentTo($user, \App\Notifications\Moderation\SuspensionDurationChangedNotification::class, function ($notification) use ($user) {
            $payload = $notification->toArray($user);
            $this->assertStringNotContainsString('Private administrative', $payload['message']);

            return $payload['ends_at'] === '2026-11-17 00:00:00';
        });
        Notification::assertSentTo($admin, \App\Notifications\Moderation\AdminSuspensionRecordedNotification::class, function ($notification) use ($admin, $case) {
            $payload = $notification->toArray($admin);
            $this->assertStringContainsString($case->case_reference_code, $payload['message']);
            $this->assertStringContainsString('updated', $payload['message']);
            $this->assertStringNotContainsString('Private administrative', $payload['message']);

            return true;
        });
        Notification::fake();
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension), $data)->assertSessionHasNoErrors();
        Notification::assertNothingSent();
        $this->assertSame(1, AdminActivityLog::where('action', 'moderation.suspension.duration_changed')->count());
    }

    #[DataProvider('invalidDurations')]
    public function test_invalid_duration_changes_leave_records_unchanged(mixed $amount, string $unit, string $reason): void
    {
        [, , $case] = $this->report();
        $this->issue($case);
        Notification::fake();
        $suspension = UserSuspension::first();
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension), ['duration_amount' => $amount, 'duration_unit' => $unit, 'reason' => $reason])->assertSessionHasErrors();
        $this->assertSame('2026-11-16 20:00:00', $suspension->fresh()->ends_at->toDateTimeString());
        $this->assertSame(0, AdminActivityLog::where('action', 'moderation.suspension.duration_changed')->count());
        Notification::assertNothingSent();
    }

    public static function invalidDurations(): array
    {
        $reason = 'Administrative reason for update.';

        return [[0, 'hours', $reason], [-1, 'days', $reason], [1.5, 'hours', $reason], [8761, 'hours', $reason], [366, 'days', $reason], [8, 'minutes', $reason], [8, 'hours', 'short'], [8, 'hours', str_repeat('x', 2001)]];
    }

    #[DataProvider('validBoundaryDurations')]
    public function test_duration_change_accepts_valid_bounds(int $amount, string $unit, string $end): void
    {
        [, , $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension),
            ['duration_amount' => $amount, 'duration_unit' => $unit, 'reason' => 'Administrative reason for update.'])->assertSessionHasNoErrors();
        $this->assertSame($end, $suspension->fresh()->ends_at->toDateTimeString());
    }

    public static function validBoundaryDurations(): array
    {
        return [[1, 'hours', '2026-11-16 13:00:00'], [1, 'days', '2026-11-17 12:00:00'],
            [8760, 'hours', '2027-11-16 12:00:00'], [365, 'days', '2027-11-16 12:00:00']];
    }

    public function test_ineligible_and_mismatched_records_cannot_change_duration(): void
    {
        [, $user, $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        $data = ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Administrative reason for update.'];
        foreach (['revoked', 'expired'] as $status) {
            $suspension->update(['status' => $status]);
            $this->patch(route('admin.moderation-suspensions.duration.update', $suspension), $data)->assertSessionHasErrors();
        }
        $suspension->update(['status' => 'active']);
        foreach (['extended_suspension', 'permanent_suspension'] as $type) {
            $suspension->enforcementAction->update(['action_type' => $type]);
            $this->patch(route('admin.moderation-suspensions.duration.update', $suspension), $data)->assertSessionHasErrors();
        }
        $suspension->enforcementAction->update(['action_type' => 'temporary_suspension', 'user_id' => $case->reporter_id]);
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension), $data)->assertSessionHasErrors();
        $suspension->enforcementAction->update(['user_id' => $user->id]);
        \Illuminate\Support\Facades\DB::table('user_suspensions')->where('id', $suspension->id)->update(['starts_at' => '2026-11-16 12:00:00']);
        \Illuminate\Support\Facades\DB::table('enforcement_actions')->where('id', $suspension->enforcement_action_id)->update(['starts_at' => '2026-11-16 12:00:00']);
        $this->travel(2)->hours();
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension), [...$data, 'duration_amount' => 1])->assertSessionHasErrors();
        $this->assertSame('2026-11-16 20:00:00', $suspension->fresh()->ends_at->toDateTimeString());
    }

    public function test_audit_failure_rolls_back_both_records_without_delivering_notifications(): void
    {
        [, , $case] = $this->report();
        $this->issue($case);
        Notification::fake();
        $suspension = UserSuspension::first();
        $this->mock(\App\Services\AdminActivityLogService::class)->shouldReceive('logModelMutation')->andThrow(new \RuntimeException('Simulated audit failure'));
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension),
            ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Administrative reason for update.'])->assertServerError();
        $this->assertSame('2026-11-16 20:00:00', $suspension->fresh()->ends_at->toDateTimeString());
        $this->assertSame('2026-11-16 20:00:00', $suspension->enforcementAction->fresh()->ends_at->toDateTimeString());
        $this->assertSame(['amount' => 8, 'unit' => 'hours'], data_get($case->fresh()->metadata, 'chat_temporary_suspension.current'));
        Notification::assertNothingSent();
    }

    public function test_editing_one_suspension_does_not_alter_other_active_suspensions(): void
    {
        [$admin, $user, $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        $action = EnforcementAction::create(['user_id' => $user->id, 'action_type' => 'temporary_suspension', 'severity_level' => 'major',
            'trigger_type' => 'manual', 'starts_at' => now(), 'ends_at' => now()->addDay(), 'status' => 'executed']);
        $other = app(\App\Services\Moderation\SuspensionService::class)->createFromEnforcementAction($action, $admin);
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension),
            ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Administrative reason for update.'])->assertSessionHasNoErrors();
        $this->assertSame('2026-11-17 12:00:00', $other->fresh()->ends_at->toDateTimeString());
        $this->assertSame('suspended', $user->fresh()->status);
        $this->assertSame(2, UserSuspension::where('status', 'active')->count());
    }

    public function test_non_admin_cannot_change_duration(): void
    {
        [, $user, $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        $this->withoutMiddleware([\App\Http\Middleware\EnsureLearnerIdentityVerified::class, \App\Http\Middleware\CheckUserSuspensionStatus::class]);
        $this->actingAs($user)->patch(route('admin.moderation-suspensions.duration.update', $suspension),
            ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Administrative reason for update.'])->assertForbidden();
        $this->assertSame('2026-11-16 20:00:00', $suspension->fresh()->ends_at->toDateTimeString());
    }

    public function test_appealing_later_preserves_original_start_for_total_duration_edits(): void
    {
        [$admin, $user, $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        $this->travel(2)->hours();
        $this->actingAs($user)->post(route('moderation.appeals.store', $suspension), ['appeal_reason' => 'Please reconsider this suspension after reviewing the context.'])->assertSessionHasNoErrors();
        $this->assertSame('2026-11-16 12:00:00', $suspension->fresh()->starts_at->toDateTimeString());
        $this->actingAs($admin)->patch(route('admin.moderation-suspensions.duration.update', $suspension),
            ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Reviewed after the submitted appeal.'])->assertSessionHasNoErrors();
        $this->assertSame('2026-11-17 00:00:00', $suspension->fresh()->ends_at->toDateTimeString());
    }

    public function test_mismatched_original_start_requires_review_before_editing(): void
    {
        [, , $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        \Illuminate\Support\Facades\DB::table('user_suspensions')->where('id', $suspension->id)->update(['starts_at' => '2026-11-16 13:00:00']);
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension),
            ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Administrative reason for update.'])->assertSessionHasErrors('duration_amount');
        $this->assertSame('2026-11-16 20:00:00', $suspension->fresh()->ends_at->toDateTimeString());
    }

    public function test_stale_report_input_does_not_trap_note_saves_after_an_explicit_edit(): void
    {
        [, , $case] = $this->report();
        $this->issue($case);
        $suspension = UserSuspension::first();
        $this->patch(route('admin.moderation-suspensions.duration.update', $suspension),
            ['duration_amount' => 12, 'duration_unit' => 'hours', 'reason' => 'Administrative reason for update.'])->assertSessionHasNoErrors();
        $url = route('admin.moderation-suspensions.reports.show', $case);
        $this->from($url)->issue($case, 8)->assertSessionHasErrors('duration_amount');
        $response = $this->get($url)->assertOk()->assertSee('value="12"', false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//*[@name="duration_amount" or @name="duration_unit"]')->length);
        $this->put(route('admin.moderation-suspensions.reports.update', $case),
            ['status' => 'resolved', 'action_taken' => 'temporary_suspension', 'moderation_notes' => 'Updated notes from the original tab.'])->assertSessionHasNoErrors();
        $this->assertSame('2026-11-17 00:00:00', $suspension->fresh()->ends_at->toDateTimeString());
    }
}
