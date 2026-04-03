<?php

namespace App\Http\Requests\Admin;

use App\Models\CommissionPolicy;

class UpdateCommissionPolicyRequest extends StoreCommissionPolicyRequest
{
    protected function policyIdToIgnore(): ?int
    {
        $policy = $this->route('commissionPolicy');

        if ($policy instanceof CommissionPolicy) {
            return (int) $policy->id;
        }

        if (is_numeric($policy)) {
            return (int) $policy;
        }

        return null;
    }
}
