<?php

return [
    'message_mutation_window_minutes' => env('CHAT_MESSAGE_MUTATION_WINDOW_MINUTES', 15),
    'max_attachments_per_message' => env('CHAT_MAX_ATTACHMENTS_PER_MESSAGE', 5),
    'max_attachment_kb' => env('CHAT_MAX_ATTACHMENT_KB', 10240),
    'support_admin_user_id' => env('CHAT_SUPPORT_ADMIN_USER_ID'),
    'support_admin_requires_active' => env('CHAT_SUPPORT_ADMIN_REQUIRES_ACTIVE', true),
];