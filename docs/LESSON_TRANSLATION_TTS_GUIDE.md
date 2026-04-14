# Lesson Text Translation and Voice Reading Guide

## Purpose

This guide documents how to add two learner-facing features for lesson text topics:

1. Text translation (Google Cloud Translation API)
2. Voice reading (Google Cloud Text-to-Speech API)

The goal is to let learners read lesson text in their preferred language and optionally listen to the content as audio.

## Current System Context

This project already renders text topics from `lesson_topics.text_content` in the learner lesson page:

- Main learner lesson container: `resources/views/learner/lessons/show.blade.php`
- Topic rendering partial: `resources/views/learner/lessons/partials/topic-page.blade.php`
- Topic data model: `app/Models/LessonTopic.php`
- Topic progression logic: `app/Http/Controllers/Learner/LessonController.php`

This means translation and TTS should attach to text topic rendering in `topic-page.blade.php`.

## Scope

In scope:

- Text topics only (`type === 'text'`)
- Translate rich text content for display
- Generate/read translated or original text audio
- Cache translation and audio to reduce repeated API calls

Out of scope (phase 1):

- Live translation for instructor authoring forms
- Full UI localization of the entire platform
- Voice cloning or custom neural voice tuning

## Recommended Architecture

## High Level Flow

1. Learner opens a text topic.
2. Learner picks a language (example: Tagalog, Cebuano, English).
3. Frontend requests translated content from backend.
4. Backend returns cached translation, or calls Google Translation API and caches result.
5. Learner clicks Play Audio.
6. Frontend requests TTS audio URL.
7. Backend returns cached audio, or calls Google Text-to-Speech API, stores audio, and returns URL.

## Backend Components

- `App\Services\TranslationService`
  - Wraps Google Translation API calls
  - Handles HTML translation mode
  - Handles retry and error mapping
- `App\Services\TextToSpeechService`
  - Wraps Google Cloud Text-to-Speech calls
  - Handles voice selection by language
  - Stores generated MP3 in `storage/app/public/tts`
- `App\Http\Controllers\Learner\TopicLanguageController`
  - Endpoint for translation
  - Endpoint for TTS generation/fetch
- Cache layer
  - Laravel cache (Redis/file/database) for fast read path
  - Optional DB tables for persistence and analytics

## Frontend Components

Inside `resources/views/learner/lessons/partials/topic-page.blade.php` text block:

- Language dropdown
- Translate button or auto-translate toggle
- Audio controls: Play/Pause, speed, progress, Stop
- Fallback to original content if translation/TTS unavailable

Use Alpine state in the same text topic block to avoid large frontend rewrites.

## Google Cloud Setup

## 1. Enable APIs

In Google Cloud Console, enable:

- Cloud Translation API
- Cloud Text-to-Speech API

## 2. Create service account

- Create a service account with minimum required permissions:
  - Cloud Translation API User
  - Cloud Text-to-Speech User
- Generate a JSON key.
- Store the key securely (do not commit to git).

## 3. Environment variables

Add to `.env`:

```env
GOOGLE_CLOUD_PROJECT=your-project-id
GOOGLE_APPLICATION_CREDENTIALS=C:/secure/path/google-service-account.json

# Translation defaults
LESSON_TRANSLATION_SOURCE=en
LESSON_TRANSLATION_DEFAULT_TARGET=tl

# TTS defaults
LESSON_TTS_DEFAULT_VOICE=en-US-Neural2-F
LESSON_TTS_AUDIO_FORMAT=MP3
```

## 4. Service config

Add in `config/services.php`:

```php
'google_cloud' => [
    'project_id' => env('GOOGLE_CLOUD_PROJECT'),
    'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
],

'lesson_translation' => [
    'source' => env('LESSON_TRANSLATION_SOURCE', 'en'),
    'default_target' => env('LESSON_TRANSLATION_DEFAULT_TARGET', 'tl'),
],

'lesson_tts' => [
    'default_voice' => env('LESSON_TTS_DEFAULT_VOICE', 'en-US-Neural2-F'),
    'audio_format' => env('LESSON_TTS_AUDIO_FORMAT', 'MP3'),
],
```

Then run:

```bash
php artisan config:clear
php artisan cache:clear
```

## API Design

## Translation endpoint

`POST /learner/topics/{topic}/translate`

Request:

```json
{
  "target_language": "tl"
}
```

Response:

```json
{
  "topic_id": 123,
  "source_language": "en",
  "target_language": "tl",
  "translated_html": "<p>...</p>",
  "cached": true
}
```

