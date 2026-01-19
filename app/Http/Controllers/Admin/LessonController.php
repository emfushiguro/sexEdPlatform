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
        
        // Handle video URL parsing
        if ($validated['content_type'] === 'video' && !empty($validated['video_url'])) {
            $videoData = VideoEmbedHelper::parseVideoUrl($validated['video_url']);
            $validated['video_provider'] = $videoData['provider'];
            $validated['video_id'] = $videoData['video_id'];
            unset($validated['video_url']); // Remove temporary field
        }
        
        // Handle worksheet file upload
        if ($request->hasFile('worksheet_file')) {
            $validated['file_path'] = $request->file('worksheet_file')->store('worksheets', 'public');
        }
        
        // Auto-increment order if not provided
        if (!isset($validated['order'])) {
            $validated['order'] = Lesson::where('module_id', $validated['module_id'])->max('order') + 1;
        }
        
        // Set published status
        $validated['is_published'] = $request->has('is_published');

        Lesson::create($validated);

        return redirect()->route('admin.modules.show', $validated['module_id'])
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
        
        // Handle video URL parsing
        if ($validated['content_type'] === 'video' && !empty($validated['video_url'])) {
            $videoData = VideoEmbedHelper::parseVideoUrl($validated['video_url']);
            $validated['video_provider'] = $videoData['provider'];
            $validated['video_id'] = $videoData['video_id'];
            unset($validated['video_url']);
        }
        
        // Handle worksheet file upload
        if ($request->hasFile('worksheet_file')) {
            // Delete old file if exists
            if ($lesson->file_path) {
                Storage::disk('public')->delete($lesson->file_path);
            }
            $validated['file_path'] = $request->file('worksheet_file')->store('worksheets', 'public');
        }
        
        // Set published status
        $validated['is_published'] = $request->has('is_published');

        $lesson->update($validated);

        return redirect()->route('admin.modules.show', $lesson->module_id)
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
