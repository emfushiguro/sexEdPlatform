# Educational event thumbnails

Approved in chat: one optional event thumbnail, JPG/PNG/WebP up to 2 MB, recommended 16:9. Objectives are optional on create/edit and must remain optional through review/publication. Description stays required. Capacity help exposes existing limits of 1–100,000, blank unlimited and no reduction below active confirmed registrations.

Additional approved refinement: display the organization's existing logo on instructor speaker invitation list/detail, and hide invitation Cancel controls once a speaker accepts. Retain pending invitation cancellation.

Use the existing public Laravel disk, multipart create/edit, nullable `seminars.thumbnail_path` via incremental migration, generated filenames under `seminars/thumbnails`. Never trust a client storage path. Support preview/replacement/removal. Preserve existing image on unrelated edit, validation or save failure; clean failed new uploads and replaced/removed owned files only after a successful database operation. Do not delete files outside the owned directory. Existing events require no image/backfill.

Reuse a shared responsive 16:9 rendering partial with a calendar fallback when absent/broken, compact form in rows/operational headers. Show covers in participant and Connector detail, Admin review, instructor speaker invitation; card images in learner/instructor browse; compact images in Connector/Admin listings, speaker invitation list, community upcoming events, attendance/registrant/monitoring headers and native event identity without replacing the video. No new uploader/cropper/gallery/dependencies. Preserve permission, external URL/code protections, lifecycle, scheduler, roster, CSV and native behavior. Public image URLs are promotional media.

Verify create/update/clear/remove/replacement/auth/invalid MIME/oversize/storage or database failure, optional objectives across lifecycle, range boundaries and active count, page rendering/fallback and desktop/mobile upload preview. Use isolated existing cc_db_test transactions serially. Inspect schema and migrate incrementally without resetting or seeding development. Preserve all existing checkout edits and leave uncommitted.
