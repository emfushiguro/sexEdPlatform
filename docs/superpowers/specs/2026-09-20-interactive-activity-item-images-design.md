# Interactive Activity Item Images Design

**Date:** 2026-09-20  
**Status:** Approved for implementation planning  
**Scope:** Add optional instructional images to existing Matching and Sequencing items without changing their interaction or scoring systems

## 1. Purpose

Extend the existing Interactive Activity item envelopes so Matching source and target items and Sequencing items can display an optional instructional image. An item may be text-only, image-only, or contain both text and an image. Existing text-only activities must continue to render and behave exactly as they do now.

This work reuses the current Interactive Activity configuration JSON, handlers, authoring builders, Preview flow, learner components, and user-scoped Image Library. It does not add an activity type, media table, frontend dependency, or separate image-processing system.

## 2. Confirmed Product Decisions

1. Images are reusable, instructor-owned Image Library assets.
2. Authors can upload a new image inline or choose an existing library image.
3. New uploads occur immediately so the existing learner Preview can render them before the activity is saved.
4. Replacing or removing an activity image detaches the asset but does not delete the underlying library file.
5. The Image Library blocks deletion while a saved Interactive Activity references an asset.
6. JPEG, PNG, and WebP images up to 2 MB are supported.
7. The repository has no shared image resizer or optimizer, so this feature does not introduce one.
8. Matching displays a contained image above item text. Sequencing displays a compact contained thumbnail beside item text and stacks responsively at narrow widths.
9. Item text is optional when an image is attached.
10. Meaningful alt text is required whenever an image is attached. Item text is not copied into alt text automatically.
11. Existing scoring, Retry, Continue, Practice, completion, progress, authorization, ownership, and placement rules remain authoritative.

## 3. Existing Architecture

Interactive Activities are persisted in the `interactive_activities.configuration` JSON column. Matching and Sequencing use schema version 1 and stable opaque IDs. `MatchingActivityHandler` and `SequencingActivityHandler` normalize configuration, produce safe learner payloads, evaluate ID-based answers, and calculate answer fingerprints used for revision isolation.

The shared authoring partial initializes `interactive-activity-authoring.js`, while type-specific Blade partials render the Matching pair builder and Sequencing order builder. Preview submits the current form as `FormData`, validates and normalizes it through `InteractiveActivityAuthoringService`, then renders the real learner shell. Matching connection geometry is based on dot endpoint rectangles and already refreshes for resize, orientation, scrolling, and font readiness. Sequencing uses the shared pointer reorder primitive and supports pointer, touch, and keyboard operation.

The existing Image Library stores authenticated-user assets under `quiz-images/user-{id}` on the public disk. Its upload endpoint accepts files, its JSON endpoint exposes the current user's files, and its delete endpoint is scoped to the current user. Quiz question images use the same directory and storage convention. There is no reusable resizing or optimization service.

## 4. Item Data Contract

The existing item envelope is extended in place:

```json
{
  "id": "stable-item-id",
  "kind": "text",
  "value": "",
  "image_path": "quiz-images/user-12/example.webp",
  "image_alt": "Illustration of water evaporating from a lake"
}
```

The same optional fields apply to `pairs.*.left`, `pairs.*.right`, and `items.*`.

- `id` remains the opaque scoring identity.
- `kind` remains `text` for schema-version-1 compatibility. No parallel item kind is introduced.
- `value` is a trimmed string and may be empty only when `image_path` is present.
- `image_path` is an optional public-disk path, never a client-supplied URL.
- `image_alt` is required, trimmed, and limited to 500 characters whenever `image_path` exists.
- `image_alt` is cleared when the image is detached.
- `image_url` is derived for authoring and learner presentation and is never persisted.

No database migration or schema-version increment is required.

## 5. Validation and Normalization

The existing item counts stay unchanged: Matching requires 2-12 pairs and Sequencing requires 3-12 items.

Each item must contain nonblank text, an image path, or both. Handler validation rejects items with neither. Text keeps the existing 500-character limit. Alt text is mandatory for an attached image and is not accepted as a substitute for the image path.

Duplicate detection uses normalized text when text exists. For image-only items, it uses the exact image path. This preserves the existing ambiguity protection while permitting distinct image-only content.

On authoring requests, a newly attached path must exist under the authenticated user's Image Library directory. During an authorized edit, an unchanged path already stored on the activity remains valid even if the current authorized editor is not the original uploader. Arbitrary URLs, nonexistent new paths, and paths belonging to another user are rejected.

Normalization preserves valid existing pair and item IDs and stores only `id`, `kind`, `value`, `image_path`, `image_alt`, and the existing canonical position fields. Text-only legacy items normalize with null media fields and retain their current behavior.

## 6. Image Library Integration

The existing Image Library upload endpoint is extended to accept WebP alongside JPEG and PNG while retaining the 2 MB limit and current user-scoped directory. JSON upload responses add the stored path without removing existing response fields. The library JSON listing similarly exposes each asset's path and public URL.

Authoring never trusts a submitted public URL. It stores the returned path and derives URLs through the configured public disk. This keeps stored configuration portable across host or storage URL changes.

