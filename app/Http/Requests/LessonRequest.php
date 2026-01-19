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
            
            // Text content rules
            'text_content' => 'required_if:content_type,text,interactive|nullable|string|max:50000',
            
            // Video rules - enhanced security
            'video_url' => [
                'required_if:content_type,video',
                'nullable',
                'url',
                'regex:/^https:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.*$/', // Only allow YouTube/Vimeo
            ],
            
            // Worksheet rules - strict file validation
            'worksheet_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            
            // Common fields
            'duration' => 'required|integer|min:1|max:300', // Max 5 hours
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
