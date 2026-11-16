# Feedback and progress refinements implementation plan

**Goal:** Apply the approved centered responsive feedback, certificate navigation, topic points, and consistent optional/quiz progress rules.

**Architecture:** Keep feedback presentation in the existing manager/host. Use LearnerModuleCompletionService as the common reader for topic and quiz progress; retain the existing certificate POST endpoint and server authorization. Optional interactions preserve their honest skipped/completed state without blocking certificates.

## Constraints

- Work on FeedbackManager in this existing worktree.
- Preserve development data; no database resets or destructive seeders.
- Test database-backed cases using existing schema and transactions only.
- Formal quiz results retain their scores/pass state. A completed attempt is visibly recorded even when further attempts are required for eligibility.

## Tasks

1. Reconcile the remote branch and reproduce sidebar/certificate mismatches; add failing regression tests for skipped optional items, quizzes across lessons, incomplete attempts, and certificate eligibility.
2. Share completed-topic and quiz eligibility readers across lesson/module/certificate paths. Ignore optional items for required completion; display saved skipped states. Preserve practice/revisit progress and await successful Skip before Continue navigation.
3. Center feedback with safe viewport margins, bounded scrollable height, responsive illustrations, stacked mobile actions, and the correct-answer module illustration. Delegate certificate claiming to a protected module-specific form action; use View certificate when already issued.
4. Retain the points Toast with actual idempotent topic/lesson awards and suppress redundant topic success Toasts.
5. Run focused JS/PHP regressions, lint, view compilation, frontend build, and browser checks at phone/tablet/desktop sizes. Record actual limits and review the final diff.

## Implementation and verification

- Reconciled remote FeedbackManager by fast-forward. Preserved identical original SVG inputs in an ignored backup.
- Shared topic/quiz readers now drive the sidebar and certificate checks. Optional interactions no longer block completion; finished failed attempts are recorded separately from pass/attempt-limit eligibility.
- Fixed sidebar checkpoint resolution and quiz status across lessons. The saved Sample Module data has a passed Lesson 2 Quiz; Lesson 1's Lesson Quiz has no completed attempt. No learner records were changed to bypass that requirement.
- Added centered responsive cards, stacked phone buttons, correct-answer module illustration, protected certificate claiming, and existing-certificate navigation.
- Retained idempotent combined topic/lesson points and suppressed the redundant topic success Toast when the points notice is shown.
- Module completion rewards are serialized and once-only. Current eligibility rechecks required progress even after a prior completion stamp; issued certificate ownership/view behavior is unchanged.
- Focused JavaScript: 169 passed. In-memory PHP regressions, completion-reward tests, and asset tests: 13 passed / 102 assertions. No existing database was reset or reseeded.
- PHP syntax, Blade compilation, Vite build, manifest file checks, and diff whitespace checks passed. Vite retains its existing large Agora chunk warning.
- Browser display-only QA: centered and within bounds at 320x568, 390x844, 768x1024, 1440x900, and 844x390; action height >=44px. Long feedback scrolls internally and its button remains reachable. Module heading receives focus; Shift+Tab wraps to the final action. Certificate action dispatches a CSRF-protected POST (intercepted for QA, no certificate issued).
- Review found and verified a fix for stale completion timestamps bypassing undone required progress. No remaining source findings in the bounded review.
- Full existing MySQL feature suite and real-device/screen-reader/audio checks remain unrun. Certificate notification delivery was not exercised.

## Follow-up: topic popup and required lesson quizzes

- Replaced the retained topic points Toast with a `topic_completed` centered completion dialog. Display actual awarded points and suppress equivalent success/points Toasts; keep ordinary notifications unchanged.
- Finishing lesson topics with an unresolved active quiz now says "Lesson topics complete" and offers "Take lesson quiz". Only quiz-resolved lessons offer advancement.
- Shared progression checks protect lesson GETs, lesson/topic completion POSTs, quiz start, and quiz submission. Inline quizzes redirect before rendering when required topics are incomplete. Sidebar completion/locking uses topics plus resolved quizzes, including cumulative locks for later lessons.
- Kept existing quiz pass/attempt-limit eligibility rules. Skipped optional interactions never substitute for formal quiz attempts.
- Focused PHP regressions cover direct GET/POST bypasses, inline quiz preflight, topic popup payload/awards, and the rendered lesson layout's duplicate-Toast suppression. Browser QA confirmed a centered topic dialog with the 25-point example at mobile width. No development learner progress was changed for verification.
