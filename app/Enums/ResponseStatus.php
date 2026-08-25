<?php

namespace App\Enums;

enum ResponseStatus: string
{
    case Partial = 'partial';
    case Complete = 'complete';
}
