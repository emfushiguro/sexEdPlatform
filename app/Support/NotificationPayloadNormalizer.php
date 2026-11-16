<?php

namespace App\Support;

class NotificationPayloadNormalizer
{
    public function normalize(array $data): array
    {
        foreach ($this->moderationDefaults($data) as $key => $value) {
            if (! is_string($data[$key] ?? null) || trim($data[$key]) === '') {
                $data[$key] = $value;
            }
        }
        $senderName = $this->resolveSenderName($data);

        return [
            'type' => (string) ($data['type'] ?? 'generic_notification'),
            'title' => $this->resolveTitle($data),
            'message' => $this->resolveMessage($data),
            'message_preview' => $this->resolveMessagePreview($data),
            'sender_name' => $senderName,
            'sender_avatar_url' => $this->resolveSenderAvatarUrl($data),
            'sender_initial' => $senderName !== null && $senderName !== ''
                ? strtoupper(mb_substr($senderName, 0, 1))
                : 'U',
            'action_url' => $this->resolveActionUrl($data),
            'severity' => $this->resolveSeverity($data),
        ];
    }

    private function moderationDefaults(array $data): array
    {
        $type = $data['type'] ?? '';
        if (! in_array($type, ['moderation_enforcement_issued', 'moderation_suspension_issued',
            'moderation_suspension_duration_changed', 'moderation_appeal_submitted', 'moderation_appeal_decision',
            'moderation_admin_suspension_recorded'], true)) {
            return [];
        }
        $statusUrl = route('moderation.suspension-status', [], false);
        $id = filter_var($data['appeal_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $suspensionId = filter_var($data['user_suspension_id'] ?? $data['suspension_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $window = '';
        try {
            if (! empty($data['ends_at'])) {
                $end = \Illuminate\Support\Carbon::parse($data['ends_at'])->timezone(config('app.timezone'))->format('M d, Y h:i A T');
                $window = ' Access is expected to resume after '.$end.'.';
            } elseif (array_key_exists('ends_at', $data) && $data['ends_at'] === null && ($data['action_type'] ?? '') === 'permanent_suspension') {
                $window = ' This suspension has no scheduled end time.';
            }
        } catch (\Throwable) {
            // Legacy malformed timestamps must not break the notification list.
        }
        $defaults = match ($type) {
            'moderation_enforcement_issued' => ['Moderation action recorded', 'A moderation action has been applied to your account.'.$window, $statusUrl],
            'moderation_suspension_issued' => ['Account suspension notice', 'Your account access is suspended.'.$window.' Review your suspension status and appeal guidance.', $statusUrl],
            'moderation_suspension_duration_changed' => ['Suspension duration updated', 'Your suspension end time has changed.'.$window, $statusUrl],
            'moderation_appeal_submitted' => ['New suspension appeal', 'A user submitted a suspension appeal for administrator review.', $id ? route('admin.moderation-appeals.show', $id, false) : route('admin.moderation-appeals.index', [], false)],
            'moderation_appeal_decision' => ['Suspension appeal updated', match ($data['appeal_status'] ?? '') {
                'approved' => 'Your suspension appeal was approved.',
                'rejected' => 'Your suspension appeal was rejected.',
                'clarification_requested' => 'Your suspension appeal requires clarification.',
                default => 'Your suspension appeal has been updated.',
            }, $suspensionId ? route('moderation.appeals.create', $suspensionId, false) : $statusUrl],
            'moderation_admin_suspension_recorded' => ['Suspension '.(($data['event'] ?? '') === 'duration_changed' ? 'duration updated' : 'recorded'), 'The account suspension was '.(($data['event'] ?? '') === 'duration_changed' ? 'updated' : 'recorded').'.'.$window, $suspensionId ? route('admin.moderation-suspensions.show', $suspensionId, false) : route('admin.moderation-suspensions.index', [], false)],
            default => null,
        };

        return $defaults ? ['title' => $defaults[0], 'message' => $defaults[1], 'action_url' => $defaults[2], 'severity' => 'info'] : [];
    }

    public function resolveActionUrl(array $data): ?string
    {
        $candidate = $data['action_url'] ?? $data['url'] ?? $data['module_url'] ?? $data['approval_url'] ?? null;

        if (! is_string($candidate)) {
            return null;
        }

        $candidate = trim($candidate);

        return $candidate !== '' ? $candidate : null;
    }

    public function resolveSeverity(array $data): string
    {
        $explicitSeverity = strtolower((string) ($data['severity'] ?? ''));

        if (in_array($explicitSeverity, ['success', 'error', 'info'], true)) {
            return $explicitSeverity;
        }

        $status = strtolower((string) ($data['status'] ?? ''));

        if (in_array($status, ['approved', 'success', 'succeeded', 'completed'], true)) {
            return 'success';
        }

        if (in_array($status, ['rejected', 'failed', 'error', 'declined', 'cancelled'], true)) {
            return 'error';
        }

        $type = strtolower((string) ($data['type'] ?? ''));

        if (str_contains($type, 'approved') || str_contains($type, 'success') || str_contains($type, 'completed')) {
            return 'success';
        }

        if (str_contains($type, 'rejected') || str_contains($type, 'failed') || str_contains($type, 'error')) {
            return 'error';
        }

        return 'info';
    }

    private function resolveTitle(array $data): string
    {
        $title = $data['title'] ?? $data['subject'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            return 'Notification';
        }

        return trim($title);
    }

    private function resolveMessage(array $data): string
    {
        $message = $data['message'] ?? $data['body'] ?? '';

        if (! is_string($message)) {
            return '';
        }

        return trim($message);
    }

    private function resolveMessagePreview(array $data): string
    {
        $preview = $data['message_preview'] ?? $data['preview'] ?? $data['message'] ?? $data['body'] ?? '';

        if (! is_string($preview)) {
            return '';
        }

        return trim($preview);
    }

    private function resolveSenderName(array $data): ?string
    {
        $senderName = $data['sender_name'] ?? $data['actor_name'] ?? null;

        if (! is_string($senderName)) {
            return null;
        }

        $senderName = trim($senderName);

        return $senderName !== '' ? $senderName : null;
    }

    private function resolveSenderAvatarUrl(array $data): ?string
    {
        $avatarUrl = $data['sender_avatar_url'] ?? $data['actor_avatar_url'] ?? null;

        if (! is_string($avatarUrl)) {
            return null;
        }

        $avatarUrl = trim($avatarUrl);

        return $avatarUrl !== '' ? $avatarUrl : null;
    }
}
