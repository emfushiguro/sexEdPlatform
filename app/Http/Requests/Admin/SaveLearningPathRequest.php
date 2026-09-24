<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\LearningPath;
use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveLearningPathRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->exists('module_ids')) {
            $this->merge(['module_ids' => []]);
        }
    }

    public function authorize(): bool
    {
        $path = $this->route('learningPath');
        $ability = $path instanceof LearningPath ? 'update' : 'create';
        $subject = $path instanceof LearningPath ? $path : LearningPath::class;

        return Gate::allows($ability, $subject)
            && ($this->input('status') !== LearningPath::STATUS_PUBLISHED
                || Gate::allows('publish', $subject));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::in(LearningPath::STATUSES)],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['string', 'distinct', Rule::in(['kids', 'teens', 'adults'])],
            'module_ids' => ['present', 'array'],
            'module_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('status') || $validator->errors()->has('categories')
                || $validator->errors()->has('module_ids')
                || collect($validator->errors()->keys())->contains(fn (string $key): bool => str_starts_with($key, 'categories.') || str_starts_with($key, 'module_ids.'))) {
                return;
            }

            $status = $this->input('status');
            $categories = $this->input('categories', []);
            $ids = array_map('intval', $this->input('module_ids', []));

            if ($status === LearningPath::STATUS_PUBLISHED && $ids === []) {
                $validator->errors()->add('module_ids', 'A published learning path needs at least one module.');
            }

            if ($status === LearningPath::STATUS_PUBLISHED
                && ! Gate::allows('publish', $this->route('learningPath') ?? LearningPath::class)) {
                $validator->errors()->add('status', 'You are not allowed to publish learning paths.');
            }

            if ($ids === []) {
                return;
            }

            $modules = Module::query()->learnerVisible()->with('learnerCategories')
                ->whereIn('id', $ids)->get()->keyBy('id');
            if ($modules->count() !== count(array_unique($ids))) {
                $validator->errors()->add('module_ids', 'Choose only learner-visible modules that still exist.');

                return;
            }

            foreach ($modules as $module) {
                if (array_intersect($categories, $module->learnerCategoryKeys()) === []) {
                    $validator->errors()->add('module_ids', 'Each selected module must support at least one path category.');

                    return;
                }
            }
        }];
    }
}
