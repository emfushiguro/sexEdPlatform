<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\VideoEmbedHelper;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TopicController extends Controller
{
    public function create(Request $request)
    {
        $lesson = Lesson::findOrFail($request->lesson);
        $quizzes = Quiz::select('id', 'title')->get();
        
        return view('admin.topics.create', compact('lesson', 'quizzes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,text,worksheet,interactive',
            'duration' => 'required|integer|min:1',
            
            // Video fields
            'video_source' => 'nullable|required_if:type,video|in:url,upload',
            'video_url' => 'nullable|required_if:video_source,url|string',
            'video_file' => 'nullable|required_if:video_source,upload|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm|max:102400',
            'video_description' => 'nullable|string',
            
            // Text fields
            'text_content' => 'nullable|required_if:type,text|string',
            'image_attachments.*' => 'nullable|image|max:2048',
            'image_captions.*' => 'nullable|string',
            'image_display_mode' => 'nullable|in:none,gallery,slideshow',
            
            // Worksheet fields
            'worksheet_file' => 'nullable|required_if:type,worksheet|file|mimes:pdf,doc,docx|max:10240',
            'worksheet_instructions' => 'nullable|string',
            
            // Interactive fields
            'interactive_type' => 'nullable|required_if:type,interactive|in:activity,simulation,exercise',
            'interactive_instructions' => 'nullable|string',
        ]);

        $lesson = Lesson::findOrFail($validated['lesson_id']);

        // Handle video
        if ($validated['type'] === 'video') {
            if ($request->hasFile('video_file')) {
                $validated['video_file_path'] = $request->file('video_file')->store('videos', 'public');
                $validated['video_provider'] = 'local';
                $validated['video_id'] = null;
            } elseif (!empty($validated['video_url'])) {
                $videoData = VideoEmbedHelper::parseVideoUrl($validated['video_url']);
                $validated['video_provider'] = $videoData['provider'];
                $validated['video_id'] = $videoData['video_id'];
                $validated['video_file_path'] = null;
            }
            // Store video description in text_content
            $validated['text_content'] = $request->input('video_description');
        }

        // Handle image attachments for text topics
        if ($request->hasFile('image_attachments')) {
            $imagePaths = [];
            $captions = $request->input('image_captions', []);
            
            foreach ($request->file('image_attachments') as $index => $image) {
                $path = $image->store('lesson-images', 'public');
                $imagePaths[] = [
                    'path' => $path,
                    'caption' => $captions[$index] ?? null,
                    'original_name' => $image->getClientOriginalName(),
                ];
            }
            $validated['image_attachments'] = $imagePaths;
            
            // Handle slideshow configuration
            $displayMode = $request->input('image_display_mode', 'none');
            if ($displayMode === 'slideshow') {
                $validated['slideshow_data'] = [
                    'enabled' => true,
                    'mode' => 'slideshow',
                    'transition' => 'slide',
                    'auto_play' => false,
                    'show_thumbnails' => true,
                ];
            } elseif ($displayMode === 'gallery') {
                $validated['slideshow_data'] = [
                    'enabled' => false,
                    'mode' => 'gallery',
                ];
            }
        }

        // Handle worksheet file upload
        if ($request->hasFile('worksheet_file')) {
            $validated['file_path'] = $request->file('worksheet_file')->store('worksheets', 'public');
            $validated['text_content'] = $request->input('worksheet_instructions');
        }

        // Handle interactive configuration
        if ($validated['type'] === 'interactive') {
            $validated['interactive_config'] = [
                'type' => $request->input('interactive_type'),
                'instructions' => $request->input('interactive_instructions'),
            ];
        }

        // Auto-increment order
        $validated['order'] = $lesson->topics()->max('order') + 1;
        
        // All topics are required/prerequisite
        $validated['is_prerequisite'] = true;

        // Clean up temporary fields that shouldn't be stored in database
        $temporaryFields = ['video_source', 'video_url', 'video_file', 'video_description', 'image_captions', 'image_display_mode', 'worksheet_instructions', 'interactive_type', 'interactive_instructions'];
        foreach ($temporaryFields as $field) {
            unset($validated[$field]);
        }

        $topic = $lesson->topics()->create($validated);

        // Update lesson duration (sum of all topics)
        $lesson->duration = $lesson->topics()->sum('duration');
        $lesson->save();

        // Update module duration (sum of all lessons)
        $module = $lesson->module;
        $module->duration_minutes = $module->lessons()->sum('duration');
        $module->save();

        return redirect()->route('admin.lessons.show', $lesson)
            ->with('success', 'Topic created successfully!');
    }

    public function edit(LessonTopic $topic)
    {
        $topic->load('lesson');
        $quizzes = Quiz::select('id', 'title')->get();
        
        return view('admin.topics.edit', compact('topic', 'quizzes'));
    }

    public function update(Request $request, LessonTopic $topic)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,text,worksheet,quiz,interactive',
            'duration' => 'required|integer|min:1',
            
            // Video fields
            'video_source' => 'nullable|required_if:type,video|in:url,upload',
            'video_url' => 'nullable|string',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm|max:102400',
            
            // Text fields
            'text_content' => 'nullable|string',
            'image_attachments.*' => 'nullable|image|max:2048',
            'image_captions.*' => 'nullable|string',
            'image_display_mode' => 'nullable|in:none,gallery,slideshow',
            'slideshow_transition' => 'nullable|in:fade,slide,zoom',
            
            // Worksheet fields
            'worksheet_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'worksheet_instructions' => 'nullable|string',
            
            // Quiz fields
            'quiz_id' => 'nullable|exists:quizzes,id',
            
            // Interactive fields
            'interactive_type' => 'nullable|in:activity,simulation,exercise',
            'interactive_instructions' => 'nullable|string',
        ]);

        // Handle video
        if ($validated['type'] === 'video') {
            if ($request->hasFile('video_file')) {
                // Delete old video file if exists
                if ($topic->video_file_path) {
                    Storage::disk('public')->delete($topic->video_file_path);
                }
                $validated['video_file_path'] = $request->file('video_file')->store('videos', 'public');
                $validated['video_provider'] = 'local';
                $validated['video_id'] = null;
            } elseif (!empty($validated['video_url'])) {
                $videoData = VideoEmbedHelper::parseVideoUrl($validated['video_url']);
                $validated['video_provider'] = $videoData['provider'];
                $validated['video_id'] = $videoData['video_id'];
                if ($topic->video_file_path) {
                    Storage::disk('public')->delete($topic->video_file_path);
                }
                $validated['video_file_path'] = null;
            }
        }

        // Handle image attachments for text topics
        if ($request->hasFile('image_attachments')) {
            // Delete old images
            if ($topic->image_attachments) {
                foreach ($topic->image_attachments as $oldImage) {
                    if (isset($oldImage['path'])) {
                        Storage::disk('public')->delete($oldImage['path']);
                    }
                }
            }
            
            $imagePaths = [];
            $captions = $request->input('image_captions', []);
            
            foreach ($request->file('image_attachments') as $index => $image) {
                $path = $image->store('lesson-images', 'public');
                $imagePaths[] = [
                    'path' => $path,
                    'caption' => $captions[$index] ?? null,
                    'original_name' => $image->getClientOriginalName(),
                ];
            }
            $validated['image_attachments'] = $imagePaths;
            
            // Handle slideshow configuration
            $displayMode = $request->input('image_display_mode', 'none');
            if ($displayMode === 'slideshow') {
                $validated['slideshow_data'] = [
                    'enabled' => true,
                    'mode' => 'slideshow',
                    'transition' => $request->input('slideshow_transition', 'fade'),
                    'auto_play' => false,
                    'show_thumbnails' => true,
                ];
            } elseif ($displayMode === 'gallery') {
                $validated['slideshow_data'] = [
                    'enabled' => false,
                    'mode' => 'gallery',
                ];
            }
        }

        // Handle worksheet file upload
        if ($request->hasFile('worksheet_file')) {
            // Delete old file
            if ($topic->file_path) {
                Storage::disk('public')->delete($topic->file_path);
            }
            $validated['file_path'] = $request->file('worksheet_file')->store('worksheets', 'public');
            $validated['text_content'] = $request->input('worksheet_instructions');
        }

        // Handle quiz type - quiz_id is already in validated array, no additional processing needed

        // Handle interactive configuration
        if ($validated['type'] === 'interactive') {
            $validated['interactive_config'] = [
                'type' => $request->input('interactive_type'),
                'instructions' => $request->input('interactive_instructions'),
            ];
        }

        // All topics are required/prerequisite
        $validated['is_prerequisite'] = true;

        // Clean up temporary fields that shouldn't be stored in database
        $temporaryFields = ['video_source', 'video_url', 'video_file', 'image_captions', 'image_display_mode', 'slideshow_transition', 'worksheet_instructions', 'interactive_type', 'interactive_instructions'];
        foreach ($temporaryFields as $field) {
            unset($validated[$field]);
        }

        $topic->update($validated);

        // Update lesson duration
        $lesson = $topic->lesson;
        $lesson->duration = $lesson->topics()->sum('duration');
        $lesson->save();

        // Update module duration
        $module = $lesson->module;
        $module->duration_minutes = $module->lessons()->sum('duration');
        $module->save();

        return redirect()->route('admin.lessons.show', $topic->lesson)
            ->with('success', 'Topic updated successfully!');
    }

    public function destroy(LessonTopic $topic)
    {
        $lesson = $topic->lesson;

        // Delete associated files
        if ($topic->video_file_path) {
            Storage::disk('public')->delete($topic->video_file_path);
        }
        if ($topic->file_path) {
            Storage::disk('public')->delete($topic->file_path);
        }
        if ($topic->image_attachments) {
            foreach ($topic->image_attachments as $image) {
                if (isset($image['path'])) {
                    Storage::disk('public')->delete($image['path']);
                }
            }
        }

        $topic->delete();

        // Update lesson duration
        $lesson->duration = $lesson->topics()->sum('duration');
        $lesson->save();

        // Update module duration
        $module = $lesson->module;
        $module->duration_minutes = $module->lessons()->sum('duration');
        $module->save();

        return redirect()->route('admin.lessons.show', $lesson)
            ->with('success', 'Topic deleted successfully!');
    }

    /**
     * Handle image uploads from TinyMCE editor
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:2048'
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('tinymce-images', 'public');
            $url = asset('storage/' . $path);

            return response()->json([
                'location' => $url
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
