<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentReportAction;
use App\Enums\ContentReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ContentReportStatus::values())],
            'action' => ['required', Rule::in(ContentReportAction::values())],
            'moderation_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
