<?php
namespace App\Http\Enums;

enum Priority:int
{
    case VERY_URGENT = 1;
    case URGENT = 2;
    case NON_URGENT = 3;
}
