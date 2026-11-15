<?php

namespace App\Http\Requests\DependentSupport;

use App\Models\User;
use App\Support\LearnerAgePolicy;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGuardianSupportAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User
            && LearnerAgePolicy::classify($this->user()->birthdate) === LearnerAgePolicy::ELIGIBLE_ADULT;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required_without:permissions', 'boolean'],
            'permissions' => ['required_without:enabled', 'array:can_view_progress,can_view_quiz_answers,can_manage_support_information'],
            'permissions.can_view_progress' => ['required_with:permissions', 'boolean'],
            'permissions.can_view_quiz_answers' => ['required_with:permissions', 'boolean'],
            'permissions.can_manage_support_information' => ['required_with:permissions', 'boolean'],
        ];
    }

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }

    public function permissions(): array
    {
        if ($this->has('permissions')) {
            return collect(['can_view_progress', 'can_view_quiz_answers', 'can_manage_support_information'])
                ->mapWithKeys(fn (string $permission): array => [$permission => $this->boolean('permissions.'.$permission)])
                ->all();
        }

        return ['can_manage_support_information' => $this->enabled()];
    }
}
