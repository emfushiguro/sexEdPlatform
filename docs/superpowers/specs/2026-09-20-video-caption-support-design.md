# Local Video Caption Support

## Problem

Local lesson-topic videos already use HTML5 `<video>` elements and Plyr.js.
The schema also contains a legacy nullable `lesson_topics.caption_file_path`
column, and the learner view renders that path as one hard-coded English,
default subtitle track. There is no authoring workflow, language metadata,
multi-track support, meaningful validation, replacement/removal lifecycle, or
test coverage for captions.

The partial implementation cannot represent the requested English, Filipino,
and Korean example simultaneously, and the hard-coded Plyr language setting
can override the intent of a non-English default track.

## Goals

- Let authorized topic authors attach, replace, inspect, and remove WebVTT
  caption files while creating or editing a local video topic.
- Support multiple language tracks with a validated language code and a
  learner-facing label.
- Permit zero or one configured default track and enforce that invariant on
  the server.
- Render standards-based HTML5 `<track kind="subtitles">` elements that Plyr
  discovers without recreating Plyr's caption interface.
- Preserve all current video controls, responsive behavior, and the existing
  distinction between local and YouTube/Vimeo videos.
- Preserve legacy caption data through an incremental migration.
- Keep stored files and database rows consistent during create, replace,
  remove, source-change, and topic-deletion flows.

## Non-goals

- YouTube or Vimeo caption management.
- Caption authoring, transcription, translation, or format conversion.
- Formats other than WebVTT (`.vtt`).
- Separate closed-caption versus subtitle kinds in Phase 1.
- Styling controls beyond Plyr's existing accessible caption presentation.
- A standalone caption-management page or caption API.
- A third-party WebVTT parser.

## Existing Architecture

- `LessonTopic` stores local video metadata and exposes
  `video_file_url` through the public storage disk.
- `TopicController::store()` and `TopicController::update()` validate and
  persist the existing multipart topic forms.
- Topic create/edit access already flows through `LessonPolicy` and
  `TopicPolicy`; the controller rechecks authorization before mutation.
- The create and edit forms already submit file uploads through the shared
  `video-upload-form.js` `FormData` workflow.
- The learner topic partial renders local HTML5 video separately from the
  existing YouTube/Vimeo iframe branch.
- `app.js` initializes each `.plyr-video` element once after
  `DOMContentLoaded` and preserves the configured playback controls.

The caption feature will extend these paths. It will not add parallel routes
or a second upload mechanism.

## Data Model and Migration

Create `lesson_topic_captions` with the following columns:

- `id`
- `lesson_topic_id`, foreign keyed to `lesson_topics` with cascade delete
- `file_path`
- `language_code`, up to 35 characters
- `label`, up to 100 characters
- `is_default`, defaulting to false
- `created_at` and `updated_at`

A unique constraint on `(lesson_topic_id, language_code)` prevents two tracks
for the same language on one video. The model-level relationship is
`LessonTopic::captions(): HasMany`; `LessonTopicCaption::topic(): BelongsTo`
provides the inverse. The caption model exposes a public-disk `file_url`
accessor rather than assembling `/storage` paths in Blade.

The database cannot portably enforce "only one true row per topic" across the
project's supported test and production databases. Caption synchronization
therefore clears prior defaults and assigns at most one selected track inside
the same database transaction.

The incremental migration first creates the new table, then copies every
non-empty legacy `caption_file_path` into it as:

- language code: `en`
- label: `Subtitles`
- default: true

These values preserve the exact semantics of the existing learner markup.
After a successful backfill, the migration removes `caption_file_path` so
there is one source of truth. The reverse migration restores the legacy
column from the default track, or the first track when no default exists,
before dropping the caption table. Rolling back necessarily collapses
multiple tracks to the single legacy field.

No database reset, destructive reseed, or development-data replacement is
part of this work.

## Authoring Interface

The create and edit pages include one shared Blade partial inside the local
video upload area. The section is hidden for URL-based video sources and is
not added to any learner or unauthorized page.

Each track row contains:

- a `.vtt` file input;
- a language-code input backed by a datalist of common suggestions, including
  `en`, `fil`, and `ko`, while still accepting other valid codes;
- a required learner-facing label;
- a radio-style default selector shared by all rows;
- a remove action.

Saved tracks additionally show their label, language, default status, and a
"View caption file" link that opens the stored VTT file. Their file input is
optional and labelled as a replacement. New rows require a file.

A dependency-free `caption-tracks-form.js` module owns only row addition,
unsaved-row removal, saved-row removal marking, default-radio clearing, and
basic selected-file feedback. It uses data attributes and a `<template>` from
the shared partial. The existing `video-upload-form.js` remains the sole
submission mechanism and automatically includes caption inputs in its
`FormData`.

The request shape is:

```text
captions[index][id]
captions[index][file]
captions[index][language_code]
captions[index][label]
captions[index][remove]
caption_default = index | absent
```

Stable database IDs identify saved tracks. The controller verifies that every
submitted ID belongs to the topic being edited; a valid caption ID from
another topic is rejected.

## Validation

Laravel remains authoritative. Caption input is accepted only when the final
topic type is `video` and the final source is `upload`/`local`.

Each new or replacement file must:

