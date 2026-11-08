<?php

namespace Tests\Unit\Helpers;

use App\Helpers\VideoEmbedHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedHelperTest extends TestCase
{
    #[DataProvider('supportedUrls')]
    public function test_supported_urls(string $url, string $provider, string $id): void
    {
        $this->assertSame(['provider' => $provider, 'video_id' => $id], VideoEmbedHelper::parseVideoUrl($url));
    }

    public static function supportedUrls(): array
    {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=2', 'youtube', 'dQw4w9WgXcQ'],
            ['https://youtu.be/dQw4w9WgXcQ', 'youtube', 'dQw4w9WgXcQ'],
            ['https://m.youtube.com/shorts/dQw4w9WgXcQ', 'youtube', 'dQw4w9WgXcQ'],
            ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'youtube', 'dQw4w9WgXcQ'],
            ['https://vimeo.com/12345678', 'vimeo', '12345678'],
            ['https://player.vimeo.com/video/12345678', 'vimeo', '12345678'],
            ['https://media.example.org/video.webm?token=test', 'custom', 'https://media.example.org/video.webm?token=test'],
        ];
    }

    #[DataProvider('unsupportedUrls')]
    public function test_unsupported_urls_never_become_embeds(string $url): void
    {
        $this->assertNull(VideoEmbedHelper::parseVideoUrl($url)['provider']);
        $this->assertNull(VideoEmbedHelper::getEmbedUrl('custom', $url));
    }

    public static function unsupportedUrls(): array
    {
        return array_map(fn ($url) => [$url], [
            'javascript:alert(1)', 'ftp://example.org/video.mp4', '//youtu.be/dQw4w9WgXcQ',
            'https://example.org/embed', 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ',
            'https://example.org/youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtube.com/watch?v=invalid', 'https://vimeo.com/not-an-id',
            'https://user:pass@youtu.be/dQw4w9WgXcQ',
        ]);
    }

    public function test_embed_generation_validates_provider_ids(): void
    {
        $this->assertNull(VideoEmbedHelper::getEmbedUrl('youtube', 'bad?x=1'));
        $this->assertNull(VideoEmbedHelper::getEmbedUrl('vimeo', '123/evil'));
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', VideoEmbedHelper::getEmbedUrl('youtube', 'dQw4w9WgXcQ'));
    }
}
