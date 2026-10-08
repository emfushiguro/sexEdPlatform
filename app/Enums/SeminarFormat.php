<?php

namespace App\Enums;

enum SeminarFormat: string
{
    case InPerson = 'in_person';
    case External = 'external';
    case Native = 'native';
}
