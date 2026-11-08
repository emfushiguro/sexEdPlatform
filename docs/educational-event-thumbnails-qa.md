# Educational event thumbnails and invitation refinements

Implemented on the existing branch, October 15, 2026. No development records reset, reseeded or replaced.

## Changes

- Optional single thumbnail for Connector seminar/webinar create/edit: JPG/JPEG, PNG or WebP, maximum 2 MB; 16:9 recommended. Browser preview, reset selection, saved-image retention, replacement and explicit removal.
- Public disk files use generated names in `seminars/thumbnails`. Client storage paths are ignored. Invalid uploads and failed saves keep the existing image. Successful replacement/removal/draft deletion cleans only owned thumbnail paths. Database writes and deletion lock the current event row before reading its thumbnail.
- Shared cover/compact renderer with a calendar fallback for absent or broken files, including errors that precede Alpine initialization. Covers appear on learner/instructor browse and detail, Connector detail and Admin review; compact images identify events on host/admin lists, speaker invitations, community upcoming events, rosters, attendance monitoring and native livestream headers. Video regions are preserved.
- Objectives is optional on create/edit and through review/publication. Description remains required. Browse cards use Description when Objectives is blank; details omit an empty Objectives block.
- Capacity help explains that the host organization chooses its own registration limit based on its arrangements and external platform capacity; blank means no registration limit in this application. Form/server integer bounds and the confirmed-registration floor remain. See [October 16 host UI refinements](educational-event-host-ui-refinements-2026-10-16.md) for the compact layout, exact PHT timestamps and attendance dialogs.
- Instructor invitation list/detail show the verified organization's existing logo when available. Confirmed speakers have no invitation Cancel action on the Connector event page; only pending invitations show it. Accepted invitations omit the pending accept/decline dialogs.

## Database and assets

`2026_10_15_000003_add_thumbnail_path_to_seminars.php` adds one nullable column. It was inspected with `--pretend`, then applied by its explicit path to `cc_db` and `cc_db_test`. A read-only development check afterward found the column, both existing events, and no backfilled thumbnail paths. The migration is forward-only to preserve stored paths.

The public disk must remain web-accessible (`public/storage` exists locally). Thumbnails are public promotional images; do not upload confidential documents as covers. No new dependency or image-processing extension is required.

Vite was built into `storage/framework/seminar-thumbnails-build` and its assets copied to the existing public build directory without removing older assets. The previous manifest is backed up at `storage/framework/seminar-thumbnail-prebuild-manifest.json`.

## Automated evidence

- Final independent PHPUnit event regression run passed **155 tests / 1,321 assertions**, using the existing transaction-safe test bootstrap and isolated `cc_db_test`; see `storage/framework/seminar-thumbnails-final.xml` and `.txt`. Includes learner/instructor thumbnail browse/detail, speaker invitation rendering, optional-objectives publication, upload retention/replacement/removal, MIME/dimensions/size, ownership, failed storage/database saves and capacity boundaries/floor. Intermediate reruns encountered shared-test-database contention from another PHPUnit process; this run was stopped until that process finished, then rerun independently. The older `seminar-thumbnails-regressions.*` files are superseded.
- Seven Node checks: schedule behavior, preview URL cleanup and missing-image initialization. Command: `node --test tests/JavaScript/seminar-thumbnail.test.mjs tests/JavaScript/seminar-schedule.test.mjs`.
- Scoped Pint, Blade compilation, production Vite build and whitespace checks passed.
- `node tests/Browser/seminar-thumbnails.mjs`: desktop/mobile multipart form, unsaved image preview/reset, optional Objectives, capacity help, layout and existing-event identity passed without JavaScript errors. It never submits event changes. Screenshots are `storage/framework/seminar-thumbnail-{desktop,mobile,event}.png`.
- Independent code review findings about early image errors and concurrent draft-deletion cleanup were addressed and reviewed again.

## Manual end-to-end checklist

Use QA accounts and a new event. Keep any existing event/attendance data.

1. As a Connector host, open **Seminars > Create**. Leave thumbnail and Objectives blank, complete required fields, and save. Expect a draft with a calendar image fallback. Confirm Description is still required.
2. Create or edit a QA event with a landscape JPG/PNG/WebP under 2 MB. Check preview before save, then save. Refresh; expect the same image. Change only title and save; expect the image retained.
3. Edit, choose a replacement, use **Reset selection**, and verify the saved image returns. Select another file and save; expect the new cover after refresh. Check **Remove saved thumbnail**, save, and expect the fallback. Selecting a file after checking removal should clear removal.
4. Submit a non-image, SVG/GIF, corrupt image or file larger than 2 MB. Expect a thumbnail validation error and the prior saved image unchanged. Correct the form and reselect a file; browsers cannot restore file selections after a server error.
5. Test capacity blank, 1 and 100,000; expect acceptance. Test 0, fractional input and 100,001; expect rejection. With two confirmed registrants, reducing capacity to 1 must be rejected.
6. Submit the event without Objectives for Admin review. Review the cover, Description, delivery, audience and schedule; approve, then publish as host. As an eligible learner, browse and open it; expect its cover and the correct event details. Check instructor browsing, host/admin lists, community upcoming events and roster headers where applicable.
7. Invite an instructor from an organization with a saved logo. On instructor invitation list/detail, verify organization logo/name and pending invitation controls. Accept, reopen both instructor and host pages, and verify confirmed-speaker status and no invitation Cancel action. Pending invitations should still be cancellable.
8. Check 390px/mobile and desktop layouts, keyboard file selection and removal checkbox, and missing-image fallback. For a legacy native webinar, confirm only its header identity changed and video controls still work.

## Remaining checks

The browser smoke check intentionally did not save real event changes or upload a new organization logo. Full human cross-role authoring/review/publication, physical-device checks, comprehensive accessibility, complete application suite and staging/deployment checks remain unrun. No commit, push or deployment was performed.
