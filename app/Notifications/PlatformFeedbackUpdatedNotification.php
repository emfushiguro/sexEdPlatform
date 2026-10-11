<?php
namespace App\Notifications;
use App\Models\PlatformFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
class PlatformFeedbackUpdatedNotification extends Notification
{
 use Queueable;
 public function __construct(private readonly PlatformFeedback $feedback) {}
 public function via(object $notifiable): array { return ['database']; }
 public function toDatabase(object $notifiable): array { return ['title'=>'Support ticket update','message'=>"Your support ticket {$this->feedback->reference_number} was updated.",'feedback_id'=>$this->feedback->id,'url'=>route('feedback.show',$this->feedback)]; }
}
