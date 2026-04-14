<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TopicTranslationService
{
    public function translateHtml(string $html, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $apiKey = config('services.google_cloud.api_key');
        $endpoint = config('services.google_cloud.translation_endpoint');

        if (empty($apiKey) || empty($endpoint)) {
            throw new RuntimeException('Google Translation API is not configured.');
        }

        $sourceLanguage = $sourceLanguage ?: config('app.locale', 'en');
        $contentHash = sha1($html);
        $cacheKey = "topic_translation:{$sourceLanguage}:{$targetLanguage}:{$contentHash}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($apiKey, $endpoint, $html, $sourceLanguage, $targetLanguage) {
            $response = Http::asForm()
                ->timeout(20)
                ->post($endpoint, [
                    'key' => $apiKey,
                    'q' => $html,
                    'source' => $sourceLanguage,
                    'target' => $targetLanguage,
                    'format' => 'html',
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Google Translation API request failed.');
            }

            $translatedHtml = data_get($response->json(), 'data.translations.0.translatedText');
            $detectedSourceLanguage = data_get($response->json(), 'data.translations.0.detectedSourceLanguage', $sourceLanguage);

            if (!is_string($translatedHtml) || trim($translatedHtml) === '') {
                throw new RuntimeException('Google Translation API returned an empty translation.');
            }

            return [
                'translated_html' => $translatedHtml,
                'source_language' => $detectedSourceLanguage,
            ];
        });
    }

    public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $apiKey = config('services.google_cloud.api_key');
        $endpoint = config('services.google_cloud.translation_endpoint');

        if (empty($apiKey) || empty($endpoint)) {
            throw new RuntimeException('Google Translation API is not configured.');
        }

        $contentHash = sha1($text);
        $sourcePart = $sourceLanguage ? strtolower($sourceLanguage) : 'auto';
        $cacheKey = "quick_translation:{$sourcePart}:{$targetLanguage}:{$contentHash}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($apiKey, $endpoint, $text, $sourceLanguage, $targetLanguage) {
            $payload = [
                'key' => $apiKey,
                'q' => $text,
                'target' => $targetLanguage,
                'format' => 'text',
            ];

            if (!empty($sourceLanguage)) {
                $payload['source'] = $sourceLanguage;
            }

            $response = Http::asForm()
                ->timeout(20)
                ->post($endpoint, $payload);

            if ($response->failed()) {
                throw new RuntimeException('Google Translation API request failed.');
            }

            $translatedText = data_get($response->json(), 'data.translations.0.translatedText');
            $detectedSourceLanguage = data_get($response->json(), 'data.translations.0.detectedSourceLanguage', $sourceLanguage ?: config('app.locale', 'en'));

            if (!is_string($translatedText) || trim($translatedText) === '') {
                throw new RuntimeException('Google Translation API returned an empty translation.');
            }

            return [
                'translated_text' => $translatedText,
                'source_language' => $detectedSourceLanguage,
            ];
        });
    }

    public function translateBatchText(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $apiKey = config('services.google_cloud.api_key');
        $endpoint = config('services.google_cloud.translation_endpoint');

        if (empty($apiKey) || empty($endpoint)) {
            throw new RuntimeException('Google Translation API is not configured.');
        }

        $normalizedTexts = array_values(array_map(static fn ($value) => trim((string) $value), $texts));

        if ($normalizedTexts === []) {
            return [
                'translated_texts' => [],
                'source_language' => $sourceLanguage ?: config('app.locale', 'en'),
            ];
        }

        $sourcePart = $sourceLanguage ? strtolower($sourceLanguage) : 'auto';
        $cacheKey = 'page_translation:' . $sourcePart . ':' . strtolower($targetLanguage) . ':' . sha1(json_encode($normalizedTexts));

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($apiKey, $endpoint, $normalizedTexts, $sourceLanguage, $targetLanguage) {
            $payload = [
                'q' => $normalizedTexts,
                'target' => $targetLanguage,
                'format' => 'text',
            ];

            if (!empty($sourceLanguage)) {
                $payload['source'] = $sourceLanguage;
            }

            $response = Http::timeout(30)
                ->post($endpoint . '?key=' . urlencode($apiKey), $payload);

            if ($response->failed()) {
                throw new RuntimeException('Google Translation API request failed.');
            }

            $translatedRows = data_get($response->json(), 'data.translations', []);

            if (!is_array($translatedRows) || count($translatedRows) !== count($normalizedTexts)) {
                throw new RuntimeException('Google Translation API returned an invalid batch response.');
            }

            $translatedTexts = array_map(static function ($row) {
                $translated = is_array($row) ? ($row['translatedText'] ?? '') : '';
                if (!is_string($translated)) {
                    return '';
                }

                return $translated;
            }, $translatedRows);

            return [
                'translated_texts' => $translatedTexts,
                'source_language' => data_get($translatedRows, '0.detectedSourceLanguage', $sourceLanguage ?: config('app.locale', 'en')),
            ];
        });
    }

    public function synthesizeText(
        string $text,
        string $languageCode = 'en-US',
        ?string $voiceName = null,
        float $speakingRate = 1.0,
        ?int $userId = null
    ): array {
        $apiKey = config('services.google_cloud.api_key');
        $endpoint = config('services.google_cloud.tts_endpoint');

        if (empty($apiKey) || empty($endpoint)) {
            throw new RuntimeException('Google Text-to-Speech API is not configured.');
        }

        $cleanText = trim($text);
        if ($cleanText === '') {
            throw new RuntimeException('Cannot synthesize empty text.');
        }

        $voiceName = $voiceName ?: null;
        $speakingRate = max(0.25, min(4.0, $speakingRate));

        $cacheKey = 'tts_audio:' . sha1(json_encode([
            'text' => $cleanText,
            'language_code' => $languageCode,
            'voice_name' => $voiceName,
            'speaking_rate' => $speakingRate,
            'user_id' => $userId,
        ]));

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($apiKey, $endpoint, $cleanText, $languageCode, $voiceName, $speakingRate) {
            $voicePayload = [
                'languageCode' => $languageCode,
            ];

            if (!empty($voiceName)) {
                $voicePayload['name'] = $voiceName;
            }

            $response = Http::timeout(45)
                ->post($endpoint . '?key=' . urlencode($apiKey), [
                    'input' => [
                        'text' => $cleanText,
                    ],
                    'voice' => $voicePayload,
                    'audioConfig' => [
                        'audioEncoding' => 'MP3',
                        'speakingRate' => $speakingRate,
                    ],
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Google Text-to-Speech API request failed.');
            }

            $audioContent = data_get($response->json(), 'audioContent');
            if (!is_string($audioContent) || trim($audioContent) === '') {
                throw new RuntimeException('Google Text-to-Speech API returned empty audio.');
            }

            $binaryAudio = base64_decode($audioContent, true);
            if ($binaryAudio === false) {
                throw new RuntimeException('Unable to decode synthesized audio.');
            }

            $scope = $userId !== null ? 'user-' . $userId : 'shared';
            $filename = 'tts/' . $scope . '/' . sha1($cleanText . '|' . $languageCode . '|' . ($voiceName ?? '') . '|' . $speakingRate) . '.mp3';
            Storage::disk('local')->put($filename, $binaryAudio);

            return [
                'audio_path' => $filename,
                'language_code' => $languageCode,
                'voice_name' => $voiceName,
                'speaking_rate' => $speakingRate,
            ];
        });
    }
}
