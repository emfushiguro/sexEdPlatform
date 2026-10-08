<?php

namespace App\Http\Requests\Connector;

use App\Services\Seminars\SeminarPublicationValidator;

class UpdateSeminarRequest extends StoreSeminarRequest
{
    public function rules(): array
    {
        return SeminarPublicationValidator::rules();
    }
}