- be an uploaded file no larger than 2 MiB (2,048 KiB);
- use the `.vtt` extension;
- be detected as `text/vtt` or `text/plain` where PHP file information is
  available;
- contain no NUL bytes and be valid UTF-8-compatible text;
- begin with `WEBVTT`, allowing a UTF-8 BOM and legal header text;
- contain at least one cue timing line with a start time, `-->`, and end time.

A focused custom validation rule performs content inspection without adding a
dependency. Laravel's standard file, extension, MIME, and size rules enforce
the surrounding upload boundary.

Language codes use a Phase 1 BCP 47-style validation boundary: a two- or
three-letter primary language subtag followed by optional two-to-eight
character alphanumeric subtags separated by hyphens. Codes are normalized to
lowercase for persistence and uniqueness. Labels are trimmed, required, and
limited to 100 characters.

The request rejects duplicate language codes, multiple selected defaults,
missing files for new rows, replacement/removal IDs from another topic, and
caption uploads for external providers. A removed row does not need its other
fields to remain valid.

Laravel's `store()` method writes files below
`storage/app/public/captions/{topic-id}` using generated safe filenames. User
filenames never become storage paths.

## Persistence and File Lifecycle

Caption synchronization is isolated in one small service used by the existing
Topic controller. It receives the authorized topic, validated track entries,
and selected default index. It does not perform authorization or redirect
responses.

For creates and edits:

1. Validate the entire request before writing files.
2. Store all new video/caption files under generated public-disk paths.
3. Persist the topic and caption rows in a database transaction.
4. Delete replaced or removed old files only after persistence succeeds.
5. If persistence fails, delete newly written files and preserve old rows and
   files.

Switching a topic from a local video to YouTube/Vimeo removes every caption
record and caption file after the source update succeeds. Deleting a topic
through the existing controller captures the caption paths before the database
cascade removes their records, then deletes the files only after the topic
deletion succeeds.

The service is deliberately limited to caption synchronization. Existing
video, image, worksheet, duration, and redirect behavior remains in the
controller.

## Learner Rendering and Plyr

Only the existing local-video branch renders tracks:

```html
<track
    kind="subtitles"
    src="/storage/captions/123/generated.vtt"
    srclang="fil"
    label="Filipino"
    default
>
```

Blade loops over `LessonTopic::captions`, escapes the language/label values,
uses the model's `file_url`, and emits `default` only for the configured row.
The YouTube/Vimeo iframe branch is unchanged.

The learner lesson query eager-loads captions with topics to avoid extra
queries. A small testable player-options module keeps the existing
`.plyr-video` discovery and one-instance-per-element initialization in
`app.js`. It derives caption options from the rendered tracks:

- no tracks: omit `captions` from controls and settings;
- tracks but no default: retain language selection, start captions off, and
  use Plyr's `auto` language preference;
- a default track: start captions active and use its `srclang`;
- static HTML tracks use `update: false`.

The play, progress, current-time, mute, volume, speed, and fullscreen controls
remain unchanged. Plyr continues to provide keyboard interaction, selection,
synchronization, fullscreen behavior, and caption styling.

## Authorization and Security

- Caption inputs exist only inside the already-authorized Topic create/edit
  workflow.
- `TopicController::update()` continues to authorize the topic before reading
  or changing caption IDs.
- `TopicController::store()` continues to require create permission plus
  update permission on the associated lesson.
- Submitted caption IDs are constrained to the authorized topic.
- Learners receive only public caption URLs; no authoring endpoints or
  controls are exposed.
- External provider URLs cannot be used to attach local caption files.

No new authorization policy or permission is needed.

## Error Handling

Validation failures return through the existing form/JSON conventions. The
shared partial displays per-row field errors when Blade rerenders, while the
existing upload overlay displays the server's first validation message for an
AJAX submission. Entered language codes and labels are reconstructed from old
input after validation failure; browsers intentionally require file
reselection.

A failed new-file write returns a server error without creating a caption
row. A failed database mutation removes only newly written files. Existing
files are not deleted until the corresponding row mutation succeeds.

## Testing and Verification

Automated coverage will include:

- migration backfill from `caption_file_path` and model relationships;
- valid create with one and multiple VTT tracks;
- valid BCP 47-style codes and normalized uniqueness;
- rejection of wrong extensions, unsupported MIME types, oversized files,
  missing headers, missing cues, invalid UTF-8/NUL content, and duplicate
  languages;
- one-default and no-default persistence;
- edit persistence, replacement, removal, and default changes;
- cleanup after failed persistence, source changes, and topic deletion;
- rejection of caption IDs owned by another topic;
- authorization failures for another instructor's topic;
- create/edit form rendering and saved-file inspection links;
- learner rendering for no tracks, one track, multiple tracks, labels,
  language codes, and the default attribute;
- unchanged YouTube/Vimeo iframe output;
- Plyr options for no captions, captions without a default, and captions with
  a default;
- existing video upload, instructor topic, and learner lesson regressions;
- frontend production build and PHP formatting checks.

Manual responsive QA will exercise caption toggling, language switching,
default behavior, seeking, playback speed, volume/mute, desktop, mobile,
fullscreen, and a no-caption local video. These checks use disposable topics
through normal application flows and never reset development data.
