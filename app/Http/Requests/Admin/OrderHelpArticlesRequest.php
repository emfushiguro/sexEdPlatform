<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderHelpArticlesRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['items' => ['required', 'array'], 'items.*.id' => ['required', 'integer', 'exists:help_articles,id'], 'items.*.sort_order' => ['required', 'integer', 'min:0']]; }
}
