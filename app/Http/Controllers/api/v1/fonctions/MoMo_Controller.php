<?php

namespace App\Http\Controllers\api\v1\fonctions;

use App\Http\Controllers\ApiLog;
use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\User;
use http\Env\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class MoMo_Controller extends Controller
{
    protected $callbackUrl;
    protected $OcpApimSubscriptionKeyDisbursement;
    protected $OcpApimSubscriptionKeyCollection;
    protected $userNameDisbursement;
    protected $passwordDisbursement;
    protected $userNameCollection;
    protected $passwordCollection;

    protected $endpoint;

    public function __construct()
    {
        $this->endpoint="https://proxy.momoapi.mtn.com";
        $this->callbackUrl="https://kiaboopay.com/api/momo/callback";
        $this->userNameDisbursement = "d51773a3-837d-4dd7-9413-2f82bc3c2de2";
        $this->passwordDisbursement = "c22f08082732417ea3ee479820813317";
        $this->userNameCollection = "748d8c40-bbe9-46e5-9d78-eb646c0de2af";
        $this->passwordCollection = "6ece0272f24745b7bedd0c3406abf3c9";
        $this->OcpApimSubscriptionKeyDisbursement="1466a4536a3c476ab18baf82ce82a1f3";
        $this->OcpApimSubscriptionKeyCollection="886cc9e141ab492f80d9567b3c46d59c";

    }
    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    public function MOMO_Disbursement_GetTokenAccess(){

        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyDisbursement])->withBasicAuth($this->userNameDisbursement, $this->passwordDisbursement)
            ->Post($this->endpoint.'/disbursement/token/');
        if($response->status()==200){
            return response()->json($response->json());
        }else{
            $alerte = new ApiLog();
            $alerte->logError($response->status(), "MOMO Token", null, json_decode($response->body()), "MOMO_Disbursement_GetTokenAccess");
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }

    }
    public function MOMO_Payment_GetTokenAccess(){

        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyCollection])->withBasicAuth($this->userNameCollection, $this->passwordCollection)
            ->Post($this->endpoint.'/collection/token/');
        if($response->status()==200){
            return response()->json($response->json());
        }else{
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );

        }

    }
    public function MOMO_Payment($accessToken, $referenceID,  $externalId, $amount, $customerPhone)
    {
        try {
            $user = User::where("id",Auth::user()->id)->where('type_user_id', UserRolesEnum::AGENT->value)->get();
            $distributeur = Distributeur::where("id",$user->first()->distributeur_id)->first();

            $response = Http::withOptions(['verify' => false,])->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$accessToken,
                    'X-Reference-Id'=> $referenceID,
                    'Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyCollection,
                    'X-Target-Environment'=> 'mtncameroon',
                    'X-Callback-Url'=> $this->callbackUrl,
                ])
                ->Post('https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay', [

                    "payeeNote" => "Owner : ".Auth::user()->telephone,
                    "externalId" => $externalId,
                    "amount" => $amount,
                    "currency" => "XAF",
                    "payer" => [
                        "partyIdType" => "MSISDN",
                        "partyId" => $customerPhone
                    ],
                    "payerMessage" => $distributeur->name_distributeur."-".$user->first()->telephone,
                ]);
            if($response->status()==202){
                return response()->json([$response],$response->status());
            }else{
                return response()->json(
                    [
                        'status'=>false,
                        'message'=>"Le traitement de la transaction a été interrompu",
                    ],$response->status()
                );
            }


        }catch (\Exception $e){
            return response()->json(
                [
                    'status'=> false,
                    'messsage'=>  $e->getMessage(),

                ],$e->getCode()
            );
        }

    }
    public function MOMO_CashIn($accessToken, $referenceID, $externalId, $amount, $customerPhone){

        $user = User::where("id",Auth::user()->id)->where('type_user_id', UserRolesEnum::AGENT->value)->get();
        $distributeur = Distributeur::where("id",$user->first()->distributeur_id)->first();

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$accessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyDisbursement,
                'X-Target-Environment'=> 'mtncameroon',
                'X-Callback-Url'=>$this->callbackUrl,
            ])
            ->Post("https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer", [
                "amount" => $amount,
                "currency" => "XAF",
                "externalId" => $externalId,
                "payee" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone,
                ],
                "payerMessage" => $distributeur->name_distributeur."-".$user->first()->telephone,
                "payeeNote" => "Owner : ".Auth::user()->telephone
            ]);
        return response()->json([$response->body()],$response->status());
        if($response->status()==202){
            return response()->json([$response],$response->status());
        }else{
            return response()->json(
                [
                    'status'=>false,
                    'message'=>"Le traitement de la transaction a été interrompu",
                ],$response->status()
            );
        }
    }
    public function MOMO_CashOut($accessToken, $referenceID, $externalId, $amount, $customerPhone){

        $user = User::where("id",Auth::user()->id)->where('type_user_id', UserRolesEnum::AGENT->value)->get();
        $distributeur = Distributeur::where("id",$user->first()->distributeur_id)->first();

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$accessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyCollection,
                'X-Target-Environment'=> 'mtncameroon',
                'X-Callback-Url'=> $this->callbackUrl,
            ])
            ->Post($this->endpoint.'/collection/v1_0/requesttowithdraw', [

                "payeeNote" => "Owner : ".Auth::user()->telephone,
                "externalId" => $externalId,
                "amount" => $amount,
                "currency" => "XAF",
                "payer" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone
                ],
                "payerMessage" => $distributeur->name_distributeur."-".$user->first()->telephone,
            ]);
    }
    public function MOMO_Customer($accessToken, $customerPhone){

        try {
            $http = $this->endpoint."/disbursement/v1_0/accountholder/msisdn/237".$customerPhone."/basicuserinfo";
            $response = Http::withOptions(['verify' => false,])->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$accessToken,
                    'Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyDisbursement,
                    'X-Target-Environment'=> 'mtncameroon',
                    'Accept'=>'application/json',
                ])
                ->Get($http);

            if($response->status()==200){
                $data = json_decode($response->body());
                return response()->json(
                    [
                        'status'=>true,
                        'firstName'=>$data->family_name,
                        'lastName'=>$data->given_name,
                    ],200
                );
            }else{
                return response()->json(
                    [
                        'status'=>false,
                        'message'=>"Le numéro de téléphone n'est pas valide",
                    ],$response->status()
                );
            }
        }catch (\Exception $e){
            return response()->json(
            [
            'status'=> false,
            'messsage'=>  $e->getMessage(),

            ],$e->getCode()
            );
        }

    }
    public function MOMO_CashInStatus($accessToken, $referenceId){

        $http = "https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer/".$referenceId;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$accessToken,
                'Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyDisbursement,
                'X-Target-Environment'=> 'mtncameroon',
            ])
            ->Get($http);

        $data = json_decode($response->body());
        $element = json_decode($response, associative: true);
        if($response->status()==200){
            if($data->status=="SUCCESSFUL"){
                return response()->json(
                    [
                        'status'=>200,
                        'amount'=>$data->amount,
                        'externalId'=>$data->externalId,
                        'message'=>"Terminée avec succès",
                        'description'=>$data->status,
                    ],200
                );
            }

            if($data->status=="CREATED"){
                return response()->json(
                    [
                        'status'=>201,
                        'amount'=>$data->amount,
                        'externalId'=>$data->externalId,
                        'message'=>"Le maximum de dépôt pour ce compte dans la semaine est atteint",
                        'description'=>$data->status,
                    ],201
                );
            }
            if($data->status=="FAILED") {
                if(Arr::has($element, "reason")) {
                    $reason = $data->reason;
                    if ($reason == "NOT_ENOUGH_FUNDS") {
                        return response()->json(
                            [
                                'status' => 404,
                                'amount' => $data->amount,
                                'externalId' => $data->externalId,
                                'message' => "Cette transaction de dépôt MTN ne peut pas aboutir pour l'instant. Veuillez informer votre support.",
                                'description' => $data->status,
                            ], 404
                        );
                    }
                }
            }
            if($data->status=="PENDING"){
                return response()->json(
                    [
                        'status'=>201,
                        'amount'=>$data->amount,
                        'externalId'=>$data->externalId,
                        'message'=>"La transaction est en statut en attente. Veuillez vérifier son statut dans la liste des transactions en attente.",
                        'description'=>$data->status,
                    ],201
                );
            }
            return response()->json(
                [
                    'status'=>404,
                    'amount'=>$data->amount,
                    'externalId'=>$data->externalId,
                    'message'=>"Rassurez vous que le client n'ait pas atteint son nombre de transactions hebdomadaire, sinon consultez votre support technique.",//$data->reason,
                    'description'=>$data->status,
                ],404
            );
        }else{
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$data->message,
                    'description'=>$data->message,
                ],$response->status()
            );
        }
    }
    public function MOMO_CashOutStatus($accessToken, $referenceId){

        try {
            $http = $this->endpoint."/collection/v1_0/requesttowithdraw/".$referenceId;

            $response = Http::withOptions(['verify' => false,])->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$accessToken,
                    'Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyCollection,
                    'X-Target-Environment'=> 'mtncameroon',
                ])->Get($http);

            $data = json_decode($response->body());
            return response()->json(
                [
                    'status'=>200,
                    'data'=>$data,
                ],$response->status()

            );

        }catch(\Exception $e){
            return response()->json(
                [
                    'status'=> 'error',
                    'messsage'=>  $e->getMessage(),

                ],$e->getCode()
            );
        }



    }
    public function MOMO_PaymentStatus($accessToken,$payToken){

        try {
            $http = $this->endpoint."/collection/v1_0/requesttopay/".$payToken;

            $response = Http::withOptions(['verify' => false,])->withHeaders(
                [
                    'Authorization'=> 'Bearer '.$accessToken,
                    'Ocp-Apim-Subscription-Key'=> $this->OcpApimSubscriptionKeyCollection,
                    'X-Target-Environment'=> 'mtncameroon',
                ])->Get($http);

            $data = json_decode($response->body());
            return response()->json(
                [
                    'status'=>$response->status(),
                    'data'=>$data,
                ],$response->status()

            );
        }catch (\Exception $e){
            return response()->json(
                [
                    'status'=> $e->getCode(),
                    'messsage'=>  $e->getMessage(),

                ],$e->getCode()
            );
        }
    }

}
