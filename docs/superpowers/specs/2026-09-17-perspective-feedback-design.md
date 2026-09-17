# Perspective Feedback Interactive Checkpoint Design

**Date:** 2026-09-17
**Status:** Approved for implementation planning

## Summary

Perspective Feedback is a new Interactive Checkpoint question type for scenario-based reflection. A learner completes it by either selecting an instructor-authored guided response and receiving feedback specific to that response, or—when enabled—submitting an ungraded written perspective.

The feature extends the existing checkpoint architecture. It reuses `quiz_questions`, `quiz_options`, `interactive_checkpoint_progress`, the current checkpoint placements, existing authorization, and lesson/module completion. It does not create a parallel checkpoint system and does not enter formal quiz scoring.

## Confirmed Product Decisions

1. Perspective Feedback is available only to Interactive Checkpoints, in both inside-topic and between-topic placements.
2. The existing neutral “Skip for now” behavior remains available and counts as resolved.
3. Every Perspective Feedback checkpoint has 2–12 guided response options, even when written perspectives are enabled.
4. Written responses default to a 1,000-character limit configurable from 100 through 5,000.
5. The scenario uses the existing rich-text editor. Supporting Perspective Feedback fields are plain text.
6. When both pathways are available, accessible selection cards reveal either “Choose a Response” or “Share Your Perspective.”
7. The first valid response is final and restored on reload.
8. Guided submissions snapshot the submitted option text and feedback so later author edits do not rewrite learner history.
9. Written submissions show the learner’s text, Reflection Guide, and optional general explanation without grading language.
10. No new instructor reporting surface is included.
11. Conversion to or from Perspective Feedback is allowed only before learner progress exists.

## Goals

- Let instructors author nuanced scenarios with independently associated response feedback.
- Let learners choose one guided response or optionally write their own perspective.
- Store both pathways through existing checkpoint progress.
- Complete progression after one valid pathway without assigning correctness or quiz points.
- Preserve stable option identities and understandable historical submissions during later author edits.
- Keep all six existing question types and formal quiz behavior unchanged.

## Non-Goals

- AI analysis, moderation, grading, or quality evaluation of learner writing.
- Instructor review queues, response rosters, analytics, aggregate reporting, or exports.
- A new scoring engine, quiz attempt type, or checkpoint subsystem.
- Requiring both a guided and written response.
- Treating written responses as alternative correct answers.
- Redesigning the broader checkpoint or lesson interface.

## Existing Architecture

Interactive Checkpoints currently reuse quiz-domain records:

- `quiz_questions` stores checkpoint questions by `checkpoint_topic_id` and optional `checkpoint_block_uuid`.
- `quiz_options` stores choice options.
- `interactive_checkpoint_progress` stores one learner/question progress row, the latest answer payload, correctness, attempts, and completion timestamps.
- `QuestionAuthoringService` validates and persists the six existing question types for both quizzes and checkpoints.
- `QuestionEvaluator` grades existing types.
- `InteractiveCheckpointController` authorizes learner access, evaluates submissions, and writes progress.
- The learner checkpoint Blade partial and JavaScript component render, submit, retry, skip, and continue.
- `LessonController` and `LearnerModuleCompletionService` recognize `correct` and `skipped` progress as resolved.

Perspective Feedback will use these same integration points while bypassing correctness evaluation.

## Data Model

### Incremental schema change

A normal additive migration will:

- add `perspective_feedback` to the existing `quiz_questions.question_type` enum;
- add nullable `context_description` text to `quiz_questions`;
- add `allow_own_perspective` boolean, defaulting to false, to `quiz_questions`;
- add nullable `perspective_prompt` text to `quiz_questions`;
- add nullable unsigned `perspective_character_limit` to `quiz_questions`;
- add nullable `reflection_guide` text to `quiz_questions`;
- add nullable `feedback` text to `quiz_options`.

The existing `question_text` column stores the required scenario. The existing `explanation` column stores the optional general explanation.

No table is reset, recreated, wiped, truncated, or reseeded.

### Model behavior

`QuizQuestion` will expose the new fields through its existing fillable/cast conventions. Boolean and integer configuration will be cast explicitly. `QuizOption` will allow the new `feedback` field.

Perspective Feedback records use:

- `points = 0`;
- `acceptable_answers = null`;
- `case_sensitive = false`;
- `word_bank = null`;
- `image_path = null`;
- every option’s `is_correct = false`.

### Progress payloads

A guided response is stored in `interactive_checkpoint_progress.latest_answer` as a neutral snapshot:

```json
{
  "pathway": "guided",
  "option_id": 123,
  "option_text": "Listen and ask what they need.",
  "feedback": "Listening respects the person’s boundaries and gives them room to identify what support would help."
}
```

A written response is stored as:

```json
{
  "pathway": "own",
  "perspective_text": "The learner’s original response"
}
```

For both pathways:

- `status = completed`;
- `is_correct = null`;
- `answered_at` and `completed_at` are set;
- `attempt_count` increments once for the accepted submission.

Skipping continues to use `status = skipped` and `is_correct = null`.

## Question-Type Isolation

