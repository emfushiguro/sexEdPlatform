<?php

namespace App\Http\Requests\Admin;

use App\Models\OnboardingGuide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class PublishOnboardingGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage onboarding guides');
    }

    public function rules(): array
    {
        return [
            'requires_recompletion' => ['nullable', 'boolean'],
            'confirm_supersede' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes(
            'requires_recompletion',
            ['required', 'boolean'],
            function (): bool {
                $guide = $this->route('guide');

                return $guide instanceof OnboardingGuide
                    && $guide->current_version_id !== null
                    && $guide->versions()
                        ->whereNull('published_at')
                        ->where('id', '!=', $guide->current_version_id)
                        ->exists();
            },
        );
    }
}
