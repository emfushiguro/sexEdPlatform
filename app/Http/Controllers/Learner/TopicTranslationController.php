<?php

namespace App\Http\Controllers\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\LessonTopic;
use App\Services\TopicTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TopicTranslationController extends Controller
{
    public function translate(Request $request, LessonTopic $topic, TopicTranslationService $translationService): JsonResponse
    {
        $validated = $request->validate([
            'target_language' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[A-Za-z]{2})?$/'],
        ]);

        if ($topic->type !== 'text') {
            return response()->json([
                'message' => 'Only text topics can be translated.',
            ], 422);
        }

        $lesson = $topic->lesson;
        $module = $lesson?->module;

        if (!$lesson || !$module || !$lesson->is_published || !$module->is_published) {
            abort(404);
        }

        $user = Auth::user();
        $isEnrolled = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->where('status', EnrollmentStatus::Approved)
            ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'message' => 'You are not enrolled in this module.',
            ], 403);
        }

        $originalHtml = (string) ($topic->text_content ?? '');

        if (trim(strip_tags($originalHtml)) === '') {
            return response()->json([
                'message' => 'This topic has no translatable text content.',
            ], 422);
        }

        try {
            $translation = $translationService->translateHtml(
                $originalHtml,
                strtolower($validated['target_language'])
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Translation service is unavailable right now.',
                'code' => 'TRANSLATION_PROVIDER_ERROR',
            ], 503);
        }

        return response()->json([
            'topic_id' => $topic->id,
            'source_language' => $translation['source_language'],
            'target_language' => strtolower($validated['target_language']),
            'translated_html' => $translation['translated_html'],
        ]);
    }

    public function translateText(Request $request, TopicTranslationService $translationService): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
            'target_language' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[A-Za-z]{2})?$/'],
            'source_language' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[A-Za-z]{2})?$/'],
        ]);

        try {
            $translation = $translationService->translateText(
                trim($validated['text']),
                strtolower($validated['target_language']),
                isset($validated['source_language']) ? strtolower($validated['source_language']) : null
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Translation service is unavailable right now.',
                'code' => 'TRANSLATION_PROVIDER_ERROR',
            ], 503);
        }

        return response()->json([
            'source_language' => $translation['source_language'],
            'target_language' => strtolower($validated['target_language']),
            'translated_text' => $translation['translated_text'],
        ]);
    }

    public function translatePage(Request $request, TopicTranslationService $translationService): JsonResponse
    {
        $validated = $request->validate([
            'texts' => ['required', 'array', 'min:1', 'max:150'],
            'texts.*' => ['required', 'string', 'max:1000'],
            'target_language' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[A-Za-z]{2})?$/'],
            'source_language' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[A-Za-z]{2})?$/'],
        ]);

        try {
            $translation = $translationService->translateBatchText(
                $validated['texts'],
                strtolower($validated['target_language']),
                isset($validated['source_language']) ? strtolower($validated['source_language']) : null
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Translation service is unavailable right now.',
                'code' => 'TRANSLATION_PROVIDER_ERROR',
            ], 503);
        }

        return response()->json([
            'source_language' => $translation['source_language'],
            'target_language' => strtolower($validated['target_language']),
            'translated_texts' => $translation['translated_texts'],
        ]);
    }

    public function synthesizeSpeech(Request $request, TopicTranslationService $translationService): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['nullable', 'string', 'max:5000'],
            'texts' => ['nullable', 'array', 'max:150'],
            'texts.*' => ['required_with:texts', 'string', 'max:1000'],
            'language_code' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2,3}-[A-Za-z]{2}$/'],
            'voice_name' => ['nullable', 'string', 'max:64'],
            'speaking_rate' => ['nullable', 'numeric', 'min:0.25', 'max:4'],
        ]);

        $text = trim((string) ($validated['text'] ?? ''));
        if ($text === '' && !empty($validated['texts'])) {
            $text = trim(implode(' ', array_values(array_unique(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                $validated['texts']
            ))))));
        }

        if ($text === '') {
            return response()->json([
                'message' => 'No text was provided for speech synthesis.',
            ], 422);
        }

        // Keep requests within API limits and avoid huge payload audio generation.
        if (mb_strlen($text) > 5000) {
            $text = mb_substr($text, 0, 5000);
        }

        try {
            $result = $translationService->synthesizeText(
                $text,
                $validated['language_code'] ?? 'en-US',
                $validated['voice_name'] ?? null,
                isset($validated['speaking_rate']) ? (float) $validated['speaking_rate'] : 1.0
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Text-to-Speech service is unavailable right now.',
                'code' => 'TTS_PROVIDER_ERROR',
            ], 503);
        }

        $audioPath = isset($result['audio_path']) ? ltrim((string) $result['audio_path'], '/') : null;
        $audioUrl = $audioPath ? Storage::disk('public')->url($audioPath) : ($result['audio_url'] ?? null);
        $audioRelativeUrl = $audioPath ? '/storage/' . $audioPath : ($result['audio_relative_url'] ?? null);

        return response()->json([
            'audio_url' => $audioUrl,
            'audio_relative_url' => $audioRelativeUrl,
            'language_code' => $result['language_code'],
            'voice_name' => $result['voice_name'],
            'speaking_rate' => $result['speaking_rate'],
        ]);
    }
}
