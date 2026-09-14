# Reliable 100 MB Video Uploads

## Problem

Lesson-topic videos are submitted with the rest of the topic form as one
multipart request. The application validates video files at 100 MB, but the
active PHP runtime accepts only 40 MB. The create and edit pages do not check
the file size before submission and show only an indefinite loading state.
Consequently, an oversized video can consume the full upload time before the
request is rejected, while a valid large upload provides no indication that it
is still progressing.

The reported 118 MB sample is intentionally outside the requested boundary.
It must be rejected before any request begins.

## Goals

- Accept uploaded lesson-topic video files up to and including 100 MiB
  (104,857,600 bytes).
- Reject larger files immediately in the browser and enforce the same limit in
  Laravel.
- Display upload progress on the create and edit topic forms, including a
  percentage, transferred size, progress bar, and status text.
- Preserve the existing public-disk storage path and topic-authoring workflow.
- Fail safely without deleting an existing video during an unsuccessful edit.

## Non-goals

- Video compression, transcoding, adaptive streaming, or thumbnail generation.
- Increasing the 100 MiB application limit.
- Resumable or multipart chunk uploads.
- Moving media to a third-party video or object-storage service.
- Increasing the user's network upload speed.

## Design

### Size limits

Laravel remains the authoritative file boundary using `max:102400`, whose
file-rule unit is KiB. PHP is configured with `upload_max_filesize=100M`.
Because the multipart request includes fields and boundary data in addition to
the file, `post_max_size` is set to 110M while Laravel still rejects any
individual video over 100 MiB.

The create and edit video inputs expose the same 104,857,600-byte limit to the
browser script. When a selected file exceeds it, the script clears the input,
shows an inline error with the selected size and 100 MB limit, and does not
submit the form.

The Hostinger deployment documentation will identify the required production
PHP settings and the hPanel verification step. Repository configuration alone
must not be treated as proof of the effective production values.

### Upload flow

For a valid local video, the form is submitted with `XMLHttpRequest` and
`FormData` so browser upload-progress events are available. Existing CSRF and
authorization behavior remains unchanged.

```text
select video
  -> validate MIME type and size in browser
  -> submit existing topic form with XMLHttpRequest
  -> report bytes sent / request bytes as percentage
  -> Laravel validates the 100 MiB boundary
  -> store once on the public disk
  -> create or update the lesson topic
  -> return a redirect target
  -> browser navigates to the lesson page
```

Non-video topic submissions retain the existing normal form behavior. The
server remains authoritative when JavaScript is disabled or bypassed.

### Upload interface

The existing loading overlay is extended for video submissions instead of
adding a separate modal. It displays:

- `Uploading video...` while request bytes are being transferred.
- A determinate progress bar with `aria-valuemin`, `aria-valuemax`, and an
  updated `aria-valuenow`.
- A visible integer percentage.
- Transferred and total sizes in MB.
- `Saving topic...` after the browser finishes transferring and while Laravel
  validates, stores, and persists the topic.

The submit button remains disabled during the request to prevent duplicates.
Status text is exposed through an `aria-live="polite"` region. Motion and
visual styling reuse the existing page and Tailwind conventions.

### Responses and errors

AJAX video submissions receive a JSON success response containing the lesson
redirect URL. Validation failures return the existing JSON error structure.
The form shows a specific file-size error for HTTP 413/oversized requests and
a general retryable message for network or server failures. After a failure,
the overlay closes and the submit button is re-enabled without clearing a
valid selected file.

During topic edits, the existing video is deleted only after the replacement
has been successfully stored. If storing the replacement fails, the old file
and database path remain intact.

## Testing

- A 100 MiB boundary test confirms Laravel accepts the configured maximum.
- A file just over 100 MiB is rejected by Laravel.
- Create and edit pages render the shared maximum-size and progress hooks.
- AJAX create/update responses include the correct redirect URL.
- An edit-storage failure leaves the prior video path and file intact.
- Existing instructor lesson-management tests remain green.
- A production checklist verifies effective PHP values rather than only the
  committed configuration.

## Deployment verification

After deployment, verify through Hostinger's PHP configuration or a temporary
authenticated diagnostic that:

```text
upload_max_filesize = 100M
post_max_size = 110M
```

Then upload one small test video and one file close to 100 MiB, confirm the
progress state advances, and confirm the stored video is playable through its
`/storage/videos/...` URL. Remove only the disposable test topics and their
media through the application's normal delete flow.
