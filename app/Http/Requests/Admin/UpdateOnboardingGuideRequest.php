<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOnboardingGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage onboarding guides');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'target_role' => ['sometimes', 'required', Rule::in(['learner', 'instructor'])],
            'video' => [
                'nullable',
                'file',
                'max:102400',
                'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm',
                'mimes:mp4,mpeg,mpg,mov,avi,webm',
            ],
        ];
    }
}
