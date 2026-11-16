<?php

namespace Tests\Feature\Admin\Moderation;

use App\Models\EnforcementAction;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\Admin\ModerationSuspensionDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SuspensionAppealNavigationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        return $admin;
    }

    protected function suspension(): UserSuspension
    {
        $user = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $user->assignRole('learner');
        $action = EnforcementAction::create(['user_id' => $user->id, 'action_type' => 'temporary_suspension',
            'severity_level' => 'major', 'trigger_type' => 'manual', 'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(), 'status' => 'executed', 'skip_ladder' => false]);

        return UserSuspension::create(['user_id' => $user->id, 'enforcement_action_id' => $action->id,
            'starts_at' => $action->starts_at, 'ends_at' => $action->ends_at, 'status' => 'active', 'appeal_status' => 'none']);
    }

    protected function appeal(UserSuspension $suspension, string $status = 'pending_review', ?string $submitted = null): SuspensionAppeal
    {
        return SuspensionAppeal::create(['user_suspension_id' => $suspension->id, 'user_id' => $suspension->user_id,
            'status' => $status, 'appeal_reason' => 'Please review the context of this decision.', 'submitted_at' => $submitted ?? now()]);
    }

    protected function links(string $html, string $testId): array
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        return array_map(fn ($node) => $node->getAttribute('href'), iterator_to_array($xpath->query('//a[@data-testid="'.$testId.'"]')));
    }

    public function test_manage_suspension_is_in_dashboard_quick_actions(): void
    {
        $this->admin();
        $response = $this->get(route('admin.dashboard'))->assertOk();
        $this->assertSame([route('admin.moderation-suspensions.index')], $this->links($response->getContent(), 'manage-suspension'));
        $response->assertSeeText('Manage Suspension');
        $this->assertMatchesRegularExpression('/Quick Actions[\s\S]*data-testid="manage-suspension"/', $response->getContent());
    }

    public function test_workspace_links_and_counts_use_real_pending_appeals(): void
    {
        $this->admin();
        $suspension = $this->suspension();
        $this->appeal($suspension);
        $this->appeal($suspension, 'clarification_requested');
        $this->appeal($suspension, 'approved');
        $payload = app(ModerationSuspensionDashboardService::class)->buildIndexPayload([]);
        $this->assertSame(1, $payload['stats']['appeals_pending']);
        $response = $this->get(route('admin.moderation-suspensions.index'))->assertOk();
        $this->assertSame([route('admin.moderation-appeals.index')], $this->links($response->getContent(), 'appeals-queue'));
        $this->assertSame([route('admin.moderation-appeals.index', ['status' => 'pending_review'])], $this->links($response->getContent(), 'pending-appeals'));
    }

    public function test_row_selects_newest_pending_appeal_even_when_reviewed_appeal_is_newer(): void
    {
        $this->admin();
        $suspension = $this->suspension();
        $this->appeal($suspension, 'pending_review', '2026-11-14 10:00:00');
        $selected = $this->appeal($suspension, 'pending_review', '2026-11-15 10:00:00');
        $this->appeal($suspension, 'rejected', '2026-11-16 10:00:00');
        $response = $this->get(route('admin.moderation-suspensions.index'))->assertOk();
        $this->assertSame([route('admin.moderation-appeals.show', $selected)], $this->links($response->getContent(), 'suspension-appeal'));
    }

    public function test_reviewed_row_and_history_use_their_exact_appeal_ids(): void
    {
        $this->admin();
        $suspension = $this->suspension();
        $older = $this->appeal($suspension, 'rejected', '2026-11-14 10:00:00');
        $newer = $this->appeal($suspension, 'approved', '2026-11-15 10:00:00');
        $index = $this->get(route('admin.moderation-suspensions.index'))->assertOk();
        $this->assertSame([route('admin.moderation-appeals.show', $newer)], $this->links($index->getContent(), 'suspension-appeal'));
        $details = $this->get(route('admin.moderation-suspensions.show', $suspension))->assertOk();
        $this->assertEqualsCanonicalizing([route('admin.moderation-appeals.show', $older), route('admin.moderation-appeals.show', $newer)], $this->links($details->getContent(), 'history-appeal'));
    }

    public function test_no_appeal_omits_row_action_and_deleted_user_still_renders(): void
    {
        $this->admin();
        $suspension = $this->suspension();
        $suspension->user->delete();
        $response = $this->get(route('admin.moderation-suspensions.index'))->assertOk()->assertSeeText('No appeal submitted');
        $this->assertSame([], $this->links($response->getContent(), 'suspension-appeal'));
    }

    public function test_non_admin_cannot_open_appeal_queue_or_review(): void
    {
        // Exercise the admin role boundary after the independent suspension/onboarding guards.
        $this->withoutMiddleware([\App\Http\Middleware\EnsureLearnerIdentityVerified::class, \App\Http\Middleware\CheckUserSuspensionStatus::class]);
        $suspension = $this->suspension();
        $appeal = $this->appeal($suspension);
        $this->actingAs($suspension->user);
        $response = $this->get(route('admin.moderation-appeals.index'));
        $this->assertSame(403, $response->status(), (string) $response->headers->get('Location'));
        $this->get(route('admin.moderation-appeals.show', $appeal))->assertForbidden();
    }

    public function test_queue_defaults_to_pending_but_all_statuses_is_explicitly_available(): void
    {
        $this->admin();
        $suspension = $this->suspension();
        $pending = $this->appeal($suspension);
        $this->appeal($suspension, 'clarification_requested');
        $this->appeal($suspension, 'approved');
        $this->get(route('admin.moderation-appeals.index'))->assertOk()
            ->assertViewHas('status', 'pending_review')
            ->assertViewHas('appeals', fn ($appeals) => $appeals->pluck('id')->all() === [$pending->id]);
        $this->get(route('admin.moderation-appeals.index', ['status' => 'all']))->assertOk()
            ->assertViewHas('status', '')->assertViewHas('appeals', fn ($appeals) => $appeals->total() === 3);
        $this->get(route('admin.moderation-appeals.index').'?status=')->assertOk()
            ->assertViewHas('appeals', fn ($appeals) => $appeals->total() === 3);
        $this->get(route('admin.moderation-appeals.index', ['status' => 'clarification_requested']))->assertOk()
            ->assertViewHas('appeals', fn ($appeals) => $appeals->total() === 1)->assertSeeText('Awaiting clarification');
    }

    public function test_all_status_search_and_pagination_preserve_explicit_filter(): void
    {
        $this->admin();
        $suspension = $this->suspension();
        $suspension->user->update(['name' => 'Search Needle User']);
        foreach (range(1, 16) as $number) {
            $this->appeal($suspension, $number % 2 ? 'rejected' : 'approved');
        }
        $this->appeal($this->suspension());
        $response = $this->get(route('admin.moderation-appeals.index', ['status' => 'all', 'search' => 'Search Needle']))->assertOk()
            ->assertViewHas('appeals', fn ($appeals) => $appeals->total() === 16 && $appeals->count() === 15);
        $next = $response->viewData('appeals')->nextPageUrl();
        parse_str(parse_url($next, PHP_URL_QUERY), $query);
        $this->assertSame('all', $query['status']);
        $this->assertSame('Search Needle', $query['search']);
        $this->get($next)->assertOk()->assertViewHas('appeals', fn ($appeals) => $appeals->count() === 1);
    }

    public function test_pending_and_search_empty_states_offer_a_way_out(): void
    {
        $this->admin();
        $this->appeal($this->suspension(), 'approved');
        $response = $this->get(route('admin.moderation-appeals.index'))->assertOk()->assertSeeText('No pending appeals');
        $this->assertSame([route('admin.moderation-appeals.index', ['status' => 'all'])], $this->links($response->getContent(), 'all-appeals'));
        $this->get(route('admin.moderation-appeals.index', ['search' => 'No matching user']))->assertOk()->assertSeeText('No appeals match your filters');
    }

    public function test_review_links_back_to_its_suspension_and_escapes_long_content(): void
    {
        $this->admin();
        $suspension = $this->suspension();
        $appeal = $this->appeal($suspension);
        $appeal->update(['appeal_reason' => '<script>alert("appeal")</script>'.str_repeat('Long evidence context ', 80)]);
        $response = $this->get(route('admin.moderation-appeals.show', $appeal))->assertOk();
        $this->assertSame([route('admin.moderation-suspensions.show', $suspension)], $this->links($response->getContent(), 'appeal-suspension'));
        $response->assertSee('&lt;script&gt;alert', false)->assertDontSee('<script>alert("appeal")</script>', false);
    }

    public function test_review_validation_preserves_decision_and_notes_with_inline_error(): void
    {
        $this->admin();
        $appeal = $this->appeal($this->suspension());
        $url = route('admin.moderation-appeals.show', $appeal);
        $this->from($url)->post(route('admin.moderation-appeals.review', $appeal), ['action' => 'reject', 'review_decision_notes' => 'abc'])
            ->assertSessionHasErrors('review_decision_notes');
        $response = $this->get($url)->assertOk()->assertSee('>abc</textarea>', false)->assertSee('id="decision-notes-error"', false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//select[@name="action"]/option[@value="reject" and @selected]')->length);
        $this->assertSame('pending_review', $appeal->fresh()->status);
    }

    public function test_admin_review_has_no_post_response_form(): void
    {
        $this->admin();
        $appeal = $this->appeal($this->suspension());
        $this->get(route('admin.moderation-appeals.show', $appeal))->assertOk()
            ->assertDontSeeText('Post a response')->assertDontSee('name="message_body"', false)
            ->assertSee('name="review_decision_notes"', false);
    }

    public function test_completed_appeals_hide_decision_form_but_keep_recorded_notes(): void
    {
        $this->admin();
        foreach (['approved', 'rejected'] as $status) {
            $appeal = $this->appeal($this->suspension(), $status);
            $appeal->update(['review_decision_notes' => 'Recorded final decision for this appeal.']);
            $this->get(route('admin.moderation-appeals.show', $appeal))->assertOk()
                ->assertDontSee('id="review-decision-title"', false)
                ->assertDontSee('name="review_decision_notes"', false)
                ->assertSeeText('Recorded final decision for this appeal.')
                ->assertSeeText('Appeal discussion');
        }
        $appeal = $this->appeal($this->suspension(), 'clarification_requested');
        $this->get(route('admin.moderation-appeals.show', $appeal))->assertOk()
            ->assertSee('id="review-decision-title"', false);
    }

    public function test_review_preserves_original_appeal_submission_time(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->admin();
        foreach (['approve', 'reject', 'clarification_requested'] as $action) {
            $appeal = $this->appeal($this->suspension(), 'pending_review', '2026-11-14 10:00:00');
            $this->post(route('admin.moderation-appeals.review', $appeal), [
                'action' => $action, 'review_decision_notes' => 'Review completed with the original submission date retained.',
            ])->assertSessionHasNoErrors();
            $this->assertSame('2026-11-14 10:00:00', $appeal->fresh()->submitted_at->toDateTimeString());
        }
    }
}