The shared authoring service must not make Perspective Feedback available to formal quizzes. Type definitions will distinguish quiz types from checkpoint types:

- formal quiz types remain the existing six;
- checkpoint types are the existing six plus `perspective_feedback`.

Formal quiz controllers and views continue using only formal quiz types. Checkpoint creation and editing use checkpoint-aware validation and type metadata.

`QuestionEvaluator` will not receive Perspective Feedback questions. The learner checkpoint controller branches to neutral Perspective Feedback submission handling before evaluation. This is the primary defense against accidental grading.

## Authoring Experience

### Type selection

Perspective Feedback appears only in Interactive Checkpoint type selectors. Its description explains that learners may choose a guided perspective or, when enabled, share their own.

### Scenario and context

- Scenario is required and stored in `question_text`.
- It uses the current rich-text editor and the existing meaningful-content validation that rejects visually empty HTML.
- Context is optional plain text with a 5,000-character maximum.

### Guided response builder

Perspective Feedback replaces correct-answer controls with 2–12 response rows. Each row includes:

- response text, required, maximum 500 characters;
- response-specific feedback, required, maximum 5,000 characters;
- accessible move-up and move-down controls;
- an accessible remove control;
- a hidden stable option ID for existing rows.

No correct-answer radio or checkbox is rendered. Reorder controls change presentation order without changing the option identity.

### General explanation

The existing `explanation` field is relabelled “General Explanation” or “Why This Matters” for this type. It is optional, limited to 5,000 characters, and displayed after either valid response pathway.

### Own-perspective settings

An instructor switch controls whether the learner may write their own perspective. When enabled:

- custom prompt is required and limited to 500 characters;
- character limit is required, defaults to 1,000, and must be between 100 and 5,000;
- Reflection Guide is optional and limited to 5,000 characters.

Disabling the switch hides the learner pathway but does not needlessly erase stored prompt, limit, or guide configuration. Those inactive values do not participate in learner submission validation.

### Client validation

The authoring JavaScript mirrors server rules, retains entered values after validation errors, and focuses the first invalid control. Server validation remains authoritative.

## Stable Option Synchronization

The existing option replacement method deletes and recreates all rows. Perspective Feedback instead uses a dedicated transactional synchronizer:

1. Validate that submitted IDs are distinct.
2. Validate that every submitted existing ID belongs to the current question.
3. Update matched options in place with response text, feedback, `is_correct = false`, and the new order.
4. Create rows that have no ID.
5. Delete existing rows omitted from the submitted collection.

This preserves IDs across text edits and reordering. Deleting an option does not break completed learner history because guided progress stores the selected ID, response text, and feedback snapshot.

Changing a question to or from Perspective Feedback is rejected if any `interactive_checkpoint_progress` row exists for it. This restriction is limited to conversions involving Perspective Feedback and does not unnecessarily change existing conversions among the original types.

## Learner Experience

### Shared presentation

The checkpoint keeps the existing responsive container and lesson placement behavior. It displays:

1. a “Perspective Feedback” label;
2. the rich-text scenario;
3. optional context;
4. pathway controls;
5. neutral submission feedback and continuation.

### Pathway choice

When written perspectives are enabled, two keyboard-operable selection cards are shown:

- “Choose a Response”;
- “Share Your Perspective.”

Selecting a card reveals only that pathway. The learner is never asked to complete both. When written perspectives are disabled, the guided pathway appears directly.

### Guided pathway

Guided options are rendered as one accessible radio group. Before submission, the learner selects one option and activates “Submit Response.”

After submission:

- the selected option remains visibly and semantically selected;
- controls become read-only;
- “Your Response” shows the snapshotted option text;
- “Feedback” shows only that option’s snapshotted feedback;
- optional “Why This Matters” shows the general explanation;
- no correct/incorrect language or styling is used;
- the existing Continue action becomes available.

### Written pathway

The written pathway displays:

- the instructor’s prompt;
- optional Reflection Guide in a distinct guidance panel;
- a plain-text textarea;
- a visible and programmatically associated remaining-character count;
- “Submit Perspective.”

After submission:

- “Your Perspective” shows the stored original text;
- the Reflection Guide remains visible;
- optional “Why This Matters” shows the general explanation;
- no option-specific feedback, correctness, quality judgment, or review-pending state appears.

### Skip and retry behavior

“Skip for now” remains available. A skip resolves the checkpoint using existing completion behavior, shows neutral skip messaging, and does not reveal option feedback or the general explanation. A skipped learner may later submit a valid response, matching current behavior.

The first valid guided or written submission is final. Reloading restores it from the progress snapshot. Repeated submission requests return the stored result rather than replacing the original response.

## Submission Validation and Error Handling

The learner submits one structured `answer` object.

Guided example:

```json
{
  "answer": {
    "pathway": "guided",
    "option_id": 123
  }
}
```

Written example:

```json
{
  "answer": {
    "pathway": "own",
    "perspective_text": "..."
  }
}
```

Guided validation requires an integer option ID belonging to the submitted checkpoint and confirms that the option still has feedback. A removed, foreign, duplicate, or malformed identifier is rejected without changing progress.

