<?php

namespace App\Http\Requests\Learner;

use App\Enums\ContentReportReason;
use App\Enums\ContentReportTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isLearner() ?? false;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(ContentReportTargetType::values())],
            'target_id' => ['required', 'integer', 'min:1'],
            'reason_code' => ['required', Rule::in(ContentReportReason::values())],
            'details' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
