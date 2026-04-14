<?php

namespace App\Http\Requests\Learner;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isLearner() ?? false;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_content' => ['required', 'string', 'max:10000'],
        ];
    }
}
