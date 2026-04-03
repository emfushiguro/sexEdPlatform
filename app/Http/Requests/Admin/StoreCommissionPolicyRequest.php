<?php

namespace App\Http\Requests\Admin;

use App\Models\CommissionPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommissionPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope_type' => ['required', 'in:global,instructor'],
            'scope_id' => ['nullable', 'required_if:scope_type,instructor', 'integer', 'exists:users,id'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_basis' => ['required', 'in:gross,net'],
            'refund_policy' => ['required', 'in:disabled,platform_absorbs,proportional'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->hasOverlappingWindow()) {
                $validator->errors()->add('effective_from', 'The selected effective window overlaps an existing policy for this scope.');
            }
        });
    }

    protected function policyIdToIgnore(): ?int
    {
        return null;
    }

    private function hasOverlappingWindow(): bool
    {
        $scopeType = (string) $this->input('scope_type');
        $scopeId = $scopeType === CommissionPolicy::SCOPE_INSTRUCTOR
            ? (int) $this->input('scope_id')
            : null;

        $from = Carbon::parse((string) $this->input('effective_from'));
        $to = $this->filled('effective_to')
            ? Carbon::parse((string) $this->input('effective_to'))
            : Carbon::create(9999, 12, 31, 23, 59, 59);

        $query = CommissionPolicy::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('effective_from', '<=', $to)
            ->where(function ($inner) use ($from) {
                $inner->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $from);
            });

        if ($this->policyIdToIgnore()) {
            $query->where('id', '!=', $this->policyIdToIgnore());
        }

        return $query->exists();
    }
}