The library delete action checks saved Interactive Activity configurations for an exact reference to the scoped path. If referenced, deletion is rejected with an explanatory message. Unreferenced assets retain the current deletion behavior. Activity removal never deletes reusable Image Library files.

## 7. Authoring State and Controls

Default authoring items gain null media fields and transient UI state:

```js
{
    value: '',
    image_path: null,
    image_url: null,
    image_alt: '',
    imageUploading: false,
    imageError: ''
}
```

Every Matching side and Sequencing item provides:

- a text field labelled as optional when an image is attached;
- an Upload image control;
- a Choose from Image Library control;
- a bounded, contained preview;
- Replace and Remove controls; and
- an alt-text field shown and required while an image is attached.

The existing type-specific builders remain shared by Create and Edit. Hidden inputs submit `image_path` and `image_alt`; file inputs do not submit with the activity form because uploads complete through the Image Library endpoint.

A single shared library picker is mounted in the common activity fields. It loads the current user's library lazily, caches the result for the authoring session, tracks the invoking pair side or sequence item, attaches the selected asset without copying it, and restores focus when closed.

Media controls have touch-friendly targets and do not start row dragging. Reordering continues to move the complete item object, including its media fields and stable IDs.

## 8. Upload Lifecycle

Selecting a new file creates a temporary local preview and starts an immediate Image Library upload. Preview and Save controls are unavailable while any upload is pending.

On success, the returned permanent path and URL replace the temporary preview. When replacing an image, existing alt text remains available for revision. On failure, a replacement preserves the previous attachment and a new attachment remains empty. Errors are associated with the affected item and announced accessibly. Temporary object URLs are revoked after success, failure, replacement, removal, or component teardown.

Cancelling activity authoring after a successful upload leaves the file in the author's reusable library. This is intentional and avoids a second temporary-media lifecycle.

## 9. Preview Flow

Preview continues to submit the existing form and render the real learner shell. Because uploads complete before Preview, configuration contains only small paths and alt strings. Preview tokens never contain image binaries or base64 data, and no temporary upload storage is needed.

Preview and Save use identical configuration validation. Invalid text-or-image combinations, missing alt text, and unauthorized paths keep Preview closed and surface through the existing summary and inline validation mechanisms. Preview evaluation, Retry, and Practice retain media because every response derives the learner payload from normalized configuration.

## 10. Learner Payloads

Learner payloads expose only the fields required for presentation and interaction:

```json
{
  "id": "stable-item-id",
  "kind": "text",
  "value": "",
  "image_url": "/storage/quiz-images/user-12/example.webp",
  "image_alt": "Illustration of water evaporating from a lake"
}
```

The raw storage path is not required by learner JavaScript. Matching and Sequencing answers continue containing only stable IDs and the existing connection or order structures. Media is never used as submitted answer material.

## 11. Matching Learner Presentation

Each Matching card gains a content wrapper that may contain a contained image above the existing text. Empty text does not reserve space. Images use bounded responsive dimensions and `object-fit: contain`, allowing diagrams and illustrations to remain uncropped.

The endpoint dot remains a separate, fixed-size sibling at the card's connection edge. Correct, incorrect, pending, selected, and unanswered labels and badges remain visible. The existing interaction state machine is unchanged: select one endpoint, display the temporary line, select the opposite endpoint, retain the persistent connection, and repeat.

Accessible endpoint labels use nonblank text first, then image alt text, then a defensive generic label. The generic fallback is only for malformed legacy data. SVG lines remain decorative and hidden from assistive technology; state text, icons, buttons, and live announcements convey equivalent information without relying on color.

## 12. Matching Geometry

The current dot-based `getBoundingClientRect()` coordinate system remains authoritative. No card or image coordinates are introduced. Image height therefore affects layout without changing the connection endpoint definition.

Geometry refresh stays animation-frame batched and occurs after image load or error, connection changes, payload rehydration, Retry, Practice, container resize, viewport resize, orientation change, relevant scrolling, font readiness, and responsive wrapping. The existing `ResizeObserver` and global listeners remain in place and are released during teardown.

The two-column responsive layout and protected connection gutter remain. Images cannot cover or displace the 44-by-44-pixel endpoint targets.

## 13. Sequencing Learner Presentation

Each Sequencing row keeps its current position, feedback, badges, insertion indicator, and handle-only dragging. A compact contained thumbnail appears beside the item text and stacks within the content region at narrow container widths. Image-only rows do not reserve empty text space.

The floating drag representation includes a smaller thumbnail when present. Images use `draggable="false"` so native image dragging cannot compete with the existing pointer reorder session. Only the drag handle suppresses touch scrolling; the rest of the card, including the image, remains normally scrollable.

Drag handle labels and live announcements use nonblank text first, then image alt text, then a defensive `Item` fallback. Pointer, touch, and keyboard pickup, movement, drop, cancellation, edge scrolling, highlighting, and insertion behavior remain unchanged.

## 14. Missing-Image Recovery

Image elements hide the browser's broken-image presentation after a load failure. Text remains visible when present. A readable `Image unavailable` fallback includes the saved alt description, preserving semantic information for image-only items. Matching schedules another geometry refresh because the fallback may change card dimensions.

