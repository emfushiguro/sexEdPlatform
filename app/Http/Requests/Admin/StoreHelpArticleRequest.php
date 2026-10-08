<?php

namespace App\Http\Requests\Admin;

use App\Models\HelpArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreHelpArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'help_category_id' => ['required', 'integer', 'exists:help_categories,id'],
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'alpha_dash', 'max:200', Rule::unique('help_articles', 'slug')],
            'summary' => ['required', 'string', 'max:500'],
            'keywords_text' => ['nullable', 'string', 'max:2000'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:80'],
            'audiences' => ['required', 'array', 'min:1'],
            'audiences.*' => [Rule::in(['all', 'guest', 'learner', 'parent', 'instructor', 'connector', 'admin'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.heading' => ['nullable', 'string', 'max:180'],
            'sections.*.body' => ['required', 'string', 'max:20000'],
            'sections.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sections.*.remove_image' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $article = $this->route('helpArticle');
        $slug = $article?->slug ?: trim((string) $this->input('slug', ''));

        if ($slug === '') {
            $slug = Str::slug((string) $this->input('title', ''));
            $baseSlug = $slug;
            $suffix = 2;
            while ($slug !== '' && HelpArticle::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }
        }

        $raw = $this->input('keywords_text');
        $values = $raw !== null ? [(string) $raw] : (array) $this->input('keywords', []);
        $keywords = collect($values)
            ->flatMap(fn ($value) => preg_split('/\R/u', (string) $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $keyword): string => mb_strtolower(trim($keyword)))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $audiences = array_values(array_unique((array) $this->input('audiences', [])));
        $this->merge(['slug' => $slug, 'keywords' => array_values(array_unique($keywords)), 'audiences' => $audiences]);
        if (in_array('all', $audiences, true) && count($audiences) > 1) {
            $this->merge(['audiences' => ['__invalid_all_combination__']]);
        }
    }
}
