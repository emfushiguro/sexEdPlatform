# Mobile QR + APK Download Flow (Landing)

Status: DONE
Date: 2026-04-20
Owner: Landing page

## 1) Problem
- The landing page mobile section needs a working QR flow for APK download.
- APK currently lives in Google Drive.
- Current route references a missing view: Route::view('/download', 'landing.download') in routes/web.php.

## 2) Goal
- Add a QR code in the landing mobile section that works.
- Scanning QR should open a stable app-owned URL, then redirect to APK download.
- Keep future updates easy (replace APK link without changing QR image).

## 3) Chosen Approach (B - Recommended)
- QR target: app route, not raw Google Drive link.
- New redirect endpoint in Laravel: GET /download/apk
- Endpoint reads env var APK_DOWNLOAD_URL and redirects (302/307) to Google Drive direct download link.
- Landing section shows:
  - Download button (same endpoint)
  - QR image pointing to /download/apk
  - Small helper text: Scan to download APK

Why this is best now:
- Stable QR forever (route stays same, link behind it can change).
- No need to regenerate QR when APK file/link changes.
- Works with current Google Drive hosting.

## 4) Alternatives (Yin/Yang)

### A) Direct Google Drive link in QR
Benefits:
- Fastest setup.
Costs:
- If Drive link changes, QR breaks.
- Less control/tracking.

### C) Host APK inside app storage
Benefits:
- Full control, no external dependency.
- Better long-term governance.
Costs:
- Needs server storage + proper file serving + release discipline.

## 5) Implementation Plan
1. Add env variable APK_DOWNLOAD_URL in .env.
2. Add route in routes/web.php:
   - GET /download/apk -> closure/controller redirect to env('APK_DOWNLOAD_URL').
3. Keep /download route optional:
   - Either create landing.download view, or remove/replace the current route if unused.
4. Add QR block in resources/views/landing/index.blade.php mobile section:
   - QR image (generated from /download/apk)
   - Caption text and fallback download link.
5. Ensure mobile responsiveness:
   - QR block stacks below copy on small screens.

## 6) Security + Reliability Notes
- Use allow-list validation so APK_DOWNLOAD_URL must be Google Drive (or approved domains).
- If APK_DOWNLOAD_URL is missing, return friendly fallback page/message.
- Prefer Google Drive direct-download format for fewer user clicks.

## 7) Test Checklist
- QR scan from real Android opens /download/apk then starts download.
- Download button opens same endpoint and works.
- Missing APK_DOWNLOAD_URL shows graceful fallback.
- Layout is usable on 320px width and desktop.

## 8) Decision
- Keep /download and provide a user-friendly page with QR + download button.
