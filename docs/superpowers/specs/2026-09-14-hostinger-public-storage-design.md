# Hostinger Public Storage Deployment Cleanup

## Problem

Laravel stores files uploaded through the `public` disk in
`storage/app/public`, and generates URLs under `/storage/...`. The repository
also tracks a second, ordinary directory at `public/storage`. On a Hostinger
Git deployment, that ordinary directory prevents `php artisan storage:link`
from creating the symbolic link Laravel requires. Newly uploaded files then
exist in `storage/app/public` but are requested from the unrelated
`public/storage` tree and return `404`.

The reported file
`modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png` is not in Git or this
checkout, which is expected for a production upload and confirms that runtime
media must not depend on Git commits.

## Design

Use `storage/app/public` as the only canonical public-media location.

- Keep `public/storage` ignored and remove its tracked duplicate files from
  the repository.
- Keep existing seeded files under `storage/app/public` available to the
  application; new runtime uploads are not added to Git.
- During the first production cleanup, copy any files still present in a real
  `public/storage` directory into `storage/app/public`, move the old directory
  to a timestamped backup, and then create the Laravel storage link.
- On every subsequent deployment, ensure any stale non-link at
  `public/storage` is moved aside before running `php artisan storage:link`.
- Run cache clearing before rebuilding Laravel caches so production config and
  view output reflect the deployed code and environment.

## Deployment data flow

```text
upload -> storage/app/public/<path>
       -> public/storage/<path> (symlink)
       -> https://consciousconnections.online/storage/<path>
```

The HTTP layer should serve the symlinked `public/storage` path directly. No
new controller or database migration is needed.

## Error handling and safety

- Do not run `migrate:fresh`, `db:wipe`, `TRUNCATE`, `DROP`, or unscoped
  deletes.
- The first cleanup uses `cp` plus `mv`, not deletion, so the old deployed
  media remains recoverable while the link is repaired.
- Verify both `public/storage` being a symlink and one known file existing in
  `storage/app/public` before declaring deployment successful.
- If symlink creation is not permitted by the Hostinger account, stop and use
  a supported persistent filesystem such as S3/R2 rather than adding an
  application image proxy.

## Verification

Repository checks:

- `public/storage` has no tracked files.
- `storage/app/public` remains the configured `public` disk root.
- Existing Laravel storage URL behavior remains unchanged.

Production checks:

- `test -L public/storage` succeeds.
- `test -f storage/app/public/modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png`
  succeeds for the reported upload.
- `curl -I
  https://consciousconnections.online/storage/modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png`
  returns `200` after the file is restored or re-uploaded.
