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
        return [
            'module_id' => 'required|exists:modules,id',
            'title' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-:,.()]+$/', // Only allow safe characters
            ],
            'content_type' => 'required|in:text,video,worksheet,interactive',
            
            // Description/instructions field (optional for all types)
            'description' => 'nullable|string|max:5000',
            
            // Text content rules (now nullable to allow TinyMCE empty state)
            'text_content' => 'nullable|string|max:50000',
            
            // Image attachments for text lessons
            'image_attachments.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB per image
            
            // Video rules - support both URL and file upload
            'video_url' => [
                'nullable',
                'required_if:content_type,video',
                'required_without:video_file',
                'url',
                'regex:/^https:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.*$/',
            ],
            'video_file' => 'nullable|required_if:content_type,video|required_without:video_url|file|mimes:mp4,avi,mov,wmv|max:102400', // 100MB max
            
            // Worksheet rules
            'worksheet_file' => 'nullable|required_if:content_type,worksheet|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            
            // Interactive lesson configuration
            'interactive_type' => 'nullable|in:picture_comparison,body_parts,drag_drop,matching',
            'interactive_config' => 'nullable|array',
            
            // Common fields
            'duration' => 'required|integer|min:1|max:300',
            'order' => 'nullable|integer|min:0|max:1000',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.regex' => 'Title contains invalid characters. Only letters, numbers, spaces, and basic punctuation allowed.',
            'video_url.required_if' => 'Video URL is required for video lessons.',
            'video_url.regex' => 'Only YouTube and Vimeo videos are supported.',
            'text_content.required_if' => 'Content is required for this lesson type.',
            'text_content.max' => 'Content cannot exceed 50,000 characters.',
            'duration.max' => 'Duration cannot exceed 5 hours (300 minutes).',
            'worksheet_file.max' => 'File size cannot exceed 10MB.',
            'order.max' => 'Order value is too large.',
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
