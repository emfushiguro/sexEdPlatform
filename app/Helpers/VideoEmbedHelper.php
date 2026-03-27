<?php

namespace App\Helpers;

class VideoEmbedHelper
{
    /**
     * Parse video URL and extract provider and video ID
     * Supports: YouTube, Vimeo
     */
    public static function parseVideoUrl(?string $url): array
    {
        if (empty($url)) {
            return ['provider' => null, 'video_id' => null];
        }

        // YouTube patterns
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $match)) {
            return [
                'provider' => 'youtube',
                'video_id' => $match[1]
            ];
        }

        // Vimeo patterns
        if (preg_match('/(?:vimeo\.com\/)(\d+)/i', $url, $match)) {
            return [
                'provider' => 'vimeo',
                'video_id' => $match[1]
            ];
        }

        // Direct video file or custom embed
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'provider' => 'custom',
                'video_id' => $url
            ];
        }

        return ['provider' => null, 'video_id' => null];
    }

    /**
     * Generate embed URL from provider and video ID
     */
    public static function getEmbedUrl(string $provider, string $videoId): ?string
    {
        return match ($provider) {
            'youtube' => "https://www.youtube.com/embed/{$videoId}",
            'vimeo' => "https://player.vimeo.com/video/{$videoId}",
            'custom' => $videoId, // Already a full URL
            default => null
        };
    }

    /**
     * Generate thumbnail URL
     */
    public static function getThumbnailUrl(string $provider, string $videoId): ?string
    {
        return match ($provider) {
            'youtube' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
            'vimeo' => null, // Vimeo requires API call for thumbnails
            default => null
        };
    }
}

