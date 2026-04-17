<?php

namespace Tests\Unit\Services;

use App\Services\TopicTranslationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TopicTranslationServiceTest extends TestCase
{
    public function test_prepare_text_for_speech_translates_when_filipino_voice_is_selected(): void
    {
        config()->set('services.google_cloud.api_key', 'test-key');
        config()->set('services.google_cloud.translation_endpoint', 'https://translation.googleapis.com/language/translate/v2');

        Http::fake([
            '*' => Http::response([
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => 'Kamusta ka ngayon?',
                            'detectedSourceLanguage' => 'en',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(TopicTranslationService::class);
        $result = $service->prepareTextForSpeech('How are you today?', 'fil-PH');

        $this->assertSame('Kamusta ka ngayon?', $result);

        Http::assertSent(static function ($request) {
            return $request['target'] === 'tl'
                && $request['q'] === 'How are you today?';
        });
    }

    public function test_prepare_text_for_speech_keeps_english_text_without_translation_request(): void
    {
        Http::fake();

        $service = app(TopicTranslationService::class);
        $result = $service->prepareTextForSpeech('Read this lesson in English.', 'en-US');

        $this->assertSame('Read this lesson in English.', $result);
        Http::assertNothingSent();
    }

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
