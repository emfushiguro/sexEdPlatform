<?php

namespace Tests\Unit\Rules;

use App\Rules\WebVttFile;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class WebVttFileTest extends TestCase
{
    public function test_it_accepts_a_valid_webvtt_file(): void
    {
        $this->assertSame([], $this->failures(
            UploadedFile::fake()->createWithContent(
                'english.vtt',
                "WEBVTT\n\n00:00.000 --> 00:02.000\nHello\n",
            ),
        ));
    }

    public function test_it_rejects_wrong_extensions_headers_and_missing_cues(): void
    {
        $this->assertNotEmpty($this->failures(
            UploadedFile::fake()->createWithContent(
                'english.txt',
                "WEBVTT\n\n00:00.000 --> 00:02.000\nHello\n",
            ),
        ));
        $this->assertNotEmpty($this->failures(
            UploadedFile::fake()->createWithContent('english.vtt', "Hello\n"),
        ));
        $this->assertNotEmpty($this->failures(
            UploadedFile::fake()->createWithContent('english.vtt', "WEBVTT\n\nHello\n"),
        ));
    }

    public function test_it_rejects_nul_and_invalid_utf8_content(): void
    {
        $this->assertNotEmpty($this->failures(
            UploadedFile::fake()->createWithContent(
                'nul.vtt',
                "WEBVTT\n\n00:00.000 --> 00:02.000\nBad\0cue\n",
            ),
        ));
        $this->assertNotEmpty($this->failures(
            UploadedFile::fake()->createWithContent(
                'encoding.vtt',
                "WEBVTT\n\n00:00.000 --> 00:02.000\n\xC3\x28\n",
            ),
        ));
    }

    public function test_it_rejects_non_text_mime_content(): void
    {
        $this->assertNotEmpty($this->failures(
            UploadedFile::fake()->create('caption.vtt', 1, 'application/pdf'),
        ));
    }

    private function failures(UploadedFile $file): array
    {
        $failures = [];
        (new WebVttFile)->validate(
            'caption',
            $file,
            function (string $message) use (&$failures): void {
                $failures[] = $message;
            },
        );

        return $failures;
    }
}
