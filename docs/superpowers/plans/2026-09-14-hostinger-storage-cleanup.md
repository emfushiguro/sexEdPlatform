# Hostinger Public Storage Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Laravel public-disk uploads survive Hostinger Git deployments and resolve through `/storage/...` without tracking runtime media in the deployment tree.

**Architecture:** `storage/app/public` remains the canonical public disk root. `public/storage` becomes a deployment-created symlink only. The repository retains already tracked seeded public-disk assets but stops allowing new runtime uploads to be staged, and the deployment guide performs a recoverable migration when an old real `public/storage` directory exists.

**Tech Stack:** Laravel filesystem, Git ignore rules, Hostinger SSH shell, PHPUnit feature tests.

## Global Constraints

- Never reset, wipe, truncate, recreate, or destructively reseed the database.
- Do not run `migrate:fresh`, `db:wipe`, `TRUNCATE`, `DROP DATABASE`, `DROP TABLE`, or unscoped deletes.
- Do not delete production media during migration; copy it and move the old directory to a timestamped backup.
- Do not add a controller, route, migration, dependency, or application image proxy.
- Run `php artisan optimize:clear` before rebuilding production caches.

## File Map

- Modify: `.gitignore` — stop unignoring the entire public disk and keep the public storage link ignored.
- Modify: `storage/app/.gitignore` — whitelist only the static certificate template; keep runtime public-disk directories ignored while preserving already tracked seed files.
- Remove from Git index: `public/storage/**` — delete the duplicate tracked tree from future checkouts without deleting the current working-tree target.
- Modify: `docs/FRESH_SERVER_SETUP.md` — document the one-time media migration and repeatable Hostinger deployment sequence.
- No application PHP files change; the existing `config/filesystems.php` mapping and `Module::thumbnail_url` behavior remain the source of truth.

### Task 1: Tighten storage ignore rules

**Files:**
- Modify: `.gitignore`
- Modify: `storage/app/.gitignore`

**Interfaces:**
- Produces: Git ignores newly uploaded public-disk files while existing tracked seed files remain tracked.

- [ ] **Step 1: Update the root ignore rules**

Remove these two rules from `.gitignore`:

```gitignore
!/storage/app/public/
!/storage/app/public/**
```

Keep this rule unchanged:

```gitignore
/public/storage
```

- [ ] **Step 2: Keep only the static certificate template whitelisted**

In `storage/app/.gitignore`, replace the public-directory whitelist with:

```gitignore
*
!private/
!public/
!.gitignore
!public/certificate-template/
!public/certificate-template/**
```

Already tracked files under `storage/app/public` remain in Git; this rule only prevents new runtime files from being added accidentally.

- [ ] **Step 3: Verify ignore behavior**

Run:

```powershell
git check-ignore -v -- storage/app/public/modules/new-upload.png storage/app/public/avatars/new-avatar.png
git check-ignore -v --no-index -- public/storage/new-upload.png
git ls-files storage/app/public/modules | Select-Object -First 1
```

Expected: both new runtime paths report `storage/app/.gitignore` as the ignore rule, `public/storage` reports `.gitignore`, and the final command still prints an existing tracked seed file.

- [ ] **Step 4: Commit the ignore-rule change**

```powershell
git add -- .gitignore storage/app/.gitignore
git commit -m "chore(storage): ignore runtime public uploads"
```

### Task 2: Remove the duplicate tracked public storage tree

**Files:**
- Remove from Git index: `public/storage/**`

**Interfaces:**
- Consumes: the canonical `storage/app/public` tree and the existing ignored `/public/storage` rule.
- Produces: a repository with zero tracked `public/storage` files, allowing `storage:link` to create the link on Hostinger.

- [ ] **Step 1: Remove only the duplicate paths from the index**

Run:

```powershell
git rm -r --cached -- public/storage
```

The `--cached` flag leaves the current working-tree files untouched. Do not replace it with a command that deletes the working tree.

- [ ] **Step 2: Verify the staged removal and canonical files**

Run:

```powershell
git ls-files public/storage
git ls-files storage/app/public | Measure-Object -Line | Select-Object Lines
git status --short
```

Expected: the first command prints no paths, canonical `storage/app/public` files remain tracked, and the status shows only the intended public-storage deletions plus the ignore-rule changes.

- [ ] **Step 3: Commit the duplicate-tree removal**

```powershell
git commit -m "chore(storage): remove tracked public link tree"
```

### Task 3: Document the safe Hostinger deployment sequence

**Files:**
- Modify: `docs/FRESH_SERVER_SETUP.md`

**Interfaces:**
- Consumes: Laravel’s configured link `public/storage -> storage/app/public`.
- Produces: copy-paste commands for the first cleanup and all later deployments.

- [ ] **Step 1: Replace the bootstrap command block**

Document this order after the Git checkout:

```bash
cd /path/to/your/project

php artisan optimize:clear
php artisan migrate --force

mkdir -p storage/app/public
if [ -d public/storage ] && [ ! -L public/storage ]; then
    cp -a public/storage/. storage/app/public/
    mv public/storage "public/storage-backup-$(date +%Y%m%d-%H%M%S)"
fi
php artisan storage:link --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

test -L public/storage
```

Explain that the `cp`/`mv` block is safe to run on every deployment: it only acts when Hostinger has checked out a real directory, copies its media into the canonical disk, and keeps a recoverable backup. It must not be replaced with `rm -rf public/storage`.

- [ ] **Step 2: Add production verification commands**

Document these checks for the currently reported file:

```bash
test -f storage/app/public/modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png
curl -I https://consciousconnections.online/storage/modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png
```

Expected: the first command succeeds if the upload still exists on the server, and the second returns HTTP `200`. If the first command fails, the file must be restored from the Hostinger backup or re-uploaded; cache commands cannot recreate it.

- [ ] **Step 3: Commit the deployment documentation**

```powershell
git add -- docs/FRESH_SERVER_SETUP.md
git commit -m "docs(deploy): repair Hostinger storage links"
```

### Task 4: Verify the cleanup

**Files:**
- Verify: `.gitignore`, `storage/app/.gitignore`, `docs/FRESH_SERVER_SETUP.md`, and Git index state.

- [ ] **Step 1: Verify repository invariants**

Run:

```powershell
git diff --check
git ls-files public/storage
git check-ignore -q --no-index public/storage/new-upload.png
git check-ignore -q storage/app/public/modules/new-upload.png
git status --short
```

Expected: `git diff --check` succeeds, `git ls-files public/storage` prints nothing, both `git check-ignore -q` commands exit successfully, and the working tree is clean after the commits.

- [ ] **Step 2: Run the focused Laravel regression tests**

Run:

```powershell
php artisan test --filter="AdminContentReviewWorkspaceDataTest|AdminAllModulesOwnershipCardUiTest"
```

Expected: both existing thumbnail/ownership UI test groups pass with zero failures. These tests confirm that the Laravel URL/accessor behavior used by the affected module cards remains intact.

- [ ] **Step 3: Confirm final state before handoff**

Run:

```powershell
git status --short
git log -3 --oneline
```

Expected: no uncommitted changes and the latest commits are the storage ignore cleanup, duplicate-tree removal, and deployment documentation changes.
