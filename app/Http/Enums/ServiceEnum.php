<?php

namespace App\Http\Enums;

enum ServiceEnum:int
{
    case APPRO_DISTRIBUTEUR = 1;
    case APPRO_S_DISTRIBUTEUR = 2;
    case APPRO_AGENT = 3;
    case DEPOT_OM = 4;
    case RETRAIT_OM = 5;
    case DEPOT_MOMO = 6;
    case RETRAIT_MOMO = 7;
    case ANNULATION = 8;
    case PAIEMENT_COMMISSION =9;
    case PAIEMENT_COMMISSION_DISTRIBUTEUR =10;
    case DEPOT_M2U = 11;
    case RETRAIT_M2U = 12;
    case RETRAIT_M2U_CB = 13;

    case DEPOT_YOOMEE= 14;
    case RETRAIT_YOOMEE = 15;
    case FACTURE_ENEO = 16;

    case PAYMENT_MOMO = 17;
    case PAYMENT_OM = 18;
    case PAYMENT_M2U=19;
    case PAYMENT_YOOMEE = 20;

    case TRANSFERT = 21;

}
