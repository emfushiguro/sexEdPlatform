<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ContentReportAction;
use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use App\Enums\MessageReportAction;
use App\Enums\MessageReportReason;
use App\Enums\ViolationSeverity;
use App\Models\ContentReport;
use App\Models\Conversation;
use App\Models\EnforcementAction;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use App\Models\Module;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\Moderation\SourceAdapters\ChatReportModerationAdapter;
use App\Services\Moderation\SourceAdapters\LearnerReportModerationAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\DatabaseTestCase;

class AdminSuspensionDashboardUiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_stat_cards_and_table_rows(): void
    {
        $admin = $this->createUserWithRole('admin');
        $suspendedLearner = $this->createSuspensionRecord(
            role: 'learner',
            severity: ViolationSeverity::Major,
            triggerType: 'manual',
            suspensionStatus: 'active',
            appealStatus: 'appeal_pending',
            name: 'Learner One',
        );

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index'))
            ->assertOk()
            ->assertSee('Suspension Dashboard', false)
            ->assertSee('Active Suspensions', false)
            ->assertSee('Appeals Pending', false)
            ->assertSee('Permanent Suspensions', false)
            ->assertSeeText($suspendedLearner->name)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-table-pagination-footer"', false);
    }

    public function test_filters_apply_role_severity_trigger_and_status(): void
    {
        $admin = $this->createUserWithRole('admin');

        $matching = $this->createSuspensionRecord(
            role: 'instructor',
            severity: ViolationSeverity::Minor,
            triggerType: 'automated',
            suspensionStatus: 'revoked',
            appealStatus: 'none',
            name: 'Instructor Match',
        );

        $nonMatching = $this->createSuspensionRecord(
            role: 'learner',
            severity: ViolationSeverity::Critical,
            triggerType: 'manual',
            suspensionStatus: 'active',
            appealStatus: 'appeal_pending',
            name: 'Learner Other',
        );

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index', [
                'role' => 'instructor',
                'severity' => 'minor',
                'trigger' => 'automated',
                'status' => 'revoked',
            ]))
            ->assertOk()
            ->assertSeeText($matching->name)
            ->assertDontSeeText($nonMatching->name);
    }

    public function test_search_and_pagination_work_together(): void
    {
        $admin = $this->createUserWithRole('admin');

        for ($i = 1; $i <= 16; $i++) {
            $this->createSuspensionRecord(
                role: 'learner',
                severity: ViolationSeverity::Moderate,
                triggerType: 'manual',
                suspensionStatus: 'active',
                appealStatus: 'none',
                name: 'Paged Learner ' . $i,
            );
        }

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index', [
                'per_page' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSeeText('Paged Learner 1');

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index', [
                'search' => 'Paged Learner 12',
            ]))
            ->assertOk()
            ->assertSeeText('Paged Learner 12')
            ->assertDontSeeText('Paged Learner 8');
    }

    public function test_dashboard_uses_payment_management_style_markers(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->createSuspensionRecord(
            role: 'learner',
            severity: ViolationSeverity::Major,
            triggerType: 'manual',
            suspensionStatus: 'active',
            appealStatus: 'none',
            name: 'Style Marker User',
        );

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index'))
            ->assertOk()
            ->assertSee('rounded-[30px]', false)
            ->assertSee('border-brand-100', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-table-pagination-footer"', false);
    }

    public function test_message_report_queue_and_review_are_centralized_in_suspension_dashboard(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reporter = $this->createUserWithRole('learner', 'Reporter User');
        $reportedUser = $this->createUserWithRole('instructor', 'Reported Instructor');

        $conversation = Conversation::query()->create([
            'participant_one_id' => $reporter->id,
            'participant_two_id' => $reportedUser->id,
            'pair_key' => Conversation::makePairKey($reporter->id, $reportedUser->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $reporter->id,
            'message_body' => 'Prior context from the reporter.',
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $reportedUser->id,
            'message_body' => 'Reported message body inside the centralized queue.',
        ]);

        $report = MessageReport::query()->create([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'reporter_id' => $reporter->id,
            'status' => 'open',
            'reason_code' => MessageReportReason::ThreateningBehavior,
            'custom_reason' => 'Specific threat context.',
        ]);

        app(ChatReportModerationAdapter::class)->syncReport($report);

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index'))
            ->assertOk()
            ->assertSee('Report Review Queue', false)
            ->assertSee('Chat Report', false)
            ->assertSee('Reported message body inside the centralized queue.')
            ->assertSee($reporter->email)
            ->assertSee($reportedUser->email);

        $case = ModerationCase::query()
            ->where('content_type', 'message_report')
            ->where('content_id', $report->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.reports.show', $case))
            ->assertOk()
            ->assertSee('Reported message body inside the centralized queue.')
            ->assertSee('Prior context from the reporter.')
            ->assertSee($reporter->email)
            ->assertSee($reportedUser->email)
            ->assertSee('Conversation #'.$conversation->id)
            ->assertSee('Specific threat context.');

        $this->actingAs($admin)
            ->put(route('admin.moderation-suspensions.reports.update', $case), [
                'status' => 'resolved',
                'action_taken' => MessageReportAction::ChatRestriction->value,
                'moderation_notes' => 'Restricted chat while the case is reviewed.',
            ])
            ->assertRedirect(route('admin.moderation-suspensions.reports.show', $case));

        $this->assertDatabaseHas('message_reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'action_taken' => MessageReportAction::ChatRestriction->value,
            'reviewed_by_admin_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('enforcement_actions', [
            'user_id' => $reportedUser->id,
            'action_type' => EnforcementActionType::ChatRestriction->value,
            'issued_by_admin_id' => $admin->id,
        ]);
    }

    public function test_learner_report_queue_and_review_are_centralized_in_suspension_dashboard(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reporter = $this->createUserWithRole('learner', 'Learner Reporter');
        $instructor = $this->createUserWithRole('instructor', 'Reported Instructor');
        $module = Module::factory()->create([
            'title' => 'Boundaries Course',
            'created_by' => $instructor->id,
        ]);

        $report = ContentReport::query()->create([
            'reporter_id' => $reporter->id,
            'target_type' => ContentReportTargetType::Module,
            'target_id' => $module->id,
            'reason_code' => 'unsafe_content',
            'status' => ContentReportStatus::Submitted,
            'details_html' => '<p>Module contains unsafe guidance.</p>',
        ]);

        app(LearnerReportModerationAdapter::class)->syncReport($report);

        $case = ModerationCase::query()
            ->where('content_type', 'content_report')
            ->where('content_id', $report->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index'))
            ->assertOk()
            ->assertSee('Report Review Queue', false)
            ->assertSee('Learner Report', false)
            ->assertSee('Boundaries Course')
            ->assertSee($reporter->email)
            ->assertSee($instructor->email);

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.reports.show', $case))
            ->assertOk()
            ->assertSee('Boundaries Course')
            ->assertSee('Module contains unsafe guidance.', false)
            ->assertSee($reporter->email)
            ->assertSee($instructor->email);

        $this->actingAs($admin)
            ->put(route('admin.moderation-suspensions.reports.update', $case), [
                'status' => ContentReportStatus::Resolved->value,
                'action' => ContentReportAction::TakeDownModule->value,
                'moderation_notes' => 'Module removed after centralized review.',
            ])
            ->assertRedirect(route('admin.moderation-suspensions.reports.show', $case));

        $this->assertDatabaseHas('content_reports', [
            'id' => $report->id,
            'status' => ContentReportStatus::Resolved->value,
            'resolved_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'is_published' => false,
        ]);
    }

    public function test_admin_sidebar_only_links_to_central_suspension_dashboard_for_reports(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->assertFalse(Route::has('admin.learner-reports.index'));
        $this->assertFalse(Route::has('admin.message-reports.index'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Suspension Dashboard', false)
            ->assertDontSee('Learner Reports', false)
            ->assertDontSee('Message Reports', false)
            ->assertDontSee('admin/learner-reports', false)
            ->assertDontSee('admin/message-reports', false);
    }

    private function createUserWithRole(string $role, ?string $name = null): User
    {
        $user = User::factory()->create([
            'name' => $name ?? ucfirst($role) . ' User',
            'role' => $role,
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createSuspensionRecord(
        string $role,
        ViolationSeverity $severity,
        string $triggerType,
        string $suspensionStatus,
        string $appealStatus,
        string $name,
    ): User {
        $user = $this->createUserWithRole($role, $name);

        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => $severity,
            'trigger_type' => $triggerType,
            'starts_at' => now()->subDay(),
            'ends_at' => $suspensionStatus === 'active' ? now()->addDays(3) : now()->subHours(2),
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        UserSuspension::query()->create([
            'user_id' => $user->id,
            'enforcement_action_id' => $action->id,
            'status' => $suspensionStatus,
            'starts_at' => now()->subDay(),
            'ends_at' => $suspensionStatus === 'active' ? now()->addDays(3) : now()->subHours(2),
            'appeal_status' => $appealStatus,
            'appeal_submitted_at' => $appealStatus === 'appeal_pending' ? now()->subHours(6) : null,
        ]);

        return $user;
    }
}
