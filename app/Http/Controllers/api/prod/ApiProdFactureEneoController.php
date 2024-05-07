<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiProdFactureEneoController extends Controller
{
    public function eneo_CheckFactureStatus($numFacture){ //GetCashTransferStatus

        if (strlen($numFacture) !=9){
            return response()->json([
                'status'=>'error',
                'message'=>'Le numéro de la facture est incorrect'
            ],404);
        }

        return response()->json([
            'success' => true,
            'message' => "Facture valide",
            'amount'=>rand(5000,200000),
            'numContrat'=> rand(2000000,9999999),
            'numFacture'=> $numFacture,
            'ownerName'=> strtoupper(fake()->name()),
        ], 200);

    }

}
