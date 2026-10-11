<?php

namespace App\Http\Requests\Connector;

use App\Models\ConnectorModuleAccessRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConnectorModuleAccessRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_type' => [
                'required',
                Rule::in([
                    ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
                    ConnectorModuleAccessRule::TARGET_ROLE,
                    ConnectorModuleAccessRule::TARGET_MEMBER,
                ]),
            ],
            'target_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn (): bool => in_array(
                    (string) $this->input('target_type'),
                    [ConnectorModuleAccessRule::TARGET_ROLE, ConnectorModuleAccessRule::TARGET_MEMBER],
                    true,
                )),
            ],
        ];
    }
}
