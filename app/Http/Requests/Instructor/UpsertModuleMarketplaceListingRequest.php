<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertModuleMarketplaceListingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('seat_packages_present') && ! $this->has('seat_packages')) {
            $this->merge(['seat_packages' => []]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'currency' => ['required', 'string', 'size:3', Rule::in([config('billing.currency.code')])],
            'seat_capacity' => ['required', 'integer', 'min:1'],
            'seat_packages' => ['nullable', 'array', 'max:9'],
            'seat_packages.*' => ['array:seat_capacity,price_amount'],
            'seat_packages.*.seat_capacity' => ['required', 'integer', 'min:1', 'distinct'],
            'seat_packages.*.price_amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'action' => ['required', Rule::in(['save', 'publish'])],
        ];
    }
}
