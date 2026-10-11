<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertModuleMarketplaceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3', Rule::in([config('billing.currency.code')])],
            'seat_capacity' => ['required', 'integer', 'min:1'],
            'action' => ['required', Rule::in(['save', 'publish'])],
        ];
    }
}
