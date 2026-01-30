# Automatic Module Publishing Setup

## For Production (Linux/cPanel Hosting)

Add this cron job to run every minute:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**How to add on cPanel:**
1. Go to **Advanced** → **Cron Jobs**
2. Select **Common Settings**: "Once Per Minute (* * * * *)"
3. Add command: `cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1`
4. Save

---

## For Local Development (Windows)

### Option 1: Keep Terminal Open (Recommended for Development)

Run this command and keep the terminal open:

```bash
php artisan schedule:work
```

This will run the scheduler every minute automatically while developing.

---

### Option 2: Windows Task Scheduler (Automatic Background)

**Step 1:** Create a batch file `schedule-run.bat`:

```batch
@echo off
cd C:\Users\Jaded\sexed-platform
php artisan schedule:run >> schedule.log 2>&1
```

**Step 2:** Open **Task Scheduler**:
1. Press `Win + R`, type `taskschd.msc`, press Enter
2. Click **Create Basic Task**
3. Name: "Laravel Scheduler"
4. Trigger: **Daily**
5. Start time: **12:00 AM**
6. Action: **Start a program**
7. Program: `C:\Users\Jaded\sexed-platform\schedule-run.bat`
8. Click **Finish**

**Step 3:** Edit the task for every minute:
1. Right-click the task → **Properties**
2. Go to **Triggers** tab → **Edit**
3. Check **Repeat task every:** `1 minute`
4. For a duration of: **Indefinitely**
5. Click **OK**

---

### Option 3: Simple PowerShell Script (Easiest for Development)

Create `auto-schedule.ps1`:

```powershell
# Auto-run Laravel scheduler every minute
while ($true) {
    Set-Location "C:\Users\Jaded\sexed-platform"
    php artisan schedule:run
    Start-Sleep -Seconds 60
}
```

Run it:
```powershell
powershell -ExecutionPolicy Bypass -File auto-schedule.ps1
```

Keep this PowerShell window open while developing.

---

## For Thesis Demo (Recommended)

**Best approach for your thesis defense:**

1. Before demo, manually publish modules using:
   ```bash
   php artisan modules:publish-scheduled
   ```

2. Or use **Option 1** (keep `php artisan schedule:work` running in background terminal)

3. This ensures modules publish exactly when scheduled during your presentation!

---

## How It Works

The scheduler automatically checks every minute if any modules have:
- `publish_status` = 'scheduled'
- `is_published` = false
- `publish_at` <= current time

When found, it updates:
- `is_published` = true
- `publish_status` = 'published'

**No manual intervention needed after setup!** ✅
