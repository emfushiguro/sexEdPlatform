<?php

namespace App\Enums;

enum SeminarInteractionStatus: string
{
    case Visible = 'visible';
    case Pending = 'pending';
    case Answered = 'answered';
    case Hidden = 'hidden';
}
