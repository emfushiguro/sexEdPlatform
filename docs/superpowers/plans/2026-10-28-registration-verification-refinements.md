# Registration verification refinements implementation plan

> **For agentic workers:** Use subagent-driven-development for independent tasks and execute dependent learner flow changes inline. Track completion below.

**Goal:** Simplify applicant/admin verification UI, fix notification navigation, display all Persona selfie poses, collect teen local IDs before Persona, and request only necessary replacement evidence.

**Architecture:** Reuse Hosted Flow, private local evidence, Persona's existing protected media proxy, existing review queues, and submission-round/ownership locks. Preserve the explicit local/staging demo guards. Keep government ID plus selfie as one Persona correction unit; keep the three selfie poses together. Corrections are saved as drafts and submitted together for review.

**Tech stack:** Laravel 12, Blade/Alpine, Vite, PHPUnit on `cc_db_test`, Playwright CLI.

## Constraints

- Work on current branch `FacialVerification`; preserve unrelated edits and development data.
- No changes to real `.env`, no worktree, no destructive development DB commands, no remote deployment/push.
- Remove environment indicators from our visible applicant/admin/moderation UI, retaining provenance and validation server-side.
- Persona-owned warnings, desktop screens and capture-pose configuration remain in Persona Dashboard.
- Camera capture is a collection method, not authenticity proof; validate files server-side and retain manual portrait comparison.
- Serialize all database tests; stop browser fixture server before tests reset the isolated test database.

## Tasks

- [x] 1. Repair notification routes from stored resource IDs, including existing localhost/ngrok links and guardian approval payloads; retain recipient authorization and internal URL validation.
- [x] 2. Simplify applicant/admin environment copy and expose protected center/left/right Persona previews with absence handling; update focused view/media assertions.
- [x] 3. Introduce targeted dependent relationship corrections using existing evidence rounds, retaining acceptable documents and requiring replacements only for requested items; distinguish request changes from final rejection.
- [x] 4. Reorder teen school/institution flow to local ID capture first, Persona selfie second, final confirmation third; preserve session drafts and prevent direct skips.
- [x] 5. Add learner targeted corrections, retaining acceptable ID/selfie evidence where appropriate, requiring renewed comparison and rejecting stale/unauthorized requests. Government-ID correction repeats the linked Persona unit.
- [x] 6. Apply additive migrations normally after inspecting current schema; run focused regression suites serially, scoped formatting, JS checks and production asset build.
- [x] 7. Run Playwright against isolated synthetic accounts and mocked Persona, covering adult/guardian registration decisions, teen camera/error/retry/order, three-pose previews, dependent notifications/corrections and unauthorized requests; document live-provider limits.
- [x] 8. Review the connected changes, fix findings, record evidence and report remaining release gates.
