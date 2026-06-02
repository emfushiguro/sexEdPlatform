<?php

namespace App\Http\Requests\Connector;

use App\Support\ConnectorOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConnectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $cityTable = config('psgc.tables.cities', 'cities');
        $barangayTable = config('psgc.tables.barangays', 'barangays');

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(ConnectorOptions::categoryKeys())],
            'organization_email' => ['nullable', 'email', 'max:255', Rule::unique('connectors', 'organization_email')],
            'contact_number' => ['required', 'string', 'max:30'],
            'city_code' => [
                'required',
                'string',
                Rule::exists($cityTable, 'code')->where(fn ($query) => $query
                    ->where('province_code', ConnectorOptions::CAVITE_PROVINCE_CODE)
                    ->orWhere('code', 'like', ConnectorOptions::CAVITE_CITY_CODE_PREFIX.'%')),
            ],
            'barangay_code' => [
                'required',
                'string',
                Rule::exists($barangayTable, 'code')->where(fn ($query) => $query->where('city_code', $this->input('city_code'))),
            ],
            'address_line' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
