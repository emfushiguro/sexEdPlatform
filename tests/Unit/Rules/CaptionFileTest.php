<?php

namespace Tests\Unit\Rules;

use App\Rules\CaptionFile;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class CaptionFileTest extends TestCase
{
    public function test_it_accepts_subrip_and_existing_webvtt_files(): void
    {
        foreach ([
            'english.SRT' => "1\r\n00:00:01,250 --> 00:00:03,500\r\nHello\r\n",
            'english.vtt' => "WEBVTT\n\n00:00.000 --> 00:02.000\nHello\n",
        ] as $name => $contents) {
            $file = UploadedFile::fake()->createWithContent($name, $contents);
            $this->assertSame([], $this->failures($file), $file->getMimeType());
        }
    }

    public function test_it_rejects_unsupported_extensions_binary_content_and_invalid_subrip(): void
    {
        $srt = "1\n00:00:01,000 --> 00:00:02,000\nHello\n";
        foreach ([
            UploadedFile::fake()->createWithContent('english.ass', $srt),
            UploadedFile::fake()->createWithContent('english.php', $srt),
            UploadedFile::fake()->createWithContent('fake.srt', "%PDF-1.7\n\0binary data"),
            UploadedFile::fake()->createWithContent('invalid.srt', 'not subtitles'),
        ] as $file) {
            $this->assertNotEmpty($this->failures($file));
        }
    }

    private function failures(UploadedFile $file): array
    {
        $failures = [];
        (new CaptionFile)->validate('caption', $file, function (string $message) use (&$failures): void {
            $failures[] = $message;
        });

        return $failures;
    }
}
