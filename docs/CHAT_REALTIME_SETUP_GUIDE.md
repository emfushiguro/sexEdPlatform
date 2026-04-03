# Chat Realtime Setup Guide

This runbook explains how to configure and operate realtime chat (Laravel Reverb + Echo) for local development and server deployment.

## 1. Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL

## 2. Environment Variables

Add these variables to your `.env` (or copy from `.env.example`):

```dotenv
BROADCAST_CONNECTION=reverb

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_SERVER_PATH=

REVERB_APP_ID=local-app
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret

REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Notes:

- `REVERB_SERVER_*` controls where `php artisan reverb:start` binds (server process).
- `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` control how Laravel and Echo connect to Reverb.
- For local dev, it is normal for both groups to point to `localhost:8080` using `http`.
- After changing env values, clear cached config before restarting services.

```bash
php artisan config:clear
php artisan cache:clear
```

## 3. Install Dependencies

```bash
composer install
npm install
```

## 4. Start the Application Stack

Run these in separate terminals:

```bash
php artisan serve
```

```bash
php artisan reverb:start
```

Optional explicit bind (equivalent to matching `REVERB_SERVER_HOST/PORT`):

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

```bash
npm run dev
```

If your environment relies on queued notifications/jobs, also run:

```bash
php artisan queue:work
```

## 5. Manual Verification Checklist

1. Open two browser sessions with different users who are allowed to chat.
2. Open the shared chat page at `/chat` in both sessions.
3. Send a message from session A and verify session B receives it without refresh.
4. Confirm unread badges update on header/layout badge hooks.
5. Enable browser alerts from the chat toggle and verify:
   - background/hidden thread messages trigger notification popup
   - active focused thread messages do not trigger popup
6. Simulate reconnect behavior:
   - stop/start Reverb
   - verify backfill endpoint restores missing messages after reconnect

## 6. Operational Checks

- Channel auth route: `POST /broadcasting/auth`
- Chat API endpoints are under `/chat/*`
- Private channels used by UI:
  - `private-chat.conversation.{conversationId}`
  - `private-chat.requests.user.{userId}`

## 7. Troubleshooting

- `403` on channel subscription:
  - Verify user is a conversation participant.
  - Verify role middleware allows user access to chat routes.
- Messages store but do not appear live:
  - Check `php artisan reverb:start` is running.
  - Check Vite client env vars (`VITE_REVERB_*`) are correct.
  - If you changed `.env`, run `php artisan config:clear` and restart Vite/Reverb.
  - Confirm browser console has no Echo connection errors.
- Browser notifications never appear:
  - Ensure user enabled browser alerts in chat UI.
  - Confirm browser permission is `granted`.

## 8. Test Commands

```bash
php artisan test tests/Feature/Chat
php artisan test --filter=ChatRolePermissionMatrixTest
php artisan test --filter=ChatRequestGateFlowTest
php artisan test --filter=ChatContextConversationFlowTest
php artisan test --filter=ChatNotificationBadgeTest
```
