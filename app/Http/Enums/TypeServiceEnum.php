<?php

namespace App\Http\Enums;

enum TypeServiceEnum:int
{
    case APPROVISIONNEMENT = 1;
    case REMBOURSEMENT = 2;
    case ANNULATION = 3;
    case ENVOI = 4;
    case RETRAIT =5;
    case FACTURE = 6;
    case PAYMENT = 7;

}
