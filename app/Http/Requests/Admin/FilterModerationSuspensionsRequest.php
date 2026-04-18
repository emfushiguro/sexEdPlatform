<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FilterModerationSuspensionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'in:learner,instructor,parent,admin'],
            'severity' => ['nullable', 'string', 'in:minor,moderate,major,critical'],
            'trigger' => ['nullable', 'string', 'in:manual,automated,system'],
            'status' => ['nullable', 'string', 'in:active,expired,revoked'],
            'appeal_status' => ['nullable', 'string', 'in:none,appeal_pending,pending_review,clarification_requested,approved,rejected'],
            'sort' => ['nullable', 'string', 'in:latest,oldest,ending_soon'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
