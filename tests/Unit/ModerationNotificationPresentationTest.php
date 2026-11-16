<?php

namespace Tests\Unit;

use App\Models\EnforcementAction;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use App\Notifications\Moderation\AppealDecisionNotification;
use App\Notifications\Moderation\AppealSubmittedNotification;
use App\Notifications\Moderation\EnforcementIssuedNotification;
use App\Notifications\Moderation\SuspensionIssuedNotification;
use App\Support\NotificationPayloadNormalizer;
use Tests\TestCase;

class ModerationNotificationPresentationTest extends TestCase
{
    public function test_existing_moderation_payloads_have_readable_text_and_destinations(): void
    {
        $normalizer = app(NotificationPayloadNormalizer::class);
        foreach (['moderation_enforcement_issued', 'moderation_suspension_issued', 'moderation_appeal_submitted', 'moderation_appeal_decision'] as $type) {
            $payload = $normalizer->normalize(['type' => $type, 'user_id' => 9, 'user_suspension_id' => 4,
                'suspension_id' => 4, 'appeal_id' => 3, 'appeal_status' => 'approved', 'action_type' => 'temporary_suspension',
                'starts_at' => '2026-11-16 08:00:00', 'ends_at' => '2026-11-16 16:00:00', 'notes' => 'PRIVATE MODERATION NOTE']);
            $this->assertNotSame('Notification', $payload['title'], $type);
            $this->assertNotSame('', $payload['message'], $type);
            $this->assertStringStartsWith('/', $payload['action_url']);
            $this->assertStringNotContainsString('PRIVATE MODERATION NOTE', $payload['message']);
        }
    }

    public function test_new_lifecycle_notifications_include_presentation_fields(): void
    {
        $user = new User;
        $action = (new EnforcementAction)->forceFill(['id' => 7, 'user_id' => 9, 'action_type' => 'temporary_suspension', 'severity_level' => 'major', 'trigger_type' => 'manual']);
        $suspension = (new UserSuspension)->forceFill(['id' => 4, 'user_id' => 9, 'status' => 'active', 'appeal_status' => 'none', 'ends_at' => '2026-11-16 16:00:00']);
        $appeal = (new SuspensionAppeal)->forceFill(['id' => 3, 'user_id' => 9, 'user_suspension_id' => 4, 'status' => 'approved']);
        foreach ([new EnforcementIssuedNotification($action), new SuspensionIssuedNotification($suspension), new AppealSubmittedNotification($appeal), new AppealDecisionNotification($appeal)] as $notification) {
            $payload = $notification->toArray($user);
            $this->assertArrayHasKey('title', $payload);
            $this->assertNotSame('', $payload['message']);
            $this->assertStringStartsWith('/', $payload['action_url']);
        }
    }

    public function test_unknown_notifications_and_explicit_display_fields_keep_existing_behavior(): void
    {
        $normalizer = app(NotificationPayloadNormalizer::class);
        $this->assertSame('Notification', $normalizer->normalize(['type' => 'unknown'])['title']);
        $this->assertSame('', $normalizer->normalize(['type' => 'unknown'])['message']);
        $payload = $normalizer->normalize(['type' => 'moderation_suspension_issued', 'title' => 'Explicit title', 'message' => 'Explicit message']);
        $this->assertSame('Explicit title', $payload['title']);
        $this->assertSame('Explicit message', $payload['message']);
    }

    public function test_appeal_decision_text_communicates_each_public_outcome(): void
    {
        foreach (['approved' => 'approved', 'rejected' => 'rejected', 'clarification_requested' => 'clarification'] as $status => $word) {
            $payload = app(NotificationPayloadNormalizer::class)->normalize(['type' => 'moderation_appeal_decision',
                'suspension_id' => 4, 'appeal_status' => $status, 'decision_notes' => 'PRIVATE NOTE']);
            $this->assertStringContainsString($word, $payload['message']);
            $this->assertStringNotContainsString('PRIVATE NOTE', $payload['message']);
        }
    }

    public function test_unrecognized_moderation_types_keep_safe_generic_navigation(): void
    {
        $user = (new User)->forceFill(['id' => 1]);
        $resolver = app(\App\Support\NotificationDeepLinkResolver::class);
        $this->assertSame('/safe-existing-destination', $resolver->resolve(['type' => 'moderation_future_notice', 'action_url' => '/safe-existing-destination'], '/fallback', $user));
        $this->assertSame('/fallback', $resolver->resolve(['type' => 'moderation_future_notice', 'action_url' => '//evil.example/'], '/fallback', $user));
    }
}
