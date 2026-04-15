<?php

namespace Tests\Unit\Services;

use App\Services\TopicTranslationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TopicTranslationServiceTest extends TestCase
{
    public function test_synthesize_text_stores_audio_in_user_scoped_path(): void
    {
        Storage::fake('local');

        config()->set('services.google_cloud.api_key', 'test-key');
        config()->set('services.google_cloud.tts_endpoint', 'https://texttospeech.googleapis.com/v1/text:synthesize');

        Http::fake([
            '*' => Http::response([
                'audioContent' => base64_encode('fake-mp3-data'),
            ], 200),
        ]);

        $service = app(TopicTranslationService::class);
        $result = $service->synthesizeText(
            'Lesson reader sample text ' . Str::uuid()->toString(),
            42,
            'en-US',
            null,
            1.0
        );

        $this->assertStringStartsWith('tts/user-42/', $result['audio_path']);
        $this->assertStringEndsWith('.mp3', $result['audio_path']);
        Storage::disk('local')->assertExists($result['audio_path']);
    }
}
