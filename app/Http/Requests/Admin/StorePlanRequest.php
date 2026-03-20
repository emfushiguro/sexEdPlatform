<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'plan_audience' => ['required', 'in:learner'],
            'billing_mode' => ['required', 'in:monthly,annual,custom'],
            'start_date' => ['nullable', 'date', 'required_if:billing_mode,custom'],
            'end_date' => ['nullable', 'date', 'required_if:billing_mode,custom', 'after_or_equal:start_date'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // Backward-compatible single price path
            'price' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/', 'required_without:prices'],
            'feature_keys' => ['nullable', 'array'],
            'feature_keys.*' => ['nullable', 'string', 'max:255'],

            // Normalized multi-price path
            'prices' => ['nullable', 'array', 'min:1'],
            'prices.*.duration_mode' => ['required_with:prices', 'in:preset,custom'],
            'prices.*.duration_unit' => ['required_with:prices', 'in:day,week,month,year'],
            'prices.*.duration_count' => ['required_with:prices', 'integer', 'min:1'],
            'prices.*.duration_label' => ['required_with:prices', 'string', 'max:255'],
            'prices.*.amount_minor' => ['required_with:prices', 'integer', 'min:0'],
            'prices.*.currency' => ['nullable', 'string', 'size:3', 'in:PHP'],
            'prices.*.compare_at_minor' => ['nullable', 'integer', 'min:0'],
            'prices.*.is_default' => ['nullable', 'boolean'],
            'prices.*.is_active' => ['nullable', 'boolean'],

            // Normalized feature entitlements
            'entitlements' => ['nullable', 'array'],
            'entitlements.*.feature_key' => ['required_with:entitlements', 'string', 'max:255'],
            'entitlements.*.feature_name' => ['nullable', 'string', 'max:255'],
            'entitlements.*.value_type' => ['required_with:entitlements', 'in:boolean,quota'],
            'entitlements.*.unit_label' => ['nullable', 'string', 'max:255'],
            'entitlements.*.category' => ['nullable', 'string', 'max:255'],
            'entitlements.*.is_enabled' => ['nullable', 'boolean'],
            'entitlements.*.quota_value' => ['nullable', 'integer', 'min:0'],
            'entitlements.*.is_unlimited' => ['nullable', 'boolean'],

            // Simplified learner entitlement payload
            'entitlement_enabled' => ['nullable', 'array'],
            'entitlement_enabled.*' => ['nullable', 'boolean'],
            'entitlement_unlimited' => ['nullable', 'array'],
            'entitlement_unlimited.*' => ['nullable', 'boolean'],
            'entitlement_limits' => ['nullable', 'array'],
            'entitlement_limits.*' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
