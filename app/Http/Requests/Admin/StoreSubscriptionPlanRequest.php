<?php

namespace App\Http\Requests\Admin;

class StoreSubscriptionPlanRequest extends StorePlanRequest
{
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
