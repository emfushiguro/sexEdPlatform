# My Development Workflow — Superpowers

## THE CORE 3-STAGE FLOW

### STAGE 1 — DESIGN

#### SESSION 1 · Brainstorming
**Skill:** `skills/brainstorming/SKILL.md`
**Goal:** Turn an idea into an approved design doc.

**What happens:**
- Copilot explores current project context
- Asks clarifying questions one at a time
- Proposes 2-3 options with trade-offs
- Presents a design and gets approval
- Saves to `docs/plans/YYYY-MM-DD-[feature]-design.md`

**Session-starter prompt:**
```md
[Attach: skills/brainstorming/SKILL.md]

## Session Context
Project: [project name]
Feature: [one sentence description]

Follow the brainstorming skill exactly. Do not write code.
```

**Output to carry forward:** `docs/plans/YYYY-MM-DD-[feature]-design.md`

---

### STAGE 2 — PLAN

#### SESSION 2 · Writing Plans
**Skill:** `skills/writing-plans/SKILL.md`
**Goal:** Convert approved design into a concrete execution plan.

**What happens:**
- Reads the approved design doc
- Breaks work into small, testable tasks
- Adds exact file paths and verification steps
- Saves to `docs/plans/YYYY-MM-DD-[feature]-plan.md`

**Session-starter prompt:**
```md
[Attach: skills/writing-plans/SKILL.md]
[Attach: docs/plans/YYYY-MM-DD-[feature]-design.md]

Create the implementation plan from this design.
```

**Output to carry forward:** `docs/plans/YYYY-MM-DD-[feature]-plan.md`

---

### STAGE 3 — EXECUTE

#### SESSION 3+ · Executing Plans
**Skill:** `skills/executing-plans/SKILL.md`
**Goal:** Implement the plan task-by-task until complete.

**What happens:**
- Executes tasks from the plan in order
- Verifies each task before moving to the next
- Updates progress continuously
- Produces completed implementation ready for review or merge

**Session-starter prompt:**
```md
[Attach: skills/executing-plans/SKILL.md]
[Attach: docs/plans/YYYY-MM-DD-[feature]-plan.md]

## Session Context
Branch: feature/[feature-name]
Worktree: .worktrees/[feature-name]

Start with Task 1 and execute the plan in order.
```

---

## SUPPORTING SKILLS (USE WHEN NEEDED)

| Situation | Skill to attach |
|---|---|
| A task is blocked by a bug | `skills/systematic-debugging/SKILL.md` |
| You need pre-merge validation | `skills/verification-before-completion/SKILL.md` |
| You want formal review | `skills/requesting-code-review/SKILL.md` |
| You are processing review feedback | `skills/receiving-code-review/SKILL.md` |
| You are finishing a complete branch | `skills/finishing-a-development-branch/SKILL.md` |
| Multiple independent bugs | `skills/dispatching-parallel-agents/SKILL.md` |

---

## OPTIONAL TERMINAL STEP — WORKTREE

```powershell
cd "path/to/your/project"
git worktree add .worktrees/[feature-name] feature/[feature-name]
```

---

## SESSION-STARTER TEMPLATE

```md
[Attach: skills/RELEVANT-SKILL/SKILL.md]
[Attach: relevant output file from previous session if needed]

## Session Context
Project: [project name]
Branch: [current branch]
What I am doing: [one sentence]
Relevant files: #file:path/to/file.php

Follow the attached skill exactly.
```

---

## FLOW AT A GLANCE

```txt
IDEA
 |
 v
[S1] brainstorming ------------------> design doc saved
 |
 v
[S2] writing-plans ------------------> plan saved
 |
 v
[S3+] executing-plans ---------------> implemented and verified
 |
 v
SHIPPED
```
