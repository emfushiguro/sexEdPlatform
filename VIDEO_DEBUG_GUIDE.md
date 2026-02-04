# Video Display Debugging Guide

## Quick Debug Steps

### 1. Check Database Value
```sql
SELECT id, title, type, video_file_path, video_provider, video_id 
FROM lesson_topics 
WHERE type = 'video';
```

**Expected for uploaded video**:
- `video_file_path`: `videos/xyz123.mp4`
- `video_provider`: NULL or empty
- `video_id`: NULL or empty

**Expected for YouTube**:
- `video_file_path`: NULL
- `video_provider`: `youtube`
- `video_id`: `dQw4w9WgXcQ`

---

### 2. Check File Exists
```bash
# Windows PowerShell
Test-Path "c:\Users\Jaded\sexEdPlatform\storage\sexEdPlatform\storage\app\public\videos\[filename].mp4"

# Should return: True
```

---

### 3. Check Storage Link
```bash
php artisan storage:link
```

Should see: `The [public/storage] link has been connected to [storage/app/public].`

---

### 4. Check Generated URL
Enable debug mode (already in code) and look for the debug panel on the video page.

Should show something like:
```
http://localhost:8000/storage/videos/filename.mp4
```

Visit that URL directly to test if file is accessible.

---

### 5. Browser Console Check
Open browser DevTools (F12) → Console

Look for errors like:
- `404 Not Found` - File doesn't exist or path wrong
- `403 Forbidden` - Permission issue
- `MIME type` error - File type issue

---

### 6. Video File MIME Type
Ensure server serves correct MIME types:

In `public/.htaccess` (for Apache):
```apache
AddType video/mp4 .mp4
AddType video/webm .webm
AddType video/ogg .ogg
```

---

### 7. Test Video File
Try playing the video directly:
```bash
# Copy to public folder for testing
Copy-Item "storage\app\public\videos\[filename].mp4" "public\test-video.mp4"
```

Then visit: `http://localhost:8000/test-video.mp4`

If this works, the file is fine - it's a path issue.

---

### 8. Common Issues & Solutions

#### Issue: 404 Not Found
**Solution**: Run `php artisan storage:link`

#### Issue: Video shows black screen
**Solution**: File format not supported. Convert to H.264 MP4:
```bash
ffmpeg -i input.mp4 -vcodec h264 -acodec aac output.mp4
```

#### Issue: "The media could not be loaded"
**Solution**: Check file size. Large files might timeout. Consider compression.

#### Issue: Path shows `file_path` instead of `video_file_path`
**Solution**: Update view to use `$currentTopic->video_file_path`

---

### 9. Check Admin Upload
Go to admin topic creation and verify:
1. File input shows selected file name
2. Form submits successfully
3. Database column `video_file_path` is populated
4. File exists in `storage/app/public/videos/`

---

### 10. Quick Fix Test
Create a test topic with a YouTube video first:
1. Create topic with type "Video"
2. Set provider: YouTube
3. Set video ID: `dQw4w9WgXcQ` (Rick Roll)
4. View on learner side

If YouTube works but uploaded videos don't, it's definitely a file storage issue.

---

## Debug Output in View

The current code includes debug output when `APP_DEBUG=true`:

```blade
@if(config('app.debug'))
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
        <p class="text-xs text-yellow-800 font-mono">
            Debug: {{ asset('storage/' . $currentTopic->video_file_path) }}
        </p>
    </div>
@endif
```

This will show you the exact URL being generated.

---

## Expected File Structure

```
sexEdPlatform/
├── public/
│   └── storage/ → (symlink to ../storage/app/public)
│       └── videos/
│           └── abc123xyz.mp4
└── storage/
    └── app/
        └── public/
            └── videos/
                └── abc123xyz.mp4
```

---

## Test with Sample Video

Add this directly in database for testing:

```sql
UPDATE lesson_topics 
SET video_file_path = 'videos/sample.mp4' 
WHERE id = 1;
```

Then create `storage/app/public/videos/sample.mp4` with any MP4 file.

---

## Laravel Storage Helper

The code uses:
```php
asset('storage/' . $currentTopic->video_file_path)
```

Which generates:
```
http://localhost:8000/storage/videos/filename.mp4
```

This relies on the symlink from `public/storage` to `storage/app/public`.

---

## Alternative: Direct Storage URL

If symlink doesn't work, you can use:
```php
Storage::url($currentTopic->video_file_path)
```

But you need to set in `.env`:
```env
FILESYSTEM_DISK=public
```

And in `config/filesystems.php`:
```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```
