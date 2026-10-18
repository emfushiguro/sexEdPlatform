<?php

namespace App\Services;

use App\Helpers\VideoEmbedHelper;
use App\Models\LessonTopic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VideoAttributionService
{
    public const LICENSES = [
        'unknown' => 'Unknown/Not specified',
        'youtube_standard' => 'Standard YouTube License',
        'cc_by' => 'Creative Commons Attribution (CC BY)',
        'other' => 'Other explicitly identified license',
    ];

    public const PERMISSION_STATUSES = [
        'unknown' => 'Unknown/Not verified',
        'not_required' => 'Permission not required for intended use',
        'obtained' => 'Permission obtained',
        'pending' => 'Permission pending',
    ];

    public function validate(Request $request, ?LessonTopic $topic = null): ?array
    {
        if ($request->input('type') !== 'video') {
            return null;
        }

        $source = $request->input('video_source', $topic?->video_provider === 'local' ? 'upload' : 'url');
        $data = $request->validate([
            'video_url' => [
                Rule::excludeIf($source !== 'url'),
                'bail', 'nullable', 'string', 'max:2048',
                function ($attribute, $value, $fail): void {
                    $provider = VideoEmbedHelper::parseVideoUrl($value)['provider'];
                    if ($provider === null) {
                        $fail('Use a valid HTTP/HTTPS YouTube, Vimeo, or direct video file URL.');
                    } elseif ($provider === 'custom' && strlen($value) > 255) {
                        $fail('Direct video file URLs must not exceed 255 characters.');
                    }
                },
            ],
            'video_attribution' => ['nullable', 'array:original_title,creator,source_url,license,license_name,attribution_text,permission_status'],
            'video_attribution.original_title' => ['nullable', 'string', 'max:255'],
            'video_attribution.creator' => ['nullable', 'string', 'max:255'],
            'video_attribution.source_url' => [
                'bail', 'nullable', 'string', 'max:2048', 'url:http,https',
                function ($attribute, $value, $fail): void {
                    if (! VideoEmbedHelper::isHttpUrl($value)) {
                        $fail('Use a valid HTTP/HTTPS source URL without credentials.');
                    }
                },
            ],
            'video_attribution.license' => ['nullable', Rule::in(array_keys(self::LICENSES))],
            'video_attribution.license_name' => ['nullable', 'required_if:video_attribution.license,other', 'string', 'max:255'],
            'video_attribution.attribution_text' => ['nullable', 'string', 'max:5000'],
            'video_attribution.permission_status' => ['nullable', Rule::in(array_keys(self::PERMISSION_STATUSES))],
        ]);

        $metadata = $request->exists('video_attribution')
            ? ($data['video_attribution'] ?? [])
            : ($topic?->video_attribution ?? []);
        // Direct file URLs already live in video_id; provider IDs do not preserve the original URL.
        if ($source === 'url') {
            $url = $data['video_url'] ?? $topic?->video_url;
            if (VideoEmbedHelper::parseVideoUrl($url)['provider'] === 'custom') {
                unset($metadata['source_url']);
            } else {
                $metadata['source_url'] = $url;
            }
        } elseif ($topic && $topic->video_provider !== 'local' && ! $request->filled('video_attribution.source_url')) {
            unset($metadata['source_url']);
        }
        if (($metadata['license'] ?? null) !== 'other') {
            unset($metadata['license_name']);
        }
        $metadata = array_filter($metadata, fn ($value) => $value !== null && $value !== '');

        return $metadata === [] ? null : $metadata;
    }
}
