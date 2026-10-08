<?php
namespace Tests\Feature\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Models\PlatformFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class AdminPlatformFeedbackTest extends TestCase
{
 use RefreshDatabase;
 public function test_admin_can_filter_and_update_feedback(): void
 {
 $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $user=User::factory()->create(['role'=>'learner']);
  $feedback=PlatformFeedback::factory()->for($user)->create(['subject'=>'Broken help','type'=>'bug_report']);
  $this->actingAs($admin)->get(route('admin.feedback.index',['search'=>'Broken']))->assertOk()->assertSee($feedback->reference_number);
  $feedback->messages()->create(['sender_id'=>$admin->id,'sender_role'=>'admin','body'=>'Fixed']);
  $this->actingAs($admin)->put(route('admin.feedback.update',$feedback),['status'=>'resolved'])->assertRedirect()->assertSessionHas('success','Support ticket updated.');
  $this->assertDatabaseHas('platform_feedback',['id'=>$feedback->id,'status'=>'resolved']);
 }

 public function test_admin_feedback_inbox_shows_sender_snapshot_and_review_eye_action(): void
 {
  $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $user=User::factory()->create(['name'=>'Ava Learner','email'=>'ava@example.com','role'=>'learner']); $user->assignRole('learner');
  $feedback=PlatformFeedback::factory()->for($user)->create(['subject'=>'Improve the feedback form']);

  $this->actingAs($admin)->get(route('admin.feedback.index'))
   ->assertOk()
   ->assertSee('Ava Learner')
   ->assertSee('Learner')
   ->assertSee('data-feedback-review-action', false)
   ->assertSee('aria-label="Review ticket: '.$feedback->subject.'"', false)
   ->assertSee('/admin/feedback/'.$feedback->id.'/open', false)
   ->assertSee('method="POST"', false);
 }

 public function test_admin_eye_marks_new_ticket_reviewed_once_but_direct_get_is_read_only(): void
 {
  $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $feedback=PlatformFeedback::factory()->create(['status'=>PlatformFeedbackStatus::New]);

  $this->actingAs($admin)->get(route('admin.feedback.show',$feedback))->assertOk();
  $this->assertSame(PlatformFeedbackStatus::New,$feedback->fresh()->status);
  $this->assertDatabaseCount('platform_feedback_histories',0);

  $this->post('/admin/feedback/'.$feedback->id.'/open')->assertRedirect(route('admin.feedback.show',$feedback));
  $this->assertSame(PlatformFeedbackStatus::Reviewed,$feedback->fresh()->status);
  $this->assertDatabaseCount('platform_feedback_histories',1);

  $this->post('/admin/feedback/'.$feedback->id.'/open')->assertRedirect(route('admin.feedback.show',$feedback));
  $this->assertDatabaseCount('platform_feedback_histories',1);
 }

 public function test_ticket_cannot_be_resolved_before_an_admin_message_exists(): void
 {
  $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $feedback=PlatformFeedback::factory()->create(['status'=>PlatformFeedbackStatus::Reviewed]);

  $this->actingAs($admin)->put(route('admin.feedback.update',$feedback),['status'=>'resolved'])->assertStatus(422);

  $this->assertSame(PlatformFeedbackStatus::Reviewed,$feedback->fresh()->status);
  $this->assertDatabaseCount('platform_feedback_histories',0);
 }

 public function test_new_ticket_with_admin_message_resolves_through_recorded_review(): void
 {
  $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $feedback=PlatformFeedback::factory()->create(['status'=>PlatformFeedbackStatus::New]);
  $feedback->messages()->create(['sender_id'=>$admin->id,'sender_role'=>'admin','body'=>'Resolution supplied.']);

  $this->actingAs($admin)->put(route('admin.feedback.update',$feedback),['status'=>'resolved'])->assertRedirect();

  $this->assertSame(PlatformFeedbackStatus::Resolved,$feedback->fresh()->status);
  $this->assertDatabaseCount('platform_feedback_histories',2);
  $this->assertDatabaseHas('platform_feedback_histories',['platform_feedback_id'=>$feedback->id,'from_status'=>'new','to_status'=>'reviewed']);
  $this->assertDatabaseHas('platform_feedback_histories',['platform_feedback_id'=>$feedback->id,'from_status'=>'reviewed','to_status'=>'resolved']);
 }

 public function test_admin_review_omits_legacy_writing_fields_and_uses_confirmation_only_for_close(): void
 {
  $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $feedback=PlatformFeedback::factory()->create(['status'=>PlatformFeedbackStatus::Reviewed]);

  $this->actingAs($admin)->withSession(['success'=>'Support ticket updated.'])->get(route('admin.feedback.show',$feedback))
   ->assertOk()
   ->assertDontSee('Private internal note')
   ->assertDontSee('name="internal_note"',false)
   ->assertDontSee('name="staff_response"',false)
   ->assertDontSee('Planned')
   ->assertDontSee('<div role="status"',false)
   ->assertSee('data-confirm-submit',false)
   ->assertSee('value="closed"',false);
 }

 public function test_admin_feedback_review_shows_sender_profile_and_inline_attachment_without_amber_guidance(): void
 {
  Storage::fake('local');
  Storage::disk('local')->put('feedback/review.png', 'png bytes');
  $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $user=User::factory()->create(['name'=>'Ava Learner','email'=>'ava@example.com','role'=>'learner']); $user->assignRole('learner');
  $feedback=PlatformFeedback::factory()->for($user)->create(['attachment_path'=>'feedback/review.png']);

  $this->actingAs($admin)->get(route('admin.feedback.show',$feedback))
   ->assertOk()
   ->assertSee('data-feedback-sender-profile', false)
   ->assertSee('Ava Learner')
   ->assertSee('Learner')
   ->assertSee('data-feedback-attachment-preview', false)
   ->assertSee('data-feedback-attachment-preview-image', false)
   ->assertSee(route('admin.feedback.attachment.show',$feedback), false)
   ->assertDontSee('Keep private and public writing separate');
 }

 public function test_support_ticket_pages_keep_help_tabs_out_and_use_colored_metric_icons(): void
 {
  $admin=User::factory()->create(['role'=>'admin']); $admin->assignRole('admin');
  $feedback=PlatformFeedback::factory()->for(User::factory()->create(['role'=>'learner']))->create();

  foreach ([route('admin.feedback.index'), route('admin.feedback.show', $feedback)] as $url) {
   $this->actingAs($admin)->get($url)
    ->assertOk()
    ->assertDontSee('aria-label="Support management"', false);
  }

  $this->actingAs($admin)->get(route('admin.feedback.index'))
   ->assertSee('data-support-metric-card="total"', false)
   ->assertSee('data-support-metric-icon="new"', false)
   ->assertSee('data-support-metric-icon="unresolved"', false)
   ->assertSee('data-support-metric-icon="resolved"', false)
   ->assertSee('from-sky-600', false)
   ->assertSee('from-amber-600', false)
   ->assertSee('from-emerald-600', false);
 }
}
