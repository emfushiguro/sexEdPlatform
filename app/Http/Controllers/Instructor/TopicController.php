<?php

namespace App\Http\Controllers\Instructor;

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
        
        return view('instructor.topics.create', compact('lesson', 'quizzes'));
    }

    public function store(Request $request)
    {
        // Log the request for debugging
        \Log::info('Topic creation request START', [
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'has_images' => $request->hasFile('image_attachments'),
            'image_count' => $request->hasFile('image_attachments') ? count($request->file('image_attachments')) : 0,
            'has_text' => !empty($request->input('text_content')),
            'is_prerequisite_in_request' => $request->has('is_prerequisite'),
            'is_prerequisite_value' => $request->input('is_prerequisite'),
            'all_inputs' => $request->except(['_token', 'text_content']),
        ]);

        try {
            $validated = $request->validate([
                'lesson_id' => 'required|exists:lessons,id',
                'title' => 'required|string|max:255',
                'type' => 'required|in:video,text,worksheet,interactive',
                'duration' => 'required|integer|min:1',
                'is_prerequisite' => 'nullable|boolean',
                
                // Video fields
                'video_source' => 'nullable|required_if:type,video|in:url,upload',
                'video_url' => 'nullable|required_if:video_source,url|string',
                'video_file' => 'nullable|required_if:video_source,upload|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm|max:102400',
                'video_description' => 'nullable|string',
                
                // Text fields (text_content is optional if images are provided)
                'text_content' => 'nullable|string',
                'image_attachments.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
                'excluded_image_indices' => 'nullable|array',
                'excluded_image_indices.*' => 'integer',
                
                // Worksheet fields
                'worksheet_files.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
                'worksheet_instructions' => 'nullable|string',
                
                // Interactive fields
                'interactive_type' => 'nullable|required_if:type,interactive|in:activity,simulation,exercise',
                'interactive_instructions' => 'nullable|string',
            ]);

            \Log::info('Validation passed', ['validated' => array_keys($validated)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['_token', 'text_content'])
            ]);
            
            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            
            throw $e;
        }

        // Validate that text topics have either text_content or image_attachments
        if ($validated['type'] === 'text') {
            if (empty($request->input('text_content')) && !$request->hasFile('image_attachments')) {
                \Log::warning('Text topic validation failed: no content or images');
                
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['text_content' => ['Please provide either text content or image attachments.']]
                    ], 422);
                }
                
                return back()->withErrors(['text_content' => 'Please provide either text content or image attachments.'])->withInput();
            }
        }

        // Validate that worksheet topics have at least one file
        if ($validated['type'] === 'worksheet') {
            if (!$request->hasFile('worksheet_files')) {
                \Log::warning('Worksheet topic validation failed: no files');
                return back()->withErrors(['worksheet_files' => 'Please upload at least one worksheet file.'])->withInput();
            }
        }

        $lesson = Lesson::findOrFail($validated['lesson_id']);
        
        \Log::info('Processing topic creation', ['lesson_id' => $lesson->id, 'type' => $validated['type']]);

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
            $excludedIndices = $request->input('excluded_image_indices', []);
            $imageFiles = $request->file('image_attachments');
            
            \Log::info('Processing image attachments', [
                'total_count' => count($imageFiles),
                'excluded_count' => count($excludedIndices),
                'excluded_indices' => $excludedIndices
            ]);
            
            $imagePaths = [];
            $captions = $request->all(); // Get all request data for caption fields
            
            foreach ($imageFiles as $index => $image) {
                // Skip excluded images
                if (in_array($index, $excludedIndices)) {
                    \Log::info('Skipping excluded image', ['index' => $index]);
                    continue;
                }
                
                try {
                    $path = $image->store('lesson-images', 'public');
                    
                    // Look for caption with this index
                    $captionKey = 'image_captions_' . $index;
                    $caption = isset($captions[$captionKey]) ? $captions[$captionKey] : null;
                    
                    $imagePaths[] = [
                        'path' => $path,
                        'caption' => $caption,
                        'original_name' => $image->getClientOriginalName(),
                    ];
                    
                    \Log::info('Image stored', ['index' => $index, 'path' => $path, 'caption' => $caption]);
                } catch (\Exception $e) {
                    \Log::error('Failed to store image', [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }
            
            $validated['image_attachments'] = $imagePaths;
            
            \Log::info('All images processed', ['stored_count' => count($imagePaths)]);
            
            // Store data for both gallery and slideshow display (learner can toggle)
            $validated['slideshow_data'] = [
                'enabled' => true,
                'gallery_mode' => 'grid',
                'slideshow_mode' => 'slide',
                'auto_play' => false,
                'show_thumbnails' => true,
                'allow_toggle' => true, // Learner can switch between gallery/slideshow
            ];
        }

        // Handle worksheet file uploads (multiple files)
        if ($request->hasFile('worksheet_files')) {
            $worksheetPaths = [];
            
            foreach ($request->file('worksheet_files') as $index => $file) {
                $path = $file->store('worksheets', 'public');
                $worksheetPaths[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
            $validated['worksheet_files'] = $worksheetPaths;
            // Store first file path in file_path for backward compatibility
            $validated['file_path'] = $worksheetPaths[0]['path'] ?? null;
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
        
        // Set prerequisite status - checkbox only sends value when checked
        // When unchecked, the field is not present in the request
        $validated['is_prerequisite'] = $request->has('is_prerequisite');
        
        \Log::info('Final prerequisite value', [
            'is_prerequisite' => $validated['is_prerequisite'],
            'has_in_request' => $request->has('is_prerequisite'),
            'input_value' => $request->input('is_prerequisite')
        ]);

        // Clean up temporary fields that shouldn't be stored in database
        $temporaryFields = ['video_source', 'video_url', 'video_file', 'video_description', 'image_captions', 'worksheet_instructions', 'interactive_type', 'interactive_instructions'];
        foreach ($temporaryFields as $field) {
            unset($validated[$field]);
        }

        \Log::info('Creating topic', ['data_keys' => array_keys($validated)]);

        try {
            $topic = $lesson->topics()->create($validated);
            \Log::info('Topic created successfully', ['topic_id' => $topic->id]);
        } catch (\Exception $e) {
            \Log::error('Failed to create topic', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['error' => ['Failed to create topic: ' . $e->getMessage()]]
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Failed to create topic: ' . $e->getMessage()])->withInput();
        }

        // Update lesson duration (sum of all topics)
        $lesson->duration = $lesson->topics()->sum('duration');
        $lesson->save();

        // Update module duration (sum of all lessons)
        $module = $lesson->module;
        $module->duration_minutes = $module->lessons()->sum('duration');
        $module->save();

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Topic created successfully!',
                'redirect' => route('instructor.lessons.show', $lesson)
            ]);
        }

        return redirect()->route('instructor.lessons.show', $lesson)
            ->with('success', 'Topic created successfully!');
    }

    public function edit(LessonTopic $topic)
    {
        $topic->load('lesson');
        $quizzes = Quiz::select('id', 'title')->get();
        
        return view('instructor.topics.edit', compact('topic', 'quizzes'));
    }

    public function update(Request $request, LessonTopic $topic)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,text,worksheet,quiz,interactive',
            'duration' => 'required|integer|min:1',
            'is_prerequisite' => 'nullable|boolean',
            
            // Video fields
            'video_source' => 'nullable|required_if:type,video|in:url,upload',
            'video_url' => 'nullable|string',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm|max:102400',
            
            // Text fields
            'text_content' => 'nullable|string',
            'image_attachments.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg|max:2048',
            'image_captions.*' => 'nullable|string',
            
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
            
            // Store data for both gallery and slideshow display (learner can toggle)
            $validated['slideshow_data'] = [
                'enabled' => true,
                'gallery_mode' => 'grid',
                'slideshow_mode' => 'slide',
                'auto_play' => false,
                'show_thumbnails' => true,
                'allow_toggle' => true,
            ];
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

        // Set prerequisite status based on checkbox presence
        $validated['is_prerequisite'] = $request->has('is_prerequisite');
        
        \Log::info('UPDATE - Final prerequisite value', [
            'is_prerequisite' => $validated['is_prerequisite'],
            'has_in_request' => $request->has('is_prerequisite'),
            'input_value' => $request->input('is_prerequisite'),
            'previous_value' => $topic->is_prerequisite
        ]);

        // Clean up temporary fields that shouldn't be stored in database
        $temporaryFields = ['video_source', 'video_url', 'video_file', 'image_captions', 'worksheet_instructions', 'interactive_type', 'interactive_instructions'];
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

        return redirect()->route('instructor.lessons.show', $topic->lesson)
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

        return redirect()->route('instructor.lessons.show', $lesson)
            ->with('success', 'Topic deleted successfully!');
    }

    /**
     * Get topic preview data
     */
    public function preview(LessonTopic $topic)
    {
        // Ensure the topic belongs to a lesson the instructor owns
        // You might want to add authorization here
        
        return response()->json([
            'id' => $topic->id,
            'title' => $topic->title,
            'type' => $topic->type,
            'duration' => $topic->duration,
            'is_prerequisite' => $topic->is_prerequisite,
            'video_url' => $topic->video_url,
            'video_file_path' => $topic->video_file_path,
            'video_file_url' => $topic->video_file_url,
            'video_description' => $topic->video_description,
            'text_content' => $topic->text_content,
            'image_attachments' => $topic->image_attachments,
            'worksheet_file_path' => $topic->file_path,
            'worksheet_file_url' => $topic->file_path ? Storage::url($topic->file_path) : null,
            'worksheet_instructions' => $topic->worksheet_instructions,
            'interactive_type' => $topic->interactive_config['type'] ?? null,
            'interactive_instructions' => $topic->interactive_instructions,
        ]);
    }

    /**
     * Handle image uploads from TinyMCE editor
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:5120'
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

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:lesson_topics,id']);

        foreach ($request->order as $index => $id) {
            LessonTopic::where('id', $id)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
