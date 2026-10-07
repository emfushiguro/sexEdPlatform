<?php

namespace App\Http\Requests\Connector;

use App\Models\Connector;
use App\Support\ConnectorVerificationRequirements;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResubmitConnectorVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connector = $this->route('connector');

        return $this->user() !== null
            && $connector instanceof Connector
            && ($connector->created_by === $this->user()->id
                || $connector->primary_representative_user_id === $this->user()->id);
    }

    public function rules(): array
    {
        return [
            'submission_round' => ['required', 'integer', 'min:0'],
            'organization_type' => [
                'nullable',
                'string',
                Rule::in(array_keys(app(ConnectorVerificationRequirements::class)->organizationTypes())),
            ],
            'reuse_representative_identity' => ['nullable', 'boolean'],
            'evidence' => ['required', 'array'],
            'evidence.*' => ['array'],
            'evidence.*.files' => ['nullable', 'array'],
            'evidence.*.files.*' => ['file'],
        ];
    }
}
