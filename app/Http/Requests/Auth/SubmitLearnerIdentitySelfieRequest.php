<?php

namespace App\Http\Requests\Auth;

class SubmitLearnerIdentitySelfieRequest extends SubmitLearnerIdentityRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        return array_intersect_key($rules, array_flip(['selfie', 'confirm_submission']));
    }
}
