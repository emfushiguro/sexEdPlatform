<?php

namespace App\Support;

class NotificationPayloadNormalizer
{
    public function normalize(array $data): array
    {
        return [
            'type' => (string) ($data['type'] ?? 'generic_notification'),
            'title' => $this->resolveTitle($data),
            'message' => $this->resolveMessage($data),
            'action_url' => $this->resolveActionUrl($data),
            'severity' => $this->resolveSeverity($data),
        ];
    }

    public function resolveActionUrl(array $data): ?string
    {
        $candidate = $data['action_url'] ?? $data['url'] ?? $data['module_url'] ?? null;

        if (!is_string($candidate)) {
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

        if (!is_string($title) || trim($title) === '') {
            return 'Notification';
        }

        return trim($title);
    }

    private function resolveMessage(array $data): string
    {
        $message = $data['message'] ?? $data['body'] ?? '';

        if (!is_string($message)) {
            return '';
        }

        return trim($message);
    }
}
