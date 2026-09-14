# Video uploads

Lesson-topic video uploads accept MP4, MPEG, MOV, AVI, and WebM files through
100 MiB (104,857,600 bytes). Larger files must be compressed below the limit or
hosted through the existing YouTube/Vimeo URL option.

## Hostinger PHP settings

The repository's `public/.user.ini` requests these per-directory values:

```ini
upload_max_filesize = 100M
post_max_size = 110M
```

The POST limit is deliberately larger because multipart form boundaries and
topic fields are sent alongside the video. Laravel still rejects any video
larger than 100 MiB.

After deployment, open hPanel -> Websites -> Dashboard -> PHP Configuration
and confirm the effective values are 100M and 110M. If Hostinger does not apply
the committed `.user.ini`, set the same values in hPanel. Plan-level Hostinger
limits take precedence over repository settings. Record the post-deploy
effective `upload_max_filesize` and `post_max_size` values.

## Verification

1. Select a video larger than 100 MiB and confirm the page rejects it before
   the request begins.
2. Upload a disposable small video and confirm percentage and transferred-size
   values advance; record the observed byte-progress and percentage behavior.
3. Upload a disposable video close to 100 MiB and confirm it is saved under
   `storage/app/public/videos` and plays from `/storage/videos/...`. Record the
   final playable URL and verification results.
4. Delete disposable test topics through the application's normal delete flow.

The progress UI reports bytes transferred; it does not compress the video or
increase the instructor's network upload speed.
