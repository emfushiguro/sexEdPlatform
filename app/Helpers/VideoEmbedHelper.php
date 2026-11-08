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
        if (! self::isHttpUrl($url)) {
            return ['provider' => null, 'video_id' => null];
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST));
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be'], true)) {
            $id = null;
            if ($host === 'youtu.be') {
                $id = trim($path, '/');
            } elseif ($path === '/watch') {
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^/(?:embed|shorts|live|v|e)/([^/]+)/?$~', $path, $match)) {
                $id = $match[1];
            }

            return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $id)
                ? ['provider' => 'youtube', 'video_id' => $id]
                : ['provider' => null, 'video_id' => null];
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            return preg_match('~^/(?:video/)?(\d+)/?$~', $path, $match)
                ? ['provider' => 'vimeo', 'video_id' => $match[1]]
                : ['provider' => null, 'video_id' => null];
        }

        if (preg_match('/\.(?:mp4|mpeg|mpg|mov|avi|webm|ogv|ogg)$/i', $path)) {
            return ['provider' => 'custom', 'video_id' => $url];
        }

        return ['provider' => null, 'video_id' => null];
    }

    public static function isHttpUrl(?string $url): bool
    {
        return $url !== null
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
            && parse_url($url, PHP_URL_USER) === null
            && parse_url($url, PHP_URL_PASS) === null;
    }

    /**
     * Generate embed URL from provider and video ID
     */
    public static function getEmbedUrl(string $provider, string $videoId): ?string
    {
        return match ($provider) {
            'youtube' => preg_match('/^[A-Za-z0-9_-]{11}$/D', $videoId) ? "https://www.youtube.com/embed/{$videoId}" : null,
            'vimeo' => ctype_digit($videoId) ? "https://player.vimeo.com/video/{$videoId}" : null,
            default => null,
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