Written validation requires own-perspective mode to be enabled, a non-empty string after whitespace checking, and a Unicode-aware length within the configured limit. The original accepted text is stored; its meaning is never validated.

Authorization remains unchanged: the learner must be authenticated, enrolled with approved status, and accessing a published lesson in a learner-visible module. The question must belong to a checkpoint topic.

Submission and progress persistence are transactional and rely on the existing unique learner/question constraint to prevent duplicate progress rows. Network or validation failure retains client input so the learner can retry.

## Feedback Contract

A successful neutral response returns fields sufficient for the learner component to restore and render the result:

- `status = completed`;
- `is_correct = null`;
- selected `pathway`;
- stored response snapshot;
- response-specific feedback for guided submissions only;
- optional general explanation.

The UI uses neutral labels including “Your Response,” “Your Perspective,” “Feedback,” “Reflection,” and “Why This Matters.” It does not use “Correct,” “Incorrect,” “Wrong Answer,” or “Correct Answer” for Perspective Feedback.

## Progress and Completion

The new `completed` progress state is added wherever resolved checkpoints are currently recognized, including:

- learner checkpoint initial state;
- footer ownership and Continue visibility;
- lesson navigation;
- resolved learning-item calculation;
- between-topic completion;
- module completion service;
- reload behavior.

Existing types continue using their current `correct`, `incorrect`, and `skipped` states. Perspective Feedback uses `completed` and `skipped` as resolved states.

Perspective Feedback never:

- creates a `QuizAttempt`;
- invokes correctness evaluation;
- changes quiz scores or pass/fail results;
- consumes or refunds shields;
- awards correctness points;
- increments a correct-answer count.

## Accessibility and Responsive Design

- Use semantic fieldsets and legends for guided responses.
- Provide explicit labels for all fields.
- Make pathway cards keyboard operable with visible focus.
- Use touch targets of at least 44 pixels where practical.
- Communicate selection with text or an icon as well as color.
- Provide accessible labels for option reorder/remove controls.
- Connect validation messages through `aria-describedby` and `aria-invalid`.
- Announce submission results through a suitable live region.
- Preserve focus and entered text after errors.
- Do not require drag-and-drop to reorder options.
- Avoid unnecessary animation.
- Escape all plain-text supporting content during rendering.

## Permissions and Privacy

Existing topic, lesson, ownership, and admin mutation authorization remains authoritative for authoring. Existing enrollment and publication checks remain authoritative for learner submissions.

No new instructor response roster, reporting page, analytics panel, or export is included. Responses remain in checkpoint progress for future separately designed reporting. This avoids expanding access to reflective learner writing without a dedicated privacy and permissions review.

## Testing Strategy

### Schema and models

- Verify new columns, casts, and option feedback.
- Verify checkpoint acceptance and formal quiz rejection of the new type.
- Verify Perspective Feedback always persists zero points and false option correctness.

### Authoring

- Create in both placements.
- Validate 2–12 options and required per-option feedback.
- Enable/disable written perspectives.
- Validate prompt and 100–5,000 character-limit configuration.
- Add/remove Reflection Guide and general explanation.
- Save and reload all configuration.
- Reorder and edit options without changing IDs or feedback associations.
- Delete an option without changing remaining IDs.
- Reject duplicate and foreign option IDs.
- Reject type conversion after progress exists.

### Guided learner flow

- Render scenario, context, and all options.
- Submit a valid option and snapshot its text and feedback.
- Return `completed` and `is_correct = null`.
- Show only the selected option’s feedback.
- Restore the stored result on reload.
- Reject an option from another checkpoint or one deleted concurrently.
- Prevent replacement of a completed response.

### Written learner flow

- Hide the pathway when disabled.
- Render prompt, guide, textarea, and counter when enabled.
- Store the original response within the configured limit.
- Reject empty, malformed, disabled, or over-limit submissions.
- Return `completed` and `is_correct = null`.
- Display the learner’s text and optional general explanation without grading language.

### Completion and regression

- Verify guided, written, and skipped pathways resolve progression.
- Verify both placements continue correctly.
- Verify lesson and module completion recognize `completed`.
- Verify no quiz attempt, score, shield, or points change occurs.
- Run the existing provider coverage for all six original checkpoint types in both placements.
- Verify formal quiz authoring, rendering, grading, and results remain unchanged.

### JavaScript and accessibility

- Verify pathway switching submits only one pathway.
- Verify character count and client validation.
- Verify completed state exposes Continue.
- Verify network errors preserve input.
- Verify selected state is not communicated by color alone.
- Preserve all existing checkpoint JavaScript tests.

## Implementation Boundaries

Expected changes are limited to:

- one additive migration;
- `QuizQuestion` and `QuizOption` metadata;
- checkpoint-aware question authoring and Perspective Feedback option synchronization;
- checkpoint creation/editing UI and JavaScript;
- learner checkpoint submission, rendering, and JavaScript;
- completion status consumers;
- focused unit, feature, JavaScript, schema, and regression tests.

Unrelated checkpoint functionality and formal quiz behavior will not be rewritten.