The deletion reference guard makes this condition uncommon, but the learner interface remains usable if deployment or filesystem state becomes inconsistent. Scoring continues because it depends on stable IDs.

## 15. Revision and Progress Semantics

Existing scoring remains ID-based. Answer fingerprints distinguish presentation-only media from semantic image-only content.

For a text-bearing item, normalized text remains its semantic fingerprint and image or alt edits do not increment the activity revision. For an image-only item, the semantic fingerprint contains its image path and normalized alt text. Replacing that image, materially changing its alt text, removing its only content, or converting it between image-only and text-bearing content changes the fingerprint.

Sequencing canonical-order changes and Matching relationship changes continue incrementing revisions normally. Presentation-only edits preserve existing learner progress. Semantic changes retain the existing stale-revision protection and progress isolation.

## 16. Interaction and Scoring Boundaries

The implementation does not change Matching evaluation, pair-result generation, Sequencing order comparison, position results, attempts, completion, Retry, Continue, Practice, working-state persistence, activity placement, checkpoint integration, or learner progress schema.

Handler changes are limited to media-aware rules, normalization, safe payload generation, duplicate detection, and answer fingerprinting. Existing text-only configuration remains a first-class supported input.

## 17. Permissions and Security

Current activity creation and update authorization remains authoritative. Additional media checks reject arbitrary paths, cross-user new paths, missing new assets, and client-supplied public URLs. Public URLs are always derived server-side from validated paths. Alt text is handled as plain escaped content.

The Image Library continues scoping upload, listing, and deletion to the authenticated user's directory. Learner endpoints cannot upload, attach, replace, or delete media. Existing admin restrictions on platform-owned content remain unchanged.

## 18. Accessibility

Instructional images require explicit alt text. Item text is never copied automatically into the alt field. Image-only items use alt text as their accessible interaction name. Upload, library, replace, remove, connection-dot, and drag-handle controls retain visible focus and touch-friendly sizing.

Matching continues to provide keyboard endpoint selection and non-color state labels. Sequencing retains Space/Enter pickup and drop, Arrow/Home/End movement, Escape cancellation, and polite announcements. Images do not create a gesture-only path or interfere with keyboard operation. Existing reduced-motion behavior remains.

## 19. Error and Recovery Behavior

Authoring reports unsupported types, files above 2 MB, upload failures, library loading failures, missing alt text, empty items, invalid paths, and in-use deletion attempts. Upload failure never clears unrelated text, ordering, existing images, or other activity state.

Learner rendering handles missing images locally while retaining the existing retryable request errors, revision-conflict response, and malformed-activity unavailable fallback. Retry continues preserving connections or sequence order. Continue remains available only under the existing completion rules.

## 20. Styling and Responsive Behavior

New media styles extend the current Interactive Activity component layer. Matching images are centered above text with a bounded height. Sequencing thumbnails remain compact beside text and stack only when the activity container becomes narrow. Authoring previews are bounded and uncropped.

Cards grow only as required, image-only cards retain usable minimum dimensions, the Matching gutter remains protected, and mobile layouts avoid horizontal scrolling. Existing selected, pending, correct, incorrect, dragged, and reduced-motion styles remain authoritative.

## 21. Testing Strategy

Handler tests cover text-only, image-only, and mixed normalization; missing-content and missing-alt rejection; duplicate image-only content; safe learner media payloads; unchanged scoring; and fingerprint behavior for supporting versus semantic media.

Feature tests cover create, Preview, save, edit, upload, library selection data, WebP support, unauthorized paths, unchanged legacy paths, deletion guards, permissions, and learner rendering. JavaScript tests cover upload and picker state, serialization, replacement and removal, accessible labels, image-load geometry refresh, Matching regression behavior, and Sequencing drag behavior with media fields.

Browser QA covers desktop pointer, mobile touch, keyboard-only operation, delayed image loading, scrolling, resizing, orientation changes, responsive reflow, Retry, Continue, Practice, correct/incorrect feedback, authoring CRUD, and existing text-only regressions.

Verification uses isolated test infrastructure and never resets, wipes, truncates, drops, recreates, or destructively reseeds the development database.

## 22. Implementation Boundaries

Expected changes are limited to the existing Image Library controller, Interactive Activity authoring service and handlers, authoring and learner JavaScript, type-specific Blade partials, Interactive Activity CSS, and focused tests. The existing application registration in `resources/js/app.js` should require no new component architecture.

No migration, activity type, scoring endpoint, progress schema, media framework, external dependency, audio change, or unrelated refactor is included.

## 23. Acceptance Criteria

The feature is accepted when authors can create, preview, save, and edit text-only, image-only, and mixed Matching and Sequencing items; upload and library selection work consistently; alt text is enforced; Matching geometry remains accurate through image and responsive layout events; Sequencing media does not interfere with dragging; Retry, Continue, Practice, scoring, and progress remain unchanged; referenced assets cannot be deleted accidentally; existing text-only activities regress cleanly; and focused tests, regression tests, formatting, production build, and browser QA pass.
