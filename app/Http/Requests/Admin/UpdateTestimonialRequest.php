<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return ['display_role' => ['nullable', 'string', 'max:60'], 'quotation' => ['required', 'string', 'max:1000'], 'sort_order' => ['required', 'integer', 'min:0']];
    }
}
