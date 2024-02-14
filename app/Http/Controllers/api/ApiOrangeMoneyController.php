<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiOrangeMoneyController extends Controller
{
 //   protected String $token;

    public function __construct()
    {
//        $responseToken = $this->OM_GetTokenAccess();
//        $dataAcessToken = json_decode($responseToken->getContent());
//        $AccessToken = $dataAcessToken->access_token;
//        $this->token = $AccessToken;

    }

    public function OM_GetTokenAccess()
    {

        $response = Http::withOptions(['verify' => false,])
            ->withBasicAuth('rEvcWyBY06f9epiUYRB6hEbktTUa', 'JM5hPUe4BXa3PjZCPfcP73Da0l4a')
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

    public function OM_NameCustomer($customerNumber)
    {

        if (strlen($customerNumber) !=9){
            return response()->json([
                'status'=>'error',
                'message'=>'Le numéro de téléphone incorrect'
            ],404);
        }
        $responseToken = $this->OM_GetTokenAccess();
        if($responseToken->getStatusCode() !=200){
            return $responseToken;
        }
        $dataAcessToken = json_decode($responseToken->getContent());

        try{

            $AccessToken = $dataAcessToken->access_token;
            $token = $AccessToken;

            $endpoint = 'https://apiw.orange.cm/omcoreapis/1.0.2/infos/subscriber/customer/'.$customerNumber;
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Authorization'=> 'Bearer '.$token,
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=> 'TVlQQVNPTTpNWVBBU1NBTkRCT1gyMDIy',
                        'Cookie'=> 'PHPSESSID=kee6vgaptskaks317hhjr273c5; route=1676640055.353.38868.519072',
                    ])

                ->Post($endpoint, [
                    "pin"=> "2222",
                    "channelMsisdn"=> "691301143"
                ]  );

            if($response->status()==200){
                $data = json_decode($response, false);
                $firstName = $data->data->firstName;
                $lastName = $data->data->lastName;

                return response()->json([
                    'status' => 'success',
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                ],200);
                //  return response()->json($response->json());
            }else{
                Log::error([
                    'code'=> $response->status(),
                    'function' => "OM_NameCustomer",
                    'response'=>$response->body(),
                    'user' => Auth::user()->id,
                    'customerPhone'=>$customerNumber,
                ]);
                $body = json_decode($response->body());
                return response()->json([
                    'code' => $response->status(),
                    'message'=>"Erreur ".$response->status()." : ".$body->message
                ],$response->status());
            }
        }catch (\Exception $e){
            Log::error([
                'user' => Auth::user()->id,
                'code'=> $e->getCode(),
                'function' => "OM_NameCustomer",
                'response'=>$e->getMessage(),
                'user' => Auth::user()->id,
                'customerPhone'=>$customerNumber,
            ]);
            return response()->json([
              //  'code' => $e->getCode(),
                'message'=>$e->getMessage()
            ],$e->getCode());
        }

    }

    public function OM_CashIn_init()
    {

        $responseToken = $this->OM_GetTokenAccess();
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $token = $AccessToken;

        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$token,
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=> 'TVlQQVNPTTpNWVBBU1NBTkRCT1gyMDIy',
                    'Cookie'=> 'PHPSESSID=kee6vgaptskaks317hhjr273c5; route=1676640055.353.38868.519072',
                ])

            ->Post('https://apiw.orange.cm/omcoreapis/1.0.2/cashin/init');

        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            return response()->json([
                'code' => $response->status(),
                'message'=>$response->body(),
            ]);
        }

    }

    public function OM_Cashin_execute($payToken, $beneficiaire, $montant, $transId)
    {
        //On genere le PayToken du depot
        $responseToken = $this->OM_GetTokenAccess();
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $token = $AccessToken;

        //On execute le depot
        $description = "Dépôt d'argent sur le compte Orange Money de ".$beneficiaire." par ".Auth::user()->telephone." d'un montant de ".$montant." FCFA";

        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$token,
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=> 'TVlQQVNPTTpNWVBBU1NBTkRCT1gyMDIy',
                    'Cookie'=> 'PHPSESSID=kee6vgaptskaks317hhjr273c5; route=1676640055.353.38868.519072',
                ])

            ->Post('https://apiw.orange.cm/omcoreapis/1.0.2/cashin/pay', [
                "channelUserMsisdn"=> "691301143",
                "amount"=> $montant,
                "subscriberMsisdn"=> $beneficiaire,
                "pin"=> "2222",
                "orderId"=> str_replace(".","",$transId),
                "description"=> $description,
                "payToken"=> $payToken
            ]);

        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            return response()->json([
                'code' => $response->status(),
                'message'=>$response->body(),
            ]);
        }
    }

    public function OM_CashOut_init($token)
    {
//        $responseToken = $this->OM_GetTokenAccess();
//        $dataAcessToken = json_decode($responseToken->getContent());
//        $AccessToken = $dataAcessToken->access_token;
//        $token = $AccessToken;
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$token,
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=> 'TVlQQVNPTTpNWVBBU1NBTkRCT1gyMDIy',
                    'Cookie'=> 'PHPSESSID=kee6vgaptskaks317hhjr273c5; route=1676640055.353.38868.519072',
                ])

            ->Post('https://apiw.orange.cm/omcoreapis/1.0.2/cashout/init');

        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            Log::error([
                'code'=> $response->status(),
                'function' => "OM_Cashin_init",
                'response'=>$response->body(),
            ]);
            return response()->json([
                'code' => $response->status(),
                'message'=>$response->body(),
            ]);
        }

    }

    public function OM_CashOut_execute($beneficiaire, $montant, $reference, $description, $token)
    {

//        $responseToken = $this->OM_GetTokenAccess();
//        $dataAcessToken = json_decode($responseToken->getContent());
//        $AccessToken = $dataAcessToken->access_token;
//        $token = $AccessToken;

        //On genere le PayToken du retrait
        $responsePayToken = $this->OM_CashOut_init($token);
        $dataPayToken = json_decode($responsePayToken->getContent());
        $payToken = $dataPayToken->data->payToken;



        //On execute le depot
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$this->token,
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=> 'TVlQQVNPTTpNWVBBU1NBTkRCT1gyMDIy',
                    'Cookie'=> 'PHPSESSID=kee6vgaptskaks317hhjr273c5; route=1676640055.353.38868.519072',
                ])

            ->Post('https://apiw.orange.cm/omcoreapis/1.0.2/cashout/pay', [
                "channelUserMsisdn"=> "691301143",
                "amount"=> $montant,
                "subscriberMsisdn"=> $beneficiaire,
                "pin"=> "2222",
                "orderId"=> str_replace(".","",$reference),
                "description"=> $description,
                "payToken"=> $payToken,
                "notifUrl"=> "https://notification.com"
            ]);

        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            Log::error([
                'code'=> $response->status(),
                'function' => "OM_CashOut_execute",
                'response'=>$response->body(),
             //   'payToken'=>$payToken,
                // 'user' => Auth::user()->id,
            ]);
            return response()->json([
                'code' => $response->status(),
                'message'=>$response->body(),
            ]);
        }
    }


}
