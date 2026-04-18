<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGamificationPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'policy_payload' => ['nullable', 'array'],
            'points_config' => ['nullable', 'array'],
            'streak_config' => ['nullable', 'array'],
            'leveling_config' => ['nullable', 'array'],
            'shield_config' => ['nullable', 'array'],
            'safeguards_config' => ['nullable', 'array'],
            'change_summary' => ['nullable', 'string', 'max:1000'],
            'version_label' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function payload(): array
    {
        $policyPayload = $this->input('policy_payload');

        if (is_array($policyPayload)) {
            return $policyPayload;
        }

        return array_filter([
            'points_config' => $this->input('points_config'),
            'streak_config' => $this->input('streak_config'),
            'leveling_config' => $this->input('leveling_config'),
            'shield_config' => $this->input('shield_config'),
            'safeguards_config' => $this->input('safeguards_config'),
        ], static fn (mixed $value): bool => is_array($value));
    }
}
