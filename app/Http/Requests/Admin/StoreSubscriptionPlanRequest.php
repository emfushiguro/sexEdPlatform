<?php

namespace App\Http\Requests\Admin;

class StoreSubscriptionPlanRequest extends StorePlanRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['plan_audience'] = ['required', 'in:learner,instructor'];
        $rules['start_date'] = ['nullable', 'date'];
        $rules['end_date'] = ['nullable', 'date', 'after_or_equal:start_date'];
        $rules['prices.*.duration_unit'] = ['required_with:prices', 'in:minute,hour,day,week,month,year'];
        $rules['prices.*.amount_minor'] = ['required_with:prices', 'integer', 'min:0'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'entitlement_enabled' => $this->normalizeBooleanMap($this->input('entitlement_enabled', [])),
            'entitlement_unlimited' => $this->normalizeBooleanMap($this->input('entitlement_unlimited', [])),
        ]);
    }

    /**
     * @param mixed $map
     */
    private function normalizeBooleanMap($map): array
    {
        if (!is_array($map)) {
            return [];
        }

        $normalized = [];

        foreach ($map as $key => $value) {
            $normalized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $normalized;
    }
}
