# My Development Workflow — Superpowers + VS Code Copilot

## The Golden Rule
`copilot-instructions.md` in every project = always-on rules, automatic, no effort.
Each chat session = one skill, one job, one stage of the workflow.
Pass outputs forward as attached files between sessions.

---

## SETUP (do once per project)

### 1. Create `.github/copilot-instructions.md` in the project root
Contents: stack, conventions, what's built, workflow rules.
This loads automatically in every Copilot session for that project. Never re-explain your project again.

### 2. Add superpowers as a second folder in VS Code
`File → Add Folder to Workspace → C:\Users\Jaded\superpowers`
`File → Save Workspace As → yourproject.code-workspace`
Now you can attach any SKILL.md directly into chat from the superpowers folder.

### 3. Add worktree directory to .gitignore
```
.worktrees/
```
Commit this before creating any worktree.

---

## THE COMPLETE SEQUENTIAL FLOW

---

### PHASE 1 — DESIGN

#### SESSION 1 · Brainstorming
**Skill:** `skills/brainstorming/SKILL.md`
**Trigger:** You have any new feature idea, change, or addition.
**What happens:**
- Copilot explores project context
- Asks clarifying questions one at a time
- Proposes 2-3 approaches with trade-offs
- Presents design in sections, you approve each
- Saves design doc to `docs/plans/YYYY-MM-DD-[feature]-design.md`

**Session-starter prompt:**
```
[Attach: skills/brainstorming/SKILL.md]

## Session Context
Working on: [project name]
Feature: [one sentence description]

Follow the brainstorming skill exactly. Do not write any code.
```

**Output to carry forward:** `docs/plans/YYYY-MM-DD-[feature]-design.md`

---

### PHASE 2 — PLANNING

#### SESSION 2 · Writing Plans
**Skill:** `skills/writing-plans/SKILL.md`
**Trigger:** Design doc is approved from Session 1.
**What happens:**
- Reads the design doc
- Breaks work into tasks of 2-5 minutes each
- Every task has: exact file paths, code to write, verification steps
- Enforces TDD structure in every task (write test → fail → code → pass → commit)
- Saves plan to `docs/plans/YYYY-MM-DD-[feature]-plan.md`

**Session-starter prompt:**
```
[Attach: skills/writing-plans/SKILL.md]
[Attach: docs/plans/YYYY-MM-DD-[feature]-design.md]

Follow the writing-plans skill. Create the implementation plan from this design.
```

**Output to carry forward:** `docs/plans/YYYY-MM-DD-[feature]-plan.md`

---

#### TERMINAL — Git Worktree Setup
**No skill needed. Run in terminal:**
```powershell
cd "path/to/your/project"
git worktree add .worktrees/[feature-name] feature/[feature-name]
```
This isolates your feature work from main. Safe to experiment.

---

### PHASE 3 — IMPLEMENTATION

#### SESSION 3 · Test-Driven Development
**Skill:** `skills/test-driven-development/SKILL.md`
**Trigger:** You have the plan from Session 2. Ready to code.
**What happens:**
- For each task in the plan:
  1. Write the failing test first
  2. Run it — confirm it FAILS (RED)
  3. Write minimal code to pass
  4. Run it — confirm it PASSES (GREEN)
  5. Refactor if needed
  6. Commit
- Iron law: code written before a test = delete it, start over

**Session-starter prompt:**
```
[Attach: skills/test-driven-development/SKILL.md]
[Attach: docs/plans/YYYY-MM-DD-[feature]-plan.md]

## Session Context
Branch: feature/[feature-name]
Worktree: .worktrees/[feature-name]

Start with Task 1. Follow TDD strictly. Write the failing test first.
```

**Same session:** Continue task by task through the plan.
**Start a new session:** When context gets long/polluted, or you get stuck and need a fresh perspective.

---

#### SESSION 3B · Systematic Debugging (when things break)
**Skill:** `skills/systematic-debugging/SKILL.md`
**Trigger:** Any test failure, bug, or unexpected behavior — at any point.
**What happens:**
- Phase 1: Read full error, reproduce consistently, find root cause
- Phase 2: Verify the fix hypothesis before applying
- Phase 3: Apply fix
- Phase 4: Verify fix works, check for regressions
- Iron law: NO fix without completing Phase 1. No guessing.

**Session-starter prompt:**
```
[Attach: skills/systematic-debugging/SKILL.md]

## Bug Report
Error/symptom: [paste full error or description]
File/test: [relevant file]
What I was doing when it happened: [one sentence]

Do not propose any fix until you have found the root cause. Follow the skill exactly.
```

---

### PHASE 4 — VERIFICATION

#### SESSION 4 · Verification Before Completion
**Skill:** `skills/verification-before-completion/SKILL.md`
**Trigger:** Before saying any task or feature is done. Before every commit.
**What happens:**
- Runs the actual test command (not "it should pass" — actually runs it)
- Shows full output with zero failures confirmed
- Runs linter if applicable
- Verifies original symptom/requirement is met

