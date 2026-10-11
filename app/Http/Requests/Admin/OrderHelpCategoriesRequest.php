<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderHelpCategoriesRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['items' => ['required', 'array'], 'items.*.id' => ['required', 'integer', 'exists:help_categories,id'], 'items.*.sort_order' => ['required', 'integer', 'min:0']]; }
}
