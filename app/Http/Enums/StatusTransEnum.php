<?php

namespace App\Http\Enums;

enum StatusTransEnum:int
{
    case INITIATED = 0;
    case VALIDATED = 1;
    case PENDING = 2;
    case CANCELED = 3;

}