## TTS endpoint

`POST /learner/topics/{topic}/tts`

Request:

```json
{
  "language": "tl-PH",
  "voice": "fil-PH-Standard-A",
  "speed": 1.0,
  "text_mode": "translated"
}
```

Response:

```json
{
  "topic_id": 123,
  "language": "tl-PH",
  "voice": "fil-PH-Standard-A",
  "audio_url": "/storage/tts/topic_123_tl-PH_xxxxx.mp3",
  "duration_seconds": 42,
  "cached": false
}
```

## Route and Authorization Notes

- Put endpoints behind authenticated learner middleware.
- Verify enrollment and topic ownership in the controller (same security model as `LessonController`).
- Rate-limit endpoints per user to control abuse and API cost.

## Data and Caching Strategy

## Cache keys

Use content hash to invalidate when instructors edit content:

- Translation key:
  - `topic_translation:{topic_id}:{target_language}:{sha1(text_content)}`
- TTS key:
  - `topic_tts:{topic_id}:{language}:{voice}:{speed}:{sha1(text_or_translation)}`

## Storage

- Save audio files under `storage/app/public/tts`
- Return URL via `asset('storage/...')`
- Add cleanup job for old, unreferenced audio files

## Optional DB tables (recommended for observability)

- `lesson_topic_translations`
  - `lesson_topic_id`, `target_language`, `content_hash`, `translated_html`, `last_used_at`
- `lesson_topic_tts_assets`
  - `lesson_topic_id`, `language`, `voice`, `speed`, `content_hash`, `audio_path`, `last_used_at`

## Implementation Steps

## Phase 1: Core backend

1. Add Google Cloud credentials/config values.
2. Implement `TranslationService`.
3. Implement `TextToSpeechService`.
4. Add learner endpoints in `TopicLanguageController`.
5. Register routes in `routes/web.php` (or learner route group file if used).

## Phase 2: Learner UI

1. Update text topic section in `resources/views/learner/lessons/partials/topic-page.blade.php`.
2. Add language selector + translate action.
3. Add audio player controls.
4. Show loading and error states.

## Phase 3: Hardening

1. Add rate limiting.
2. Add queue support for long TTS generation.
3. Add cleanup command/job for stale audio assets.
4. Add monitoring logs and metrics.

## Translation Quality and HTML Safety

- Send HTML as HTML content to translation API so tags are preserved.
- Keep allowed HTML sanitization rules before rendering translated output.
- Avoid translating dynamic placeholders/tokens.
- If translation fails, keep original text and show a small warning message.

## Voice Selection Guidance

Start with a small voice map:

- `en`: `en-US-Neural2-F`
- `tl` or `fil`: `fil-PH-Standard-A`

If a requested voice is unavailable:

1. Fallback to language default voice
2. Fallback to English default voice

## Cost and Performance Controls

- Cache first, call API second.
- Enforce max text length per request.
- Use queues for large content.
- Add per-user daily request cap for translation and TTS.

## Error Handling

Return consistent error payload:

```json
{
  "message": "Text-to-speech unavailable right now.",
  "code": "TTS_PROVIDER_ERROR"
}
```

Frontend behavior:

- Show non-blocking toast/banner
- Keep original topic text visible
- Disable Play button until retry

## Testing Checklist

## Unit tests

- Translation service success/failure
- TTS service success/failure
- Cache hit/miss behavior

## Feature tests

- Learner can translate only enrolled lesson topics
- Learner can request TTS only for accessible topics
- Unauthorized users get denied
- Edited topic content invalidates old cache

## Manual QA

- Switch language and verify translated content updates
- Play/Pause/Stop works on desktop and mobile
- Verify file URLs are accessible after generation
- Verify fallback behavior during API outage

## Rollout Plan

1. Release behind feature flag (`LESSON_LANGUAGE_TOOLS_ENABLED=true`).
2. Enable for a small set of modules first.
3. Track API usage and error rates for 1 week.
4. Expand to all text topics after validation.

## Suggested Next Files To Implement

- `app/Services/TranslationService.php`
- `app/Services/TextToSpeechService.php`
- `app/Http/Controllers/Learner/TopicLanguageController.php`
- `resources/views/learner/lessons/partials/topic-page.blade.php`
- `config/services.php`
- `routes/web.php` (or learner route group)

## Success Criteria

Feature is complete when:

- Learner can translate text topic content into selected language
- Learner can play generated voice for selected language
- Repeated requests reuse cache
- Failures degrade gracefully without blocking lesson progression
