<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\VideoEmbedHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $query = Lesson::with('module');

        if ($request->filled('module_id')) {
            $query->where('module_id', $request->module_id);
        }

        $lessons = $query->latest()->paginate(15);
        $modules = Module::all();

        return view('admin.lessons.index', compact('lessons', 'modules'));
    }

    public function create()
    {
        $modules = Module::all();
        return view('admin.lessons.create', compact('modules'));
    }

    public function store(LessonRequest $request)
    {
        $validated = $request->validated();
        
        // Handle video - both URL and file upload
        if ($validated['content_type'] === 'video') {
            if ($request->hasFile('video_file')) {
                // Store uploaded video file
                $validated['video_file_path'] = $request->file('video_file')->store('videos', 'public');
                $validated['video_provider'] = 'local';
            } elseif (!empty($validated['video_url'])) {
                // Parse external video URL
                $videoData = VideoEmbedHelper::parseVideoUrl($validated['video_url']);
                $validated['video_provider'] = $videoData['provider'];
                $validated['video_id'] = $videoData['video_id'];
            }
            unset($validated['video_url']);
        }
        
        // Handle image attachments for text lessons
        if ($request->hasFile('image_attachments')) {
            $imagePaths = [];
            foreach ($request->file('image_attachments') as $image) {
                $imagePaths[] = $image->store('lesson-images', 'public');
            }
            $validated['image_attachments'] = $imagePaths;
        }
        
        // Handle worksheet file upload
        if ($request->hasFile('worksheet_file')) {
            $validated['file_path'] = $request->file('worksheet_file')->store('worksheets', 'public');
        }
        
        // Handle interactive configuration
        if ($validated['content_type'] === 'interactive' && $request->filled('interactive_type')) {
            $validated['interactive_config'] = [
                'type' => $request->input('interactive_type'),
                'settings' => $request->input('interactive_config', []),
            ];
        }
        
        // Auto-increment order if not provided
        if (!isset($validated['order'])) {
            $validated['order'] = Lesson::where('module_id', $validated['module_id'])->max('order') + 1;
        }
        
        // Set published status
        $validated['is_published'] = $request->has('is_published');

        Lesson::create($validated);

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson created successfully!');
    }

    public function show(Lesson $lesson)
    {
        $lesson->load('module');
        return view('admin.lessons.show', compact('lesson'));
    }

    public function edit(Lesson $lesson)
    {
        $modules = Module::all();
        return view('admin.lessons.edit', compact('lesson', 'modules'));
    }

    public function update(LessonRequest $request, Lesson $lesson)
    {
        $validated = $request->validated();
        
        // Handle video - both URL and file upload
        if ($validated['content_type'] === 'video') {
            if ($request->hasFile('video_file')) {
                // Delete old video file if exists
                if ($lesson->video_file_path) {
                    Storage::disk('public')->delete($lesson->video_file_path);
                }
                $validated['video_file_path'] = $request->file('video_file')->store('videos', 'public');
                $validated['video_provider'] = 'local';
                // Clear external video data
                $validated['video_id'] = null;
            } elseif (!empty($validated['video_url'])) {
                // Parse external video URL
                $videoData = VideoEmbedHelper::parseVideoUrl($validated['video_url']);
                $validated['video_provider'] = $videoData['provider'];
                $validated['video_id'] = $videoData['video_id'];
                // Clear local video file
                $validated['video_file_path'] = null;
            }
            unset($validated['video_url']);
        }
        
        // Handle image attachments
        if ($request->hasFile('image_attachments')) {
            // Delete old images
            if ($lesson->image_attachments) {
                foreach ($lesson->image_attachments as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            $imagePaths = [];
            foreach ($request->file('image_attachments') as $image) {
                $imagePaths[] = $image->store('lesson-images', 'public');
            }
            $validated['image_attachments'] = $imagePaths;
        }
        
        // Handle worksheet file upload
        if ($request->hasFile('worksheet_file')) {
            // Delete old file if exists
            if ($lesson->file_path) {
                Storage::disk('public')->delete($lesson->file_path);
            }
            $validated['file_path'] = $request->file('worksheet_file')->store('worksheets', 'public');
        }
        
        // Handle interactive configuration
        if ($validated['content_type'] === 'interactive' && $request->filled('interactive_type')) {
            $validated['interactive_config'] = [
                'type' => $request->input('interactive_type'),
                'settings' => $request->input('interactive_config', []),
            ];
        }
        
        // Set published status
        $validated['is_published'] = $request->has('is_published');

        $lesson->update($validated);

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson updated successfully!');
    }

    public function destroy(Lesson $lesson)
    {
        $moduleId = $lesson->module_id;
        $lesson->delete();

        return redirect()->route('admin.modules.show', $moduleId)
            ->with('success', 'Lesson deleted successfully!');
    }

    public function move(Request $request, Lesson $lesson)
    {
        $direction = $request->input('direction');
        $currentOrder = $lesson->order;
        
        if ($direction === 'up' && $currentOrder > 1) {
            $swapLesson = Lesson::where('module_id', $lesson->module_id)
                ->where('order', $currentOrder - 1)
                ->first();
            
            if ($swapLesson) {
                $lesson->update(['order' => $currentOrder - 1]);
                $swapLesson->update(['order' => $currentOrder]);
            }
        } elseif ($direction === 'down') {
            $maxOrder = Lesson::where('module_id', $lesson->module_id)->max('order');
            
            if ($currentOrder < $maxOrder) {
                $swapLesson = Lesson::where('module_id', $lesson->module_id)
                    ->where('order', $currentOrder + 1)
                    ->first();
                
                if ($swapLesson) {
                    $lesson->update(['order' => $currentOrder + 1]);
                    $swapLesson->update(['order' => $currentOrder]);
                }
            }
        }

        return redirect()->back()->with('success', 'Lesson order updated!');
    }
}
