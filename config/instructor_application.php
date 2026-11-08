<?php

return [
    'enabled' => (bool) env('DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED', false),
    'identity_enforced_from' => env('INSTRUCTOR_IDENTITY_ENFORCED_FROM') ?: null,
    'minimum_age' => 18,
    'province_code' => '402100000',
    'document_disk' => 'local',
    'max_upload_kilobytes' => 5120,
];
