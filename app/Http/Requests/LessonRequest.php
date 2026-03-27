<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize inputs - security best practice
        $this->merge([
            'title' => trim($this->title ?? ''),
            'text_content' => trim($this->text_content ?? ''),
            'video_url' => trim($this->video_url ?? ''),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'module_id' => 'required|exists:modules,id',
            'title' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
            'content_type' => 'nullable|in:text,video,worksheet,interactive',
            
            // Description/instructions field (optional for all types)
            'description' => 'nullable|string|max:5000',
            
            // Common fields
            'duration' => 'nullable|integer|min:1|max:300',
            'order' => 'nullable|integer|min:0|max:1000',
            'is_published' => 'nullable|boolean',
            
            // Topics JSON (for new topic-based system)
            'topics_json' => 'nullable|json',
        ];

        // Content-type specific rules (for legacy lessons without topics)
        $contentType = $this->input('content_type', 'text');

        // Only apply content validation if not using topics
        if (!$this->filled('topics_json')) {
            switch ($contentType) {
                case 'text':
                    $rules['text_content'] = 'nullable|string|max:50000';
                    $rules['image_attachments'] = 'nullable|array';
                    $rules['image_attachments.*'] = 'image|mimes:jpeg,png,jpg,gif,webp|max:5120'; // 5MB
                    $rules['image_captions'] = 'nullable|array';
                    $rules['image_captions.*'] = 'nullable|string|max:255';
                    $rules['image_display_mode'] = 'nullable|in:none,gallery,slideshow';
                    $rules['slideshow_transition'] = 'nullable|in:fade,slide';
                    break;

                case 'video':
                    // Either URL OR file is optional for container lessons
                    $rules['video_url'] = [
                        'nullable',
                        'url',
                        'regex:/^https:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.*$/',
                    ];
                    $rules['video_file'] = 'nullable|file|mimes:mp4,avi,mov,wmv,flv,webm|max:102400'; // 100MB
                    break;

                case 'worksheet':
                    $rules['text_content'] = 'nullable|string|max:50000';
                    $rules['worksheet_file'] = 'nullable|file|mimes:pdf,doc,docx|max:10240'; // 10MB
                    break;

                case 'interactive':
                    $rules['interactive_type'] = 'nullable|string|in:body_parts,feelings_matching,touch_scenarios,hygiene_sequence,privacy_zones,picture_comparison,drag_drop,matching';
                    $rules['text_content'] = 'nullable|string|max:10000';
                    $rules['interactive_config'] = 'nullable|array';
                    break;
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Lesson title is required.',
            'title.min' => 'Lesson title must be at least 3 characters.',
            'title.max' => 'Lesson title cannot exceed 255 characters.',
            
            'content_type.in' => 'Invalid lesson type selected.',
            
            'text_content.max' => 'Content cannot exceed 50,000 characters.',
            
            'video_url.regex' => 'Only YouTube and Vimeo videos are supported. Please provide a valid video URL.',
            'video_file.max' => 'Video file size cannot exceed 100MB.',
            'video_file.mimes' => 'Video must be in MP4, AVI, MOV, WMV, or WEBM format.',
            
            'worksheet_file.max' => 'Worksheet file size cannot exceed 10MB.',
            'worksheet_file.mimes' => 'Worksheet must be a PDF, DOC, or DOCX file.',
            
            'image_attachments.*.max' => 'Each image cannot exceed 5MB.',
            'image_attachments.*.mimes' => 'Images must be JPEG, PNG, GIF, or WEBP format.',
            
            'interactive_type.in' => 'Invalid interactive activity type.',
            
            'duration.min' => 'Duration must be at least 1 minute.',
            'duration.max' => 'Duration cannot exceed 5 hours (300 minutes).',
            
            'order.max' => 'Order value cannot exceed 1000.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'module_id' => 'module',
            'content_type' => 'lesson type',
            'text_content' => 'content',
            'video_url' => 'video URL',
            'worksheet_file' => 'worksheet',
        ];
    }
}
