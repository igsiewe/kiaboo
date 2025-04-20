<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\ApiNotification;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiMoMoMoneyController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
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

        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> '1466a4536a3c476ab18baf82ce82a1f3'])->withBasicAuth('b7159130-4563-46be-ba8b-4c5225345a03', 'dec401d6543f4e00a599a89b0429e57e')
            ->Post('https://proxy.momoapi.mtn.com/disbursement/token/');
        if($response->status()==200){
            return response()->json($response->json());
        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_Depot",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
            ]);
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }

    }

    public function MOMO_Depot_GetTokenAccess(){

        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> '1466a4536a3c476ab18baf82ce82a1f3'])->withBasicAuth('cc0e937a-0b3f-4a69-b2aa-88eb00a05686', '398a7961a06246058d8814c0b1c18337')
            ->Post('https://proxy.momoapi.mtn.com/disbursement/token/');
        if($response->status()==200){
            return response()->json($response->json());
        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_Depot",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
            ]);
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }

    }

    public function MOMO_CustomerName($customerPhone){

        $responseToken = $this->MOMO_Disbursement_GetTokenAccess();
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;

        if($responseToken->status()!=200){
            return response()->json(
                [
                    'status'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }


        $http = "https://proxy.momoapi.mtn.com/disbursement/v1_0/accountholder/msisdn/237".$customerPhone."/basicuserinfo";

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'Ocp-Apim-Subscription-Key'=> '1466a4536a3c476ab18baf82ce82a1f3',
                'X-Target-Environment'=> 'mtncameroon',
                'Accept'=>'application/json',
            ])
            ->Get($http);

        if($response->status()==200){
            $data = json_decode($response->body());
            return response()->json(
                [
                    'status'=>200,
                    'firstName'=>$data->family_name,
                    'lastName'=>$data->given_name,
                    'token'=>$AccessToken,
                ],200
            );
        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_CustomerName",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
                'phone' => $customerPhone,
            ]);

            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>"Le numéro de téléphone n'est pas valide",
                ],$response->status()
            );


        }
    }

    public function MOMO_Depot(Request $request){

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:50|max:500000',
            'deviceId' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $customerNumber = $request->phone;
        $montant = $request->amount;

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::DEPOT_MOMO->value;

        // Vérifie si le service est actif
        if($apiCheck->checkStatusService($service)==false){
            return response()->json([
                'status'=>'error',
                'message'=>"Ce service n'est pas actif",
            ],403);
        }

        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if(!$apiCheck->checkUserValidity()){
            return response()->json([
                'status'=>'error',
                'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
            ],403);
        }

        // Vérifie si le solde de l'utilisateur lui permet d'effectuer cette opération
        if(!$apiCheck->checkUserBalance($montant)){
            return response()->json([
                'status'=>'error',
                'message'=>'Votre solde est insuffisant pour effectuer cette opération',
            ],403);
        }

        //Vérifie si l'utilisateur n'a pas initié une operation similaire dans les 5 dernières minutes

        if($apiCheck->checkFiveLastTransaction($customerNumber, $montant, $service)){
            return response()->json([
                'status'=>'error',
                'message'=>'Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ],403);
        }

        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$montant);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "Impossible de calculer la commission",
            ], 403);
        }

        //Initie la transaction
        $device = $request->deviceId;
        $init_transaction = $apiCheck->init_Depot($montant, $customerNumber, $service, "",$device,"","","",1,Auth::user()->id,"");
        $dataInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>$dataInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataInit->transId; //Id de la transaction initiée
        $reference = $dataInit->reference; //Référence de la transaction initiée

        //On génère le token de la transation
        $responseToken = $this->MOMO_Disbursement_GetTokenAccess();
        if($responseToken->status()!=200){
            return response()->json(
                [
                    'status'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $accessToken = $dataAcessToken->access_token;
        $referenceID = $this->gen_uuid();
        $subcriptionKey = '1466a4536a3c476ab18baf82ce82a1f3';
        $customerPhone = "237".$customerNumber;
        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$accessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> $subcriptionKey,
                'X-Target-Environment'=> 'mtncameroon',
                //'X-Callback-Url'=>'https://kiaboopay.com/callback/cico'
            ])
            ->Post("https://proxy.momoapi.mtn.com/disbursement/v1_0/deposit", [
                "amount" => $montant,
                "currency" => "XAF",
                "externalId" => $idTransaction,
                "payee" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone,
                ],
                "payerMessage" => "Agent :".Auth::user()->telephone,
                "payeeNote" => "Agent : ".Auth::user()->telephone
            ]);

        if($response->status()==202){

            $checkStatus = $this->MOMO_Depot_Status( $accessToken, $subcriptionKey, $referenceID);
            $datacheckStatus = json_decode($checkStatus->getContent());

            if($checkStatus->getStatusCode() !=200){
                return response()->json([
                    'status'=>'error',
                    'message'=>$datacheckStatus->message,
                ],$checkStatus->getStatusCode());
            }
            //$financialTransactionId = $datacheckStatus->financialTransactionId;
            //$dataResponse = json_decode($response->body());
            try {
                DB::beginTransaction();
                //On Calcule la commission
                $commission=json_decode($lacommission->getContent());
                $commissionFiliale = doubleval($commission->commission_kiaboo);
                $commissionDistributeur=doubleval($commission->commission_distributeur);
                $commissionAgent=doubleval($commission->commission_agent);

                $user = User::where('id', Auth::user()->id);
                $balanceBeforeAgent = $user->get()->first()->balance_after;
                $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($montant);
                //on met à jour la table transaction

                $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                    'reference_partenaire'=>$referenceID, //$financialTransactionId,
                    'balance_before'=>$balanceBeforeAgent,
                    'balance_after'=>$balanceAfterAgent,
                    'debit'=>$montant,
                    'credit'=>0,
                    'status'=>1, //End successfully
                    'paytoken'=>$referenceID,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$datacheckStatus->description,
                    'message'=>'Le dépôt a été effectué avec succès',
                    'commission'=>$commission->commission_globale,
                    'commission_filiale'=>$commissionFiliale,
                    'commission_agent'=>$commissionAgent,
                    'commission_distributeur'=>$commissionDistributeur,
                ]);

                //on met à jour le solde de l'utilisateur

                //La commmission de l'agent après chaque transaction

                $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");

                $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                    'balance_after'=>$balanceAfterAgent,
                    'balance_before'=>$balanceBeforeAgent,
                    'last_amount'=>$montant,
                    'date_last_transaction'=>Carbon::now(),
                    'user_last_transaction_id'=>Auth::user()->id,
                    'last_service_id'=>ServiceEnum::DEPOT_OM->value,
                    'reference_last_transaction'=>$reference,
                    'remember_token'=>$referenceID,
                    'total_commission'=>$commission_agent,
                ]);

                DB::commit();

                $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();
                $transactionsRefresh = DB::table('transactions')
                    ->join('services', 'transactions.service_id', '=', 'services.id')
                    ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                    ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
                    ->where("fichier","agent")
                    ->where("source",Auth::user()->id)
                    ->where('transactions.status',1)
                    ->orderBy('transactions.date_transaction', 'desc')
                    ->limit(5)
                    ->get();

                $idDevice = $device;
                $title = "Kiaboo";
                $message = "Le dépôt MOMO de " . $montant . " F CFA a été effectué avec succès au ".$customerNumber;
                $subtitle ="Success";
                $appNotification = new ApiNotification();
                $envoiNotification = $appNotification->SendPushNotificationCallBack($idDevice, $title,  $message);
                if($envoiNotification->status()==200){
                    $resultNotification=json_decode($envoiNotification->getContent());
                    $responseNotification=$resultNotification->response ;
                    if($responseNotification->success==true){
                        Log::info([
                            'code'=> 200,
                            'function' => "MOMO_Depot",
                            'response'=>"Notification envoyée avec succès",
                            'user' => Auth::user()->id,
                            //'request' => $request->all()
                        ]);
                    }else{
                        Log::error([
                            'code'=> 500,
                            'function' => "MOMO_Depot",
                            'response'=>$resultNotification,
                            'user' => Auth::user()->id,
                         //   'request' => $request->all()
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => "SUCCESSFULL", // $resultat->message,
                    'textmessage' =>"Le dépôt a été effectué avec succès", // $resultat->message,
                    'reference' => $reference,// $resultat->data->data->txnid,
                    'data' => [],// $resultat,
                    'user'=>$userRefresh,
                    'transactions'=>$transactionsRefresh,
                //    'transactionsEnAttente'=>$transactionEnAttente,
                ], 200);

            }catch (\Exception $e) {
                DB::rollback();
                Log::error([
                    'code'=> $response->status(),
                    'function' => "MOMO_Depot",
                    'response'=>$e->getMessage(),
                    'user' => Auth::user()->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
                ], 400);
            }

        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_Depot",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
            ]);
            return response()->json(
                [
                    'status'=>$response->status(),
                    'error'=>$response->body(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }
    }

    public function MOMO_Depot_Status($token, $subcriptionKey, $referenceId){

        $http = "https://proxy.momoapi.mtn.com/disbursement/v1_0/deposit/".$referenceId;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$token,
                'Ocp-Apim-Subscription-Key'=> $subcriptionKey,
                'X-Target-Environment'=> 'mtncameroon',
            ])
            ->Get($http);
        $data = json_decode($response->body());
        dd($data);
        if($response->status()==200){
            if($data->status=="SUCCESSFUL"){
                return response()->json(
                    [
                        'status'=>200,
                        'amount'=>$data->amount,
                        'externalId'=>$data->externalId,
                        'message'=>"Terminée avec succès",
                        'description'=>$data->status,
                        // 'financialTransactionId'=>$data->financialTransactionId,
                    ],200
                );
            }
            return response()->json(
                [
                    'status'=>404,
                    'amount'=>$data->amount,
                    'externalId'=>$data->externalId,
                    'message'=>$data->reason,
                    'description'=>$data->status,
                    // 'financialTransactionId'=>$data->financialTransactionId,
                ],404
            );
        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_Depot_Status",
                'response'=>$response,
                'user' => Auth::user()->id,

            ]);
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$data->message,
                ],$response->status()
            );
        }
    }

    public function MOMO_Collection_GetTokenAccess(){
        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c'])->withBasicAuth('b7159130-4563-46be-ba8b-4c5225345a03', 'dec401d6543f4e00a599a89b0429e57e')
            ->Post('https://proxy.momoapi.mtn.com/collection/token/');
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
    
    public function MOMO_Retrait(Request $request){

        $validator = Validator::make($request->all(), [
            'customerPhone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:50|max:500000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::RETRAIT_MOMO->value;

        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if($apiCheck->checkUserValidity()==false){
            return response()->json([
                'status'=>'error',
                'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
            ],401);
        }

        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$request->amount);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "Impossible de calculer la commission",
            ], 403);
        }
        $commission=json_decode($lacommission->getContent());

        $commissionFiliale = doubleval($commission->commission_kiaboo);
        $commissionDistributeur=doubleval($commission->commission_distributeur);
        $commissionAgent=doubleval($commission->commission_agent);

        //Initie la transaction
        $device = $request->deviceId;
        $init_transaction = $apiCheck->init_Retrait($request->amount, $request->customerPhone, $service,"", $device);
        $dataTransactionInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>$dataTransactionInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataTransactionInit->transId; //Id de la transaction initiée
        $reference = $dataTransactionInit->reference; //Référence de la transaction initiée

        //On génère le token de la transation
        $responseToken = $this->MOMO_Collection_GetTokenAccess();
        if($responseToken->status()!=200){
            return response()->json(
                [
                    'status'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;

        //Référence de la transaction
        $referenceID = $this->gen_uuid();
        $customerPhone = "237".$request->customerPhone;
        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c',
                'X-Target-Environment'=> 'mtncameroon',
           //     'X-Callback-Url'=> 'https://allinone.kiaboo.cm/api/momo/retrait/callback/status/'.$referenceID,
            ])
            ->Post('https://proxy.momoapi.mtn.com/collection/v1_0/requesttowithdraw', [

                "payeeNote" => "Transaction initiée par lagent N".Auth::user()->id." le ".Carbon::now()." vers le client ".$request->customerPhone,
                "externalId" => $idTransaction,
                "amount" => $request->amount,
                "currency" => "XAF",
                "payer" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone
                ],
                "payerMessage" => "Transaction initiée par lagent N".Auth::user()->id." le ".Carbon::now()." vers le client ".$request->customerPhone,
            ]);



        if($response->status()==202){
            //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)

            //On change le statut de la transaction dans la base de donnée

            $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                'reference_partenaire'=>$referenceID,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$request->amount,
                'status'=>2, // Pending
                'paytoken'=>$referenceID,
                'date_end_trans'=>Carbon::now(),
                'description'=>'PENDING',
                'message'=>"Transaction initiée par l'agent N°".Auth::user()->id." le ".Carbon::now()." vers le client ".$request->customerPhone." En attente confirmation du client",
                'commission'=>$commission->commission_globale,
                'commission_filiale'=>$commissionFiliale,
                'commission_agent'=>$commissionAgent,
                'commission_distributeur'=>$commissionDistributeur,
            ]);

            //Le solde du compte de l'agent ne sera mis à jour qu'après confirmation de l'agent : Opération traitée dans le callback

            //On recupère toutes les transactions en attente

            return response()->json(
                [
                    'status'=>200,
                    'message'=>"Transaction initiée avec succès. Le client doit confirmer le retrait avec son code secret",

                ],200
            );

        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_Retrait",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
                'request' => $request->all()
            ]);
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }
    }

    public function MOMO_Retrait_CheckStatus($referenceID){

        //On se rassure que la transaction est bien en status en attente
        $Transaction = Transaction::where('paytoken',$referenceID)->where('service_id',ServiceEnum::RETRAIT_MOMO->value)->where('status',2);

        if($Transaction->count()==0){
            return response()->json(
                [
                    'status'=>404,
                    'message'=>"Aucune transaction en attente",

                ],404
            );
        }
        $reference = $Transaction->first()->reference;
        $device_notification = $Transaction->first()->device_notification;
        $customer_phone = $Transaction->first()->customer_phone;
        //On génère le token de la transation
        $responseToken = $this->MOMO_Collection_GetTokenAccess();

        if($responseToken->status()!=200){
            return response()->json(
                [
                    'status'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;

        $http = "https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/".$referenceID;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c',
                'X-Target-Environment'=> 'mtncameroon',
            ])->Get($http);

        $data = json_decode($response->body());

        if($response->status()==200){

            if($data->status=="PENDING"){
               // $reason = json_decode($data->reason);
                return response()->json(
                    [
                        'status'=>202,
                        'message'=>"PENDING - Transaction en attente de confirmation par le client",

                    ],202
                );
            }
            if($data->status=="FAILED"){
                $updateTransaction=$Transaction->update([
                    'status'=>3, // Le client n'a pas validé dans les délai et l'opérateur l'a annule
                    'paytoken'=>$referenceID,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$data->status,
                ]);
                return response()->json(
                    [
                        'status'=>402,
                        'message'=>$data->status." - Le client n'a pas validé la transaction dans les délais et l'opérateur l'a annulé",

                    ],402
                );
            }

            if($data->status=="SUCCESSFUL"){
                $montant = $data->amount;
                $user = User::where('id', Auth::user()->id);
                $balanceBeforeAgent = $user->get()->first()->balance_after;
                $balanceAfterAgent = floatval($balanceBeforeAgent) + floatval($montant);
                $reference_partenaire=$data->financialTransactionId;
                try{
                    DB::beginTransaction();
                    $updateTransaction=$Transaction->update([
                        'balance_before'=>$balanceAfterAgent,
                        'balance_after'=>$balanceAfterAgent,
                        'status'=>1, // Successful
                        'paytoken'=>$referenceID,
                        'date_end_trans'=>Carbon::now(),
                        'description'=>$data->status,
                        'reference_partenaire'=>$reference_partenaire,
                    ]);

                    $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");

                    $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                        'balance_after'=>$balanceAfterAgent,
                        'balance_before'=>$balanceBeforeAgent,
                        'last_amount'=>$montant,
                        'date_last_transaction'=>Carbon::now(),
                        'user_last_transaction_id'=>Auth::user()->id,
                        'last_service_id'=>ServiceEnum::RETRAIT_MOMO->value,
                        'reference_last_transaction'=>$reference,
                        'remember_token'=>$referenceID,
                        'total_commission'=>$commission_agent,
                    ]);
                    $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();
                    $transactionsRefresh = DB::table('transactions')
                        ->join('services', 'transactions.service_id', '=', 'services.id')
                        ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                        ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
                        ->where("fichier","agent")
                        ->where("source",Auth::user()->id)
                        ->where('transactions.status',1)
                        ->orderBy('transactions.date_transaction', 'desc')
                        ->limit(5)
                        ->get();

                    DB::commit();


                    $title = "Kiaboo";
                    $message = "Le retrait MOMO de " . $montant . " F CFA a été effectué avec succès au ".$customer_phone;
                    $subtitle ="Success";
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($device_notification, $title,  $message);
                    if($envoiNotification->status()==200){
                        $resultNotification=json_decode($envoiNotification->getContent());
                        $responseNotification=$resultNotification->response ;
                        if($responseNotification->success==true){
                            Log::info([
                                'code'=> 200,
                                'function' => "MOMO_Retrait_CheckStatus",
                                'response'=>"Notification envoyée avec succès",
                                'user' => Auth::user()->id,
                             //   'request' => $request->all()
                            ]);
                        }else{
                            Log::error([
                                'code'=> 500,
                                'function' => "MOMO_Retrait_CheckStatus",
                                'response'=>$resultNotification,
                                'user' => Auth::user()->id,
                              //  'request' => $request->all()
                            ]);
                        }
                        return response()->json(
                            [
                                'status'=>200,
                                'message'=>$data->status." - Transaction effectuée avec succès",
                                'user'=>$userRefresh,
                                'transactions'=>$transactionsRefresh,

                            ],200
                        );
                    }


                }catch(\Exception $e){
                    DB::rollBack();
                    Log::error([
                        'code'=> $e->getCode(),
                        'function' => "MOMO_Retrait_CheckStatus",
                        'response'=>$e->getMessage(),
                        'user' => Auth::user()->id,
                        'referenceID' => $$referenceID,
                    ]);
                    return response()->json(
                        [
                            'status'=>500,
                            'message'=>"Une erreur est survenue lors de la mise à jour de la transaction",
                        ],500
                    );
                }

            }
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_Retrait_CheckStatus",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
                'referenceID' => $$referenceID,
            ]);
            return response()->json(
                [
                    'status'=>404,
                    'message'=>$response->body(),
                ],404
            );
        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_Depot_Status",
                'response'=>$response,
                'user' => Auth::user()->id,

            ]);
            return response()->json(
                [
                    'error'=>false,
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }
    }

    public function MOMO_Retrait_CallBack($referenceID){

    }

    public function MOMO_Payment_CallBack(Request $request){

        //Implementation de la fonction de callback
       $data = file_get_contents('php://input');

      // $data = file_get_contents('https://allinone.kiaboo.net/api/v1/callback');
       $data = json_decode($data);
        dd($data);
       $financialTransactionId = $data->financialTransactionId;
       $status = $data->externalId;


      //  $status = $request->input('status');
      //  $financialTransactionId = $request->input('financialTransactionId');

        if($status=="SUCCESSFUL"){
            $getLastTransactionId = Transaction::where("id","775724")->update([
                "callback_response"=>"SUCCESSFUL = ".$financialTransactionId
            ]);
            return response()->json(
                [
                    'status'=>200,
                    'message'=>"Transaction effectuée avec succès",
                ],200
            );
        }else{
            $getLastTransactionId = Transaction::where("id","775724")->update([
                "callback_response"=>'FAILLURE = '.$financialTransactionId
            ]);
            return response()->json(
                [
                    'status'=>500,
                    'message'=>"Une erreur est survenue lors de la mise à jour de la transaction",
                ],500
            );
        }

    }

    public function MOMO_transfert(Request $request){
        $validator = Validator::make($request->all(), [
            'customerPhone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:50|max:500000',
        ]);
        //On génère le token de la transation
        $responseToken = $this->MOMO_Depot_GetTokenAccess();
        if($responseToken->status()!=200){
            return response()->json(
                [
                    'status'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $referenceID = $this->gen_uuid();
        $montant = $request->amount;
        $customerNumber = $request->customerPhone;
        $idTransaction=rand(1000,9999);

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> '1466a4536a3c476ab18baf82ce82a1f3',
                'X-Target-Environment'=> 'mtncameroon',
                'X-Callback-Url'=>'https://allinone.kiaboo.net/callback/cico'
            ])

            ->Post('https://proxy.momoapi.mtn.com/disbursement/v2_0/transfer', [
                'amount' => "'.$montant.'",
                'currency' => 'XAF',
                'externalId' => "'.$idTransaction.'",
                'payee' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => '237'.$customerNumber,
                ],
                'payerMessage' => "N".Auth::user()->id,
                'payeeNote' => "N".Auth::user()->id
            ]);

        dd($response);

    }
}
