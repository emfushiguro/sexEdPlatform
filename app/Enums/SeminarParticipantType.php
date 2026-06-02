<?php

namespace App\Enums;

enum SeminarParticipantType: string
{
    case Learners = 'learners';
    case Instructors = 'instructors';
    case LearnersAndInstructors = 'learners_and_instructors';

    public function includesLearners(): bool
    {
        return in_array($this, [self::Learners, self::LearnersAndInstructors], true);
    }

    public function includesInstructors(): bool
    {
        return in_array($this, [self::Instructors, self::LearnersAndInstructors], true);
    }
}
