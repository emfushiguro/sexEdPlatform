<?php

namespace App\Enums;

enum LearnerIdentityRejectionReason: string
{
    case UnclearId = 'unclear_id';
    case UnclearSelfie = 'unclear_selfie';
    case BirthdateMismatch = 'birthdate_mismatch';
    case InformationMismatch = 'information_mismatch';
    case UnsupportedDocumentType = 'unsupported_document_type';

    public function label(): string
    {
        return match ($this) {
            self::UnclearId => 'Please upload a clearer ID photo.',
            self::UnclearSelfie => 'Please upload a clearer selfie photo.',
            self::BirthdateMismatch => 'The birthdate on your ID does not match your profile.',
            self::InformationMismatch => 'The information on your ID does not match your profile.',
            self::UnsupportedDocumentType => 'This ID type is not accepted for your learner account.',
        };
    }
}
