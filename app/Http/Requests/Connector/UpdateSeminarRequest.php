<?php

namespace App\Http\Requests\Connector;

class UpdateSeminarRequest extends StoreSeminarRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['starts_at'] = ['required', 'date'];

        return $rules;
    }
}
