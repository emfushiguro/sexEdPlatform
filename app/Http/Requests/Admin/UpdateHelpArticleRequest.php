<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateHelpArticleRequest extends StoreHelpArticleRequest
{
    public function rules(): array
    {
        $article = $this->route('helpArticle');

        return array_replace_recursive(parent::rules(), [
            'slug' => ['required', 'alpha_dash', 'max:200', Rule::unique('help_articles', 'slug')->ignore($article?->id)],
        ]);
    }
}
