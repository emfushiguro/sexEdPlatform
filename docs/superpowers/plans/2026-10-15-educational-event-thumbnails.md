# Educational event thumbnails implementation plan

**Goal:** Add optional thumbnails across real event surfaces, make Objectives optional and explain capacity limits.
**Architecture:** Existing Laravel multipart authoring and public disk; nullable path migration; shared Blade renderer and lightweight preview; current permissions/lifecycle preserved.
**Execution:** Current checkout only, user authorized. No reset/destructive seed; serialize PHPUnit using tests/existing-database-bootstrap.php. No commits or unrelated edits.

## Task 1: Upload and optional-authoring contract

- [x] Add meaningful `tests/Feature/Seminars/SeminarThumbnailTest.php` covering create/replace/keep/remove, invalid upload, authorization/ownership, failure cleanup and directory scope. Cover missing/blank Objectives through submission/approval/publish and capacity 1/100000/blank/invalid/confirmed floor.
- [x] Inspect current schema and add nullable thumbnail_path migration; apply only that forward migration to cc_db and cc_db_test, preserving existing records.
- [x] Add upload rules to StoreSeminarRequest/UpdateSeminarRequest and optional purpose to SeminarPublicationValidator. Persist uploads through authorized controller writes, preserving images on failed saves and scoped cleanup. Add Seminar::thumbnailUrl(): ?string using the public disk.
- [x] Run focused tests serially; update existing test expecting required purpose to the new approved behavior.

## Task 2: Event presentation

- [x] Add shared _thumbnail partial with cover/compact variants and absent/broken image fallback. Reuse public Seminar::thumbnailUrl(). Add upload/preview/remove form, multipart encoding, optional Objectives label and capacity guidance/max in create/edit.
- [x] Wire real learner/instructor browse/detail, Connector list/detail, Admin list/review, speaker invitations list/detail, community upcoming-events cards, attendance/registrant/monitor headers and native event identity. Leave inactive placeholder create/stub pages outside scope. Preserve active video regions and CSV exports.
- [x] Verify rendering of existing/no-image and uploaded-image events; browser check responsive preview/select/reset without changing real event data. Persisted removal covered by transactional HTTP tests.

## Task 3: Review and handoff

- [x] Run serial Seminar/EducationalEvent/ownership regressions, scoped Pint, Blade compilation, Node checks, isolated Vite build and whitespace checks. Update local built assets preserving existing ones. Final independent run: 155 tests / 1321 assertions; seven Node checks; desktop/mobile browser passed. Intermediate database contention runs are superseded by seminar-thumbnails-final.xml.
- [x] Review scope and security, fix findings and rerun covering checks. Record changes, migration, tests, manual QA and remaining limits in docs/educational-event-thumbnails-qa.md. Mark checklist completed accurately.
