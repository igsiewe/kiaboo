<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiProdOrangeMoneyController extends Controller
{
    public function OM_GetTokenAccess()
    {

        $response = Http::withOptions(['verify' => false,])
            ->withBasicAuth('_Vj5oHqhBuZsotMaGpnbGPCwccoa', 'L7cNBHLZCZMJSxufRo_rfVS_4M4a')
            ->withBody('grant_type=client_credentials', 'application/x-www-form-urlencoded')
            ->Post('https://apiw.orange.cm/token');


        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            Log::error([
                'user' => Auth::user()->id,
                'code'=> $response->status(),
                'function' => "OM_GetTokenAccess",
                'response'=>$response->body(),

            ]);
            return response()->json([
                'status'=>'error',
                'message'=>"Erreur ".$response->status(). ' : Erreur lors de la connexion au serveur. Veuillez réessayer plus tard'
            ],$response->status());

        }

    }
}
