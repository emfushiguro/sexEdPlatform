<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageLibraryController extends Controller
{
    /**
     * Display the image library
     */
    public function index()
    {
        $images = [];
        $files = $this->listImageFiles();
        
        foreach ($files as $file) {
            $images[] = [
                'filename' => basename($file),
                'url' => asset('storage/' . $file),
                'size' => Storage::disk('public')->size($file),
                'uploaded' => Storage::disk('public')->lastModified($file),
            ];
        }
        
        // Sort by upload date (newest first)
        usort($images, function($a, $b) {
            return $b['uploaded'] - $a['uploaded'];
        });
        
        return view('instructor.image-library.index', compact('images'));
    }
    
    /**
     * Return image list as JSON (for inline gallery in modals)
     */
    public function indexJson()
    {
        $images = [];
        $files = $this->listImageFiles();

        foreach ($files as $file) {
            $images[] = [
                'filename' => basename($file),
                'url'      => asset('storage/' . $file),
                'size_kb'  => round(Storage::disk('public')->size($file) / 1024, 1),
            ];
        }

        usort($images, fn($a, $b) => strcmp($b['filename'], $a['filename']));

        return response()->json(['images' => $images]);
    }

    /**
     * Upload new image to library
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        try {
            $file = $request->file('image');
            $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $path = $file->storeAs($this->userImageDirectory(), $filename, 'public');

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'filename' => $filename,
                    'url'      => asset('storage/' . $path),
                    'size_kb'  => round(Storage::disk('public')->size($path) / 1024, 1),
                ]);
            }

            return back()->with('success', "Image '{$filename}' uploaded successfully!");
        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to upload image.'], 500);
            }

            return back()->with('error', 'Failed to upload image. Please try again.');
        }
    }
    
    /**
     * Delete image from library
     */
    public function delete($filename)
    {
        try {
            $path = $this->resolveDeletePath($filename);
            
            if (!$path || !Storage::disk('public')->exists($path)) {
                return back()->with('error', 'Image not found.');
            }
            
            Storage::disk('public')->delete($path);
            
            return back()->with('success', "Image '{$filename}' deleted successfully!");
        } catch (\Exception $e) {
            Log::error('Image delete failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete image.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function listImageFiles(): array
    {
        $disk = Storage::disk('public');
        $userDir = $this->userImageDirectory();

        return $disk->exists($userDir) ? $disk->files($userDir) : [];
    }

    private function resolveDeletePath(string $filename): ?string
    {
        $disk = Storage::disk('public');
        $safeFilename = basename($filename);

        $scopedPath = $this->userImageDirectory() . '/' . $safeFilename;
        if ($disk->exists($scopedPath)) {
            return $scopedPath;
        }

        return null;
    }

    private function userImageDirectory(): string
    {
        $userId = (int) Auth::id();

        return 'quiz-images/user-' . $userId;
    }
}
