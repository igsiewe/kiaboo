<?php

namespace App\Http\Enums;

enum UserRolesEnum:int
{
    case SUPADMIN = 1;
    case ADMIN = 2;
    case DISTRIBUTEUR = 3;
    case SDISTRIBUTEUR = 4;
    case AGENT = 5;
    case BACKOFFICE = 6;
    case FRONTOFFICE = 7;
    case AUDIT = 8;
    case ANALYST = 9;
    case IT = 10;
    case ACCOUNTABLE = 11;
    case VIEW = 12;
    Case Stagiaire = 13;
    Case Support = 14;
}
