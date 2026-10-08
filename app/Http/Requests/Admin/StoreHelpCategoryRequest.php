<?php

namespace App\Http\Requests\Admin;

use App\Models\HelpCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreHelpCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:140', Rule::unique('help_categories', 'slug')],
            'description' => ['nullable', 'string', 'max:500'],
            'icon_key' => ['nullable', Rule::in(['rocket', 'account', 'book', 'quiz', 'seminar', 'community', 'guardian', 'instructor', 'connector', 'payment', 'shield', 'accessibility', 'tools'])],
            'audiences' => ['required', 'array', 'min:1'],
            'audiences.*' => [Rule::in(['all', 'guest', 'learner', 'parent', 'instructor', 'connector', 'admin'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $category = $this->route('helpCategory');
        $slug = $category?->slug ?: trim((string) $this->input('slug', ''));

        if ($slug === '') {
            $slug = Str::slug((string) $this->input('name', ''));
            $baseSlug = $slug;
            $suffix = 2;
            while ($slug !== '' && HelpCategory::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }
        }

        $this->merge(['slug' => $slug, 'is_active' => $this->boolean('is_active')]);
        $audiences = array_values(array_unique((array) $this->input('audiences', [])));
        if (in_array('all', $audiences, true) && count($audiences) > 1) {
            $this->merge(['audiences' => ['__invalid_all_combination__']]);
        }
    }
}
