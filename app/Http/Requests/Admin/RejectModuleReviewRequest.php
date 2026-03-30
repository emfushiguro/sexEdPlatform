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
                'required_without:feedback',
                'nullable',
                Rule::in(ModuleReviewRejectionReason::values()),
            ],
            'guidance_note' => [
                'required_without:feedback',
                'nullable',
                'string',
                'max:2000',
            ],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->filled('feedback')) {
            return;
        }

        $reasonCode = $this->input('reason_code');
        $guidance = trim((string) $this->input('guidance_note', ''));

        if (!$reasonCode || $guidance === '') {
            return;
        }

        $reasonLabel = ModuleReviewRejectionReason::tryFrom($reasonCode)?->label() ?? 'Rejection reason';

        $this->merge([
            'feedback' => $reasonLabel . ': ' . $guidance,
        ]);
    }
}
