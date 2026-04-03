<?php

namespace App\Http\Requests\Admin;

use App\Enums\ModuleReviewRejectionReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectModuleReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason_code' => [
                'required',
                Rule::in(ModuleReviewRejectionReason::values()),
            ],
            'guidance_note' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:reason_code,other',
            ],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'issue_warning' => ['nullable', 'boolean'],
            'moderation_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->filled('feedback')) {
            return;
        }

        $reasonCode = $this->input('reason_code');
        $guidance = trim((string) $this->input('guidance_note', ''));
        $moderationNotes = trim(strip_tags((string) $this->input('moderation_notes', '')));

        if (!$reasonCode) {
            return;
        }

        $reasonLabel = ModuleReviewRejectionReason::tryFrom($reasonCode)?->label() ?? 'Rejection reason';
        $segments = [$reasonLabel];

        if ($guidance !== '') {
            $segments[] = $guidance;
        }

        if ($moderationNotes !== '') {
            $segments[] = $moderationNotes;
        }

        $feedback = implode("\n\n", $segments);

        $this->merge([
            'feedback' => $feedback,
        ]);
    }
}
