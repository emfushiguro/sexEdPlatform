<?php

namespace App\Http\Requests\Parent;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dependent = $this->route('child');

        return $dependent instanceof User && $this->user()?->can('giftSubscription', $dependent);
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')
                ->where('plan_audience', 'learner')->where('is_active', true)->whereNull('archived_at')],
            'request_key' => ['required', 'uuid'],
        ];
    }
}
