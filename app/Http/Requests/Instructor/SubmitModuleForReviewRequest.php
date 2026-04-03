<?php

namespace App\Http\Requests\Instructor;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;

class SubmitModuleForReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Module|null $module */
        $module = $this->route('module');

        return $this->user()?->hasRole('instructor')
            && $module !== null
            && (int) $module->created_by === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [];
    }
}
