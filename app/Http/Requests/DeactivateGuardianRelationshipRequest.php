<?php

namespace App\Http\Requests;

use App\Models\ParentChildAccount;
use Illuminate\Foundation\Http\FormRequest;

class DeactivateGuardianRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $relationship = $this->route('parentChildAccount');

        if (! $user || ! $relationship instanceof ParentChildAccount) {
            return false;
        }

        return $user->hasRole('admin')
            || in_array((int) $user->id, [
                (int) $relationship->parent_user_id,
                (int) $relationship->child_user_id,
            ], true);
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
