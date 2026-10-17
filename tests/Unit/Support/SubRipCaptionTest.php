<?php

namespace Tests\Unit\Support;

use App\Support\SubRipCaption;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SubRipCaptionTest extends TestCase
{
    public function test_it_converts_windows_subtitles_without_changing_cue_text(): void
    {
        $srt = "\xEF\xBB\xBF1\r\n00:00:01,250 --> 00:00:03,500\r\nHello, world\r\n<i>Second line</i>\r\n\r\n2\r\n00:00:04,000 --> 00:00:05,000\r\nKumusta\r\n";

        $this->assertSame(
            "WEBVTT\n\n1\n00:00:01.250 --> 00:00:03.500\nHello, world\n<i>Second line</i>\n\n2\n00:00:04.000 --> 00:00:05.000\nKumusta\n",
            SubRipCaption::toWebVtt($srt),
        );
    }

    #[DataProvider('invalidCaptions')]
    public function test_it_rejects_invalid_subtitles(string $srt): void
    {
        $this->expectException(InvalidArgumentException::class);
        SubRipCaption::toWebVtt($srt);
    }

    public static function invalidCaptions(): array
    {
        return [
            'empty' => [''],
            'not subtitles' => ['Hello'],
            'empty cue' => ["1\n00:00:01,000 --> 00:00:02,000\n"],
            'invalid minutes' => ["1\n00:60:01,000 --> 00:61:02,000\nHello"],
            'reverse timing' => ["1\n00:00:02,000 --> 00:00:01,000\nHello"],
            'zero duration' => ["1\n00:00:01,000 --> 00:00:01,000\nHello"],
            'malformed second cue' => ["1\n00:00:01,000 --> 00:00:02,000\nHello\n\n2\nBad timing\nWorld"],
            'nul' => ["1\n00:00:01,000 --> 00:00:02,000\nBad\0text"],
            'invalid utf8' => ["1\n00:00:01,000 --> 00:00:02,000\n\xC3\x28"],
        ];
    }
}
