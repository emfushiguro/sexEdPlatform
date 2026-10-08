<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHelpCategoryRequest extends StoreHelpCategoryRequest
{
    public function rules(): array
    {
        $category = $this->route('helpCategory');

        return array_replace_recursive(parent::rules(), [
            'slug' => ['required', 'alpha_dash', 'max:140', Rule::unique('help_categories', 'slug')->ignore($category?->id)],
        ]);
    }
}
