<?php

namespace App\Enums;

enum Decision: string
{
    case None = 'none';
    case Remove = 'remove';
    case UpToStandard = 'up_to_standard';
}
