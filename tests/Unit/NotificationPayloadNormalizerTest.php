<?php

namespace Tests\Unit;

use App\Support\NotificationPayloadNormalizer;
use Tests\TestCase;

class NotificationPayloadNormalizerTest extends TestCase
{
    public function test_it_resolves_action_url_with_expected_fallback_order(): void
    {
        $normalizer = new NotificationPayloadNormalizer();

        $this->assertSame('/action-path', $normalizer->resolveActionUrl([
            'action_url' => '/action-path',
            'url' => '/url-path',
            'module_url' => '/module-path',
        ]));

        $this->assertSame('/url-path', $normalizer->resolveActionUrl([
            'url' => '/url-path',
            'module_url' => '/module-path',
        ]));

        $this->assertSame('/module-path', $normalizer->resolveActionUrl([
            'module_url' => '/module-path',
        ]));
    }

    public function test_it_derives_severity_from_explicit_value_status_and_type(): void
    {
        $normalizer = new NotificationPayloadNormalizer();

        $this->assertSame('success', $normalizer->resolveSeverity([
            'severity' => 'success',
            'type' => 'something_failed',
        ]));

        $this->assertSame('error', $normalizer->resolveSeverity([
            'status' => 'rejected',
        ]));

        $this->assertSame('success', $normalizer->resolveSeverity([
            'type' => 'instructor_application_approved',
        ]));

        $this->assertSame('info', $normalizer->resolveSeverity([
            'type' => 'generic_update',
        ]));
    }

    public function test_it_applies_title_and_message_fallbacks_during_normalization(): void
    {
        $normalizer = new NotificationPayloadNormalizer();

        $normalized = $normalizer->normalize([
            'subject' => 'Fallback Subject',
            'body' => 'Fallback body content',
            'module_url' => '/modules/1',
        ]);

        $this->assertSame('Fallback Subject', $normalized['title']);
        $this->assertSame('Fallback body content', $normalized['message']);
        $this->assertSame('/modules/1', $normalized['action_url']);
        $this->assertSame('info', $normalized['severity']);
    }

    public function test_it_normalizes_sender_and_preview_fields_for_chat_payloads(): void
    {
        $normalizer = new NotificationPayloadNormalizer();

        $normalized = $normalizer->normalize([
            'type' => 'chat_message_received',
            'title' => 'New message',
            'message' => 'Original body',
            'preview' => 'Short preview',
            'actor_name' => 'Alex Rivera',
            'actor_avatar_url' => '/storage/avatars/alex.jpg',
        ]);

        $this->assertSame('Short preview', $normalized['message_preview']);
        $this->assertSame('Alex Rivera', $normalized['sender_name']);
        $this->assertSame('/storage/avatars/alex.jpg', $normalized['sender_avatar_url']);
        $this->assertSame('A', $normalized['sender_initial']);
    }
}