**Session-starter prompt:**
```
[Attach: skills/verification-before-completion/SKILL.md]
[Attach: docs/plans/YYYY-MM-DD-[feature]-plan.md]

## What I'm verifying
Claim: "Task [N] is complete"
Test command: [e.g. php artisan test / npm test / pytest]

Run verification now. Show the full output. Do not claim it passes without running it.
```

---

### PHASE 5 — CODE REVIEW

#### SESSION 5 · Requesting Code Review
**Skill:** `skills/requesting-code-review/SKILL.md`
**Also attach:** `agents/code-reviewer.md`
**Trigger:** After completing a major feature or before merging.
**What happens:**
- Gets git SHAs (base and head of your feature)
- Dispatches code reviewer with your plan + the diff
- Reviews for spec compliance first, then code quality
- Reports issues by severity (critical blocks progress)

**Session-starter prompt:**
```
[Attach: skills/requesting-code-review/SKILL.md]
[Attach: agents/code-reviewer.md]
[Attach: docs/plans/YYYY-MM-DD-[feature]-plan.md]

## Review Request
What I built: [one sentence]
Base branch: main
Feature branch: feature/[feature-name]

Follow the requesting-code-review skill. Review for spec compliance first, then code quality.
```

---

#### SESSION 6 · Receiving Code Review
**Skill:** `skills/receiving-code-review/SKILL.md`
**Trigger:** You have review feedback to act on.
**What happens:**
- Restates each feedback item in its own words
- Verifies each point against the actual codebase (doesn't blindly agree)
- Pushes back with reasoning if a point is wrong
- Implements one item at a time, verifies after each

**Session-starter prompt:**
```
[Attach: skills/receiving-code-review/SKILL.md]

## Review Feedback
[paste the review feedback here]

Process this feedback using the skill. Restate each item before implementing. Do not implement everything at once.
```

---

### PHASE 6 — FINISHING

#### SESSION 7 · Finishing a Development Branch
**Skill:** `skills/finishing-a-development-branch/SKILL.md`
**Trigger:** All tasks in the plan are done, all reviews are addressed.
**What happens:**
- Runs full test suite one final time
- Determines base branch
- Presents options: merge to main / open PR / keep branch / discard
- Executes your choice
- Cleans up worktree

**Session-starter prompt:**
```
[Attach: skills/finishing-a-development-branch/SKILL.md]

## Feature Complete
Branch: feature/[feature-name]
Worktree: .worktrees/[feature-name]
All tasks done. All reviews addressed.

Follow the skill. Run tests first, then present my options.
```

---

## SPECIAL SITUATIONS

### When multiple unrelated things break at once
**Skill:** `skills/dispatching-parallel-agents/SKILL.md`
Open one chat session per independent broken thing.
Paste `systematic-debugging/SKILL.md` into each.
Work them simultaneously instead of sequentially.

### When you want to create your own custom skill
**Skill:** `skills/writing-skills/SKILL.md`
Follow TDD for documentation: write a test scenario first, confirm an agent fails without the skill, write the skill, confirm the agent passes.

---

## QUICK REFERENCE — WHICH SKILL FOR WHAT SITUATION

| Situation | Skill to attach |
|---|---|
| New feature/change idea | `brainstorming/SKILL.md` |
| Approved design, need a plan | `writing-plans/SKILL.md` |
| Writing code for a task | `test-driven-development/SKILL.md` |
| Something is broken | `systematic-debugging/SKILL.md` |
| About to say "done" | `verification-before-completion/SKILL.md` |
| Feature complete, need review | `requesting-code-review/SKILL.md` + `agents/code-reviewer.md` |
| Got review feedback | `receiving-code-review/SKILL.md` |
| Ready to merge or PR | `finishing-a-development-branch/SKILL.md` |
| Multiple independent bugs | `dispatching-parallel-agents/SKILL.md` |
| Building a new skill | `writing-skills/SKILL.md` |

---

## SESSION-STARTER TEMPLATE (copy for every new session)

```
[Attach: skills/RELEVANT-SKILL/SKILL.md]
[Attach: relevant output file from previous session if needed]

## Session Context
Project: [project name]
Branch: [current branch]
What I'm doing: [one sentence]
Relevant files: #file:path/to/file.php

Follow the attached skill exactly.
```

---

## THE FLOW AT A GLANCE

```
IDEA
 │
 ▼
[S1] brainstorming ──────────────────→ design doc saved
 │
 ▼
[S2] writing-plans ──────────────────→ plan saved
 │
 ▼
[Terminal] git worktree add
 │
 ▼
[S3] test-driven-development
      ↕ (if broken)
    [S3B] systematic-debugging
 │
 ▼
[S4] verification-before-completion ─→ tests pass, confirmed
 │
 ▼
[S5] requesting-code-review
 │
 ▼
[S6] receiving-code-review ──────────→ all feedback addressed
 │
 ▼
[S7] finishing-a-development-branch ─→ merged / PR opened
 │
 ▼
SHIPPED ✓
```
