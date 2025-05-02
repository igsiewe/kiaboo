<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\ApiNotification;
use App\Http\Controllers\ApiLog;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isEmpty;

class ApiProdMoMoMoneyController extends Controller
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

        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> '1466a4536a3c476ab18baf82ce82a1f3'])->withBasicAuth('d51773a3-837d-4dd7-9413-2f82bc3c2de2', 'c22f08082732417ea3ee479820813317')
            ->Post('https://proxy.momoapi.mtn.com/disbursement/token/');
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
            $alerte = new ApiLog();
            $alerte->logError($response->status(), "MOMO_CustomerName", $customerPhone, json_decode($response->body()),"MOMO_CustomerName");

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
            ],401);
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

      // On recupere les charges de services
     //  $idTypeService = Service::where('id',$service)->first()->type_service_id;
     //  $lacharge = $functionCommission->getChargeService($idTypeService,$montant);

        //Initie la transaction
        $device = $request->deviceId;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Depot($montant, $customerNumber, $service, "",$device, $latitude, $longitude, $place,1, Auth::user()->id,"");
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
        //On gardee l'UID de la transaction initiee
        $saveUID = Transaction::where('id',$idTransaction)->update([
            'reference_partenaire'=>$referenceID,
            "paytoken"=>$referenceID
        ]);
        $subcriptionKey = '1466a4536a3c476ab18baf82ce82a1f3';
        $customerPhone = "237".$customerNumber;
        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$accessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> $subcriptionKey,
                'X-Target-Environment'=> 'mtncameroon',
                'X-Callback-Url'=>'https://kiaboopay.com/api/momo/callback'
            ])
            ->Post("https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer", [
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

            $dataRequete = [
                "amount" => $montant,
                "currency" => "XAF",
                "externalId" => $idTransaction,
                "payee" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone,
                ],
                "payerMessage" => "Agent :".Auth::user()->telephone,
                "payeeNote" => "Agent : ".Auth::user()->telephone
            ];

            $saveResponse = Transaction::where('id',$idTransaction)->update([
                'api_response'=>$response->status(),
            ]);

        $alerte = new ApiLog();
        $alerte->logInfo($response->status(), "MOMO_Depot", $dataRequete, json_decode($response->body()),"MOMO_Depot");
        if($response->status()==202){

            $checkStatus = $this->MOMO_Depot_Status( $accessToken, $subcriptionKey, $referenceID);
            $datacheckStatus = json_decode($checkStatus->getContent());

            if($checkStatus->getStatusCode() !=200){
                $updateTransaction=Transaction::where("id",$idTransaction)->update([
                    'status'=>2, // Le dépôt n'a pas abouti, on passe en statut pending
                    //'reference_partenaire'=>$data->financialTransactionId,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$datacheckStatus->description,
                    'message'=>$datacheckStatus->message." - Vérifier le status dans la liste des encours",
                ]);
                $alerte->logError($checkStatus->getStatusCode(), "MOMO_Depot", $dataRequete, $datacheckStatus->message,"MOMO_Depot");
                return response()->json([
                    'status'=>'error',
                    'message'=>$datacheckStatus->message,
                ],$checkStatus->getStatusCode());
            }

            try {

                DB::commit();

               // $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();
                $userRefresh = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                    ->join("villes", "quartiers.ville_id", "=", "villes.id")
                    ->where('users.id', Auth::user()->id)
                    ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

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

                $services = Service::all();
                return response()->json([
                    'success' => true,
                    'message' => "SUCCESSFULL", // $resultat->message,
                    'textmessage' =>"Le dépôt a été effectué avec succès", // $resultat->message,
                    'reference' => $reference,// $resultat->data->data->txnid,
                    'data' => [],// $resultat,
                    'user'=>$userRefresh,
                    'transactions'=>$transactionsRefresh,
                    'services'=>$services,
                ], 200);

            }catch (\Exception $e) {
                DB::rollback();
                $alerte->logError($response->status(), "MOMO_Depot", $dataRequete, $e->getMessage(),"MOMO_Depot");
                return response()->json([
                    'success' => false,
                    'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
                ], $e->getCode());
            }

        }else{

            $alerte->logError($response->status(), "MOMO_Depot", $dataRequete, json_decode($response->body()),"MOMO_Depot");
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

        $http = "https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer/".$referenceId;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$token,
                'Ocp-Apim-Subscription-Key'=> $subcriptionKey,
                'X-Target-Environment'=> 'mtncameroon',
            ])
            ->Get($http);

        $data = json_decode($response->body());
        $element = json_decode($response, associative: true);
        $alerte = new ApiLog();
        $alerte->logInfo($response->status(), "MOMO_Depot_Status", $referenceId, $data,"MOMO_Depot_Status");
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
            //Je convertis en tableau associatif

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
                $alerte->logError($response->status(), "MOMO_Depot_Status", $referenceId, $response->body());
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

            $alerte->logError($response->status(), "MOMO_Depot_Status", $referenceId, $data, "MOMO_Depot_Status");
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$data->message,
                    'description'=>$data->message,
                ],$response->status()
            );
        }
    }

    public function MOMO_Depot_Status_Api($referenceId){

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
        $token = $dataAcessToken->access_token;

        $subcriptionKey = '1466a4536a3c476ab18baf82ce82a1f3';

        $http = "https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer/".$referenceId;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$token,
                'Ocp-Apim-Subscription-Key'=> $subcriptionKey,
                'X-Target-Environment'=> 'mtncameroon',
            ])
            ->Get($http);

        $data = json_decode($response->body());

        $alerte = new ApiLog();
        $alerte->logInfo($response->status(), "MOMO_Depot_Status_Api", $referenceId, $data,"MOMO_Depot_Status_Api");

        if($data==null){

            $alerte->logError($response->status(), "MOMO_Depot_Status_Api", $referenceId, json_decode($response->body()),"MOMO_Depot_Status_Api");
            return response()->json(
                [
                    'status'=>404,
                    'amount'=>0,
                    'externalId'=>$referenceId,
                    'message'=>"La transaction n'existe pas",
                    'description'=>"NOT_FOUND",
                ],404
            );

        }

        $element = json_decode($response, associative: true);
        $externalId = $data->externalId;
        //On se rassure que la transaction est bien en status en attente
        $Transaction = Transaction::where('id',$externalId);
        $financialTransactionId = $Transaction->first()->paytoken;
        if(Arr::has($element, "financialTransactionId")) {
            $financialTransactionId = $data->financialTransactionId;
        }
        $reason=null;
        if(Arr::has($element, "reason")) {
            $reason = $data->reason;
        }
        if($response->status()==200){

            if($data->status=="FAILED"){
                $updateTransaction=$Transaction->update([
                    'status'=>3, // Le dépôt n'a pas abouti
                    'reference_partenaire'=>$financialTransactionId,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$data->status,
                    'message'=>$reason==null?$Transaction->first()->message:$reason,
                    'terminaison'=>'CALLBACK',
                ]);
            }


            if($data->status=="CREATED"){
                return response()->json(
                    [
                        'status'=>201,
                        'amount'=>$data->amount,
                        'externalId'=>$data->externalId,
                        'message'=>"Le maximum de dépôt pour ce compte dans la semaine est atteint.",
                        'description'=>$data->status,
                    ],201
                );
            }
            if(Arr::has($element, "reason")) {
                $reason = $data->reason;
                if($reason=="NOT_ENOUGH_FUNDS"){
                    $alerte->logError($response->status(), "MOMO_Depot_Status_Api", $referenceId, $data);
                    return response()->json(
                        [
                            'status'=>404,
                            'amount'=>$data->amount,
                            'externalId'=>$data->externalId,
                            'message'=>"Le solde du compte chez le partenaire est insuffisant",
                            'description'=>$data->status,
                        ],404
                    );
                }
            }

            if($data->status=="SUCCESSFUL"){
                   $Transaction = Transaction::where('paytoken',$referenceId)->where('service_id',ServiceEnum::DEPOT_MOMO->value);
                   if($Transaction->first()->status ==2) {
                        $idTransaction = $Transaction->first()->id;
                        $service = $Transaction->first()->service_id;
                        $montant = $Transaction->first()->debit;
                        $user = User::where('id', Auth::user()->id);
                        $agent = $user->first()->id;
                        $reference = $Transaction->first()->reference;

                        // On vérifie si les commissions sont paramétrées
                        $functionCommission = new ApiCommissionController();
                        $lacommission = $functionCommission->getCommissionByService($service, $montant);
                        if ($lacommission->getStatusCode() != 200) {
                                                        $alerte->logError($lacommission->getStatusCode(), "MOMO_Depot_Status_Api", null, $lacommission->getContent());
                            return response()->json([
                                'success' => false,
                                'message' => "Impossible de calculer la commission",
                            ], 400);
                        }
                        //On Calcule la commission
                        $commission = json_decode($lacommission->getContent());
                        $commissionFiliale = doubleval($commission->commission_kiaboo);
                        $commissionDistributeur = doubleval($commission->commission_distributeur);
                        $commissionAgent = doubleval($commission->commission_agent);

                        $user = User::where('id', $agent);
                        $balanceBeforeAgent = $user->get()->first()->balance_after;
                        $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($montant);
                        //on met à jour la table transaction

                        $Transaction = Transaction::where('id', $idTransaction)->where('service_id', $service)->update([
                            // 'reference_partenaire'=>$referenceID, //$financialTransactionId,
                            'balance_before' => $balanceBeforeAgent,
                            'balance_after' => $balanceAfterAgent,
                            'debit' => $montant,
                            'credit' => 0,
                            'status' => 1, //End successfully
                            'date_end_trans' => Carbon::now(),
                            'description' => $data->status,
                            'message' => 'Le dépôt a été effectué avec succès',
                            'commission' => $commission->commission_globale,
                            'commission_filiale' => $commissionFiliale,
                            'commission_agent' => $commissionAgent,
                            'commission_distributeur' => $commissionDistributeur,
                            'reference_partenaire' => $data->financialTransactionId,
                            'terminaison' => 'MANUAL',
                        ]);

                        //on met à jour le solde de l'utilisateur
                        //La commmission de l'agent après chaque transaction

                        $commission_agent = Transaction::where("status", 1)->where("fichier", "agent")->where("commission_agent_rembourse", 0)->where("source", $agent)->sum("commission_agent");

                        $debitAgent = DB::table("users")->where("id", $agent)->update([
                            'balance_after' => $balanceAfterAgent,
                            'balance_before' => $balanceBeforeAgent,
                            'last_amount' => $montant,
                            'date_last_transaction' => Carbon::now(),
                            'user_last_transaction_id' => $agent,
                            'last_service_id' => ServiceEnum::DEPOT_OM->value,
                            'reference_last_transaction' => $reference,
                            'remember_token' => $reference,
                            'total_commission' => $commission_agent,
                        ]);
                  }
                    $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email', 'balance_before', 'balance_after', 'total_commission', 'last_amount', 'sous_distributeur_id', 'date_last_transaction')->first();
                    $transactionsRefresh = DB::table('transactions')
                        ->join('services', 'transactions.service_id', '=', 'services.id')
                        ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                        ->select('transactions.id', 'transactions.reference as reference','transactions.paytoken', 'transactions.reference_partenaire', 'transactions.date_transaction', 'transactions.debit', 'transactions.credit', 'transactions.customer_phone', 'transactions.commission_agent as commission', 'transactions.balance_before', 'transactions.balance_after', 'transactions.status', 'transactions.service_id', 'services.name_service', 'services.logo_service', 'type_services.name_type_service', 'type_services.id as type_service_id', 'transactions.date_operation', 'transactions.heure_operation', 'transactions.commission_agent_rembourse as commission_agent')
                        ->where("fichier", "agent")
                        ->where("source", Auth::user()->id)
                        ->where('transactions.status', 1)
                        ->orderBy('transactions.date_transaction', 'desc')
                        ->limit(5)
                        ->get();
                return response()->json(
                    [
                        'status'=>200,
                        'amount'=>$data->amount,
                        'externalId'=>$data->externalId,
                        'message'=>"Terminée avec succès",
                        'description'=>$data->status,
                        'response'=>$data,
                        'user'=>$userRefresh,
                        'transactions'=>$transactionsRefresh,
                        // 'financialTransactionId'=>$data->financialTransactionId,
                    ],200
                );
            }

            $alerte->logError($response->status(), "MOMO_Depot_Status_Api", $data, json_decode($response->body()),"MOMO_Depot_Status_Api");
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

            $alerte->logError($response->status(), "MOMO_Depot_Status_Api", $data, json_decode($response->body()));
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$data->message,
                ],$response->status()
            );
        }
    }

    public function MOMO_Collection_GetTokenAccess(){
        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c'])->withBasicAuth('d51773a3-837d-4dd7-9413-2f82bc3c2de2', 'c22f08082732417ea3ee479820813317')
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

    public function MOMO_Payment_GetTokenAccess(){

        $response = Http::withOptions(['verify' => false,])->withHeaders(['Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c'])->withBasicAuth('748d8c40-bbe9-46e5-9d78-eb646c0de2af', '6ece0272f24745b7bedd0c3406abf3c9')
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
        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::RETRAIT_MOMO->value;

        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if(!$apiCheck->checkUserValidity()){
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
            ], 400);
        }
        $commission=json_decode($lacommission->getContent());

        $commissionFiliale = doubleval($commission->commission_kiaboo);
        $commissionDistributeur=doubleval($commission->commission_distributeur);
        $commissionAgent=doubleval($commission->commission_agent);

        //Initie la transaction
        $device = $request->deviceId;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Retrait($request->amount, $request->customerPhone, $service,"", $device,$latitude,$longitude,$place);

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
        //On gardee l'UID de la transaction initiee
        $saveUID = Transaction::where('id',$idTransaction)->update([
            "paytoken"=>$referenceID
        ]);
        $customerPhone = "237".$request->customerPhone;
        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c',
                'X-Target-Environment'=> 'mtncameroon',
                'X-Callback-Url'=> 'https://kiaboopay.com/api/momo/callback',
            ])
            ->Post('https://proxy.momoapi.mtn.com/collection/v1_0/requesttowithdraw', [

                "payeeNote" => "Transaction initiée par le compte ".Auth::user()->telephone,
                "externalId" => $idTransaction,
                "amount" => $request->amount,
                "currency" => "XAF",
                "payer" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone
                ],
                "payerMessage" => "Transaction initiée par le compte ".Auth::user()->telephone,
            ]);

        $data = [

            "payeeNote" => "Transaction initiée par le compte ".Auth::user()->telephone,
            "externalId" => $idTransaction,
            "amount" => $request->amount,
            "currency" => "XAF",
            "payer" => [
                "partyIdType" => "MSISDN",
                "partyId" => $customerPhone
            ],
            "payerMessage" => "Transaction initiée par le compte ".Auth::user()->telephone,
        ];
        Log::info("Kiaboo",[
            "data"=>$data,
        ]);
        $alerte = new ApiLog();
        $alerte->logInfo($response->status(), "MOMO_Retrait", $data, $response,"MOMO_Retrait");

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
                'message'=>"Transaction initiée par l'agent N°".Auth::user()->telephone,
                'commission'=>$commission->commission_globale,
                'commission_filiale'=>$commissionFiliale,
                'commission_agent'=>$commissionAgent,
                'commission_distributeur'=>$commissionDistributeur,
                'api_response'=>$response->status(),
                'application'=>1,
            ]);

            //Le solde du compte de l'agent ne sera mis à jour qu'après confirmation de l'agent : Opération traitée dans le callback

            //On recupère toutes les transactions en attente

            return response()->json(
                [
                    'status'=>200,
                    'message'=>"Transaction initiée avec succès. Le client doit confirmer le retrait avec son code secret",
                    'paytoken'=>$referenceID,
                ],200
            );

        }else{
            $alerte = new ApiLog();
            $alerte->logError($response->status(), "MOMO_Retrait", $data, $response,"MOMO_Retrait");

            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }
    }

    public function MOMO_Retrait_Status($referenceID){

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

        $http = "https://proxy.momoapi.mtn.com/collection/v1_0/requesttowithdraw/".$referenceID;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c',
                'X-Target-Environment'=> 'mtncameroon',
            ])->Get($http);

        $data = json_decode($response->body());
        $alerte = new ApiLog();
        $alerte->logInfo($response->status(), "MOMO_Retrait_Status", $referenceID, $data,"MOMO_Retrait_Status");

        if($response->status()==200){

            if($data->status=="PENDING"){
               // $reason = json_decode($data->reason);
                return response()->json(
                    [
                        'status'=>202,
                        'message'=>"PENDING - Transaction en attente de confirmation par le client",
                        'data'=>$data,
                    ],202
                );
            }
            if($data->status=="FAILED"){
                $updateTransaction=$Transaction->update([
                    'status'=>3, // Le client n'a pas validé dans les délais et l'opérateur l'a annule
                    'paytoken'=>$referenceID,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$data->status,
                    'terminaison'=>'MANUAL',
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
                        'balance_before'=>$balanceBeforeAgent,
                        'balance_after'=>$balanceAfterAgent,
                        'status'=>1, // Successful
                        'paytoken'=>$referenceID,
                        'date_end_trans'=>Carbon::now(),
                        'description'=>$data->status,
                        'reference_partenaire'=>$reference_partenaire,
                        'terminaison'=>'MANUAL',
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

                    return response()->json(
                        [
                            'status'=>200,
                            'message'=>$data->status." - Transaction en succès",
                            'user'=>$userRefresh,
                            'transactions'=>$transactionsRefresh,
                            'response'=>$data
                        ],200
                    );
                }catch(\Exception $e){
                    DB::rollBack();

                    $alerte->logError($response->status(), "MOMO_Retrait_Status", $referenceID, $e->getMessage(),"MOMO_Retrait_Status");

                    return response()->json(
                        [
                            'status'=>500,
                            'message'=>"Une erreur est survenue lors de la mise à jour de la transaction",
                        ],500
                    );
                }

            }

            $alerte->logError($response->status(), "MOMO_Retrait_Status", $referenceID, json_decode($response->body()),"MOMO_Retrait_Status");

            return response()->json(
                [
                    'status'=>404,
                    'message'=>$response->body(),
                ],404
            );
        }else{

            $alerte->logError($response->status(), "MOMO_Retrait_Status", $referenceID, json_decode($response->body()),"MOMO_Retrait_Status");

            return response()->json(
                [
                    'error'=>false,
                    'status'=>$response->status(),
                    'message'=>'Ressource introuvable',
                ],$response->status()
            );
        }
    }

    public function MOMO_Retrait_Status_Api($referenceID){

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

        $http = "https://proxy.momoapi.mtn.com/collection/v1_0/requesttowithdraw/".$referenceID;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c',
                'X-Target-Environment'=> 'mtncameroon',
            ])->Get($http);

        $data = json_decode($response->body());
        $alerte = new ApiLog();
        $alerte->logInfo($response->status(), "MOMO_Retrait_Status_Api", $referenceID, $data,"MOMO_Retrait_Status_Api");

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
                return response()->json(
                    [
                        'status'=>402,
                        'message'=>$data->status." - Le client n'a pas validé la transaction dans les délais et l'opérateur l'a annulé",

                    ],402
                );
            }
            if($data->status=="SUCCESSFUL"){

                    return response()->json(
                        [
                            'status'=>200,
                            'message'=>$data->status." - Transaction en succès",
                            'response'=>$data
                        ],200
                    );
             }

        }else{
            $alerte = new ApiLog();
            $alerte->logError($response->status(), "MOMO_Retrait_Status_Api", $referenceID, json_decode($response->body()),"MOMO_Retrait_Status_Api");
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body()

                ],404
            );
        }

    }

    public function MomoCallBack(Request $request){
        header("Content-Type: application/json");
        $momocallBackResponse = file_get_contents('php://input');
        $data = json_decode($momocallBackResponse);
        $element = json_decode($momocallBackResponse, associative: true);
        Log::info("MoMoCallBack", [
            'data' => $data,
        ]);
        $externalId = $data->externalId;
        //On se rassure que la transaction est bien en status en attente
        $Transaction = Transaction::where('id',$externalId);
        $customer_phone = $Transaction->first()->customer_phone;
        $dateTransaction = $Transaction->first()->date_transaction;
        $device_notification = $Transaction->first()->device_notification;
        if($Transaction->count()>0){
            $status = $Transaction->first()->status;
            if(($Transaction->first()->service_id ==ServiceEnum::RETRAIT_MOMO->value || $Transaction->first()->service_id ==ServiceEnum::PAYMENT_MOMO->value) && $status==2){
                $financialTransactionId = $Transaction->first()->paytoken;
                if(Arr::has($element, "financialTransactionId")) {
                    $financialTransactionId = $data->financialTransactionId;
                }
                if($data->status=="FAILED"){
                    $updateTransaction=$Transaction->update([
                        'status'=>3, // Le client n'a pas validé dans les délais et l'opérateur l'a annule
                        'reference_partenaire'=>$financialTransactionId,
                        'date_end_trans'=>Carbon::now(),
                        'description'=>$data->status,
                        'message'=>$data->reason,
                        'terminaison'=>'CALLBACK',

                    ]);
                }
                if($data->status=="SUCCESSFUL"){
                    $montant = $data->amount;
                    $user = User::where('id', $Transaction->first()->created_by);
                    $balanceBeforeAgent = $user->get()->first()->balance_after;
                    $balanceAfterAgent = floatval($balanceBeforeAgent) + floatval($montant);
                    $reference_partenaire=$data->financialTransactionId;
                    $agent = $user->first()->id;
                    $reference = $Transaction->first()->reference;
                    try{
                        DB::beginTransaction();
                        $updateTransaction=$Transaction->update([
                            'balance_before'=>$balanceBeforeAgent,
                            'balance_after'=>$balanceAfterAgent,
                            'status'=>1, // Successful
                            'date_end_trans'=>Carbon::now(),
                            'description'=>$data->status,
                            'reference_partenaire'=>$reference_partenaire,
                            'terminaison'=>'CALLBACK',
                            'callback_response'=>$data,
                        ]);

                        $commission_agent = Transaction::where("status",1)->where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",$agent)->sum("commission_agent");
                        $debitAgent = DB::table("users")->where("id", $agent)->update([
                            'balance_after'=>$balanceAfterAgent,
                            'balance_before'=>$balanceBeforeAgent,
                            'last_amount'=>$montant,
                            'date_last_transaction'=>Carbon::now(),
                            'user_last_transaction_id'=>$agent,
                            'last_service_id'=>ServiceEnum::RETRAIT_MOMO->value,
                            'reference_last_transaction'=>$reference_partenaire,
                            'remember_token'=>$reference,
                            'total_commission'=>$commission_agent,
                        ]);

                        $title = "Kiaboo";
                        $message = "Le retrait MoMo de " . $montant . " F CFA a été effectué avec succès au ".$customer_phone." (ID : ".$reference_partenaire.") le ".$dateTransaction;
                        $appNotification = new ApiNotification();
                        $envoiNotification = $appNotification->SendPushNotificationCallBack($device_notification, $title, $message);

                        DB::commit();

                    }catch(\Exception $e){
                        DB::rollBack();
                        Log::error(
                            'MOMO CallBack',
                            [
                                'error' => $e->getMessage(),
                                'transaction_id' => $Transaction->first()->id,
                                'data' => $data,
                            ]
                        );

                    }

                }
            }

            if($Transaction->first()->service_id ==ServiceEnum::DEPOT_MOMO->value){

                $financialTransactionId = $Transaction->first()->paytoken;
                if(Arr::has($element, "financialTransactionId")) {
                    $financialTransactionId = $data->financialTransactionId;
                }
                $reason=null;
                if(Arr::has($element, "reason")) {
                    $reason = $data->reason;
                }
                if($data->status=="FAILED"){
                    $updateTransaction=$Transaction->update([
                        'status'=>3, // Le dépôt n'a pas abouti
                        'reference_partenaire'=>$financialTransactionId,
                        'date_end_trans'=>Carbon::now(),
                        'description'=>$data->status,
                        'message'=>$reason==null?$Transaction->first()->message:$reason,
                        'terminaison'=>'CALLBACK',

                    ]);
                }
                if($data->status=="CREATED"){
                    $updateTransaction=$Transaction->update([
                        'status'=>3, // Le dépôt n'a pas abouti
                        'reference_partenaire'=>$financialTransactionId,
                        'date_end_trans'=>Carbon::now(),
                        'description'=>$data->status,
                        'message'=>$reason==null?$Transaction->first()->message:$reason,
                        'terminaison'=>'CALLBACK',
                    ]);
                }
                if($data->status=="SUCCESSFUL"){

                    $idTransaction = $Transaction->first()->id;
                    $service = $Transaction->first()->service_id;
                    $montant = $data->amount;
                    $user = User::where('id', $Transaction->first()->created_by);

                    $agent = $user->first()->id;
                    $reference = $Transaction->first()->reference;


                    // On vérifie si les commissions sont paramétrées
                    $functionCommission = new ApiCommissionController();
                    $lacommission =$functionCommission->getCommissionByService($service,$montant);
                    if($lacommission->getStatusCode()!=200){
                        $alerte = new ApiLog();
                        $alerte->logError($lacommission->getStatusCode(), "MoMoCallBack", null, $lacommission->getContent(),"getCommissionByService");

                    }
                    //On Calcule la commission
                    $commission=json_decode($lacommission->getContent());
                    $commissionFiliale = doubleval($commission->commission_kiaboo);
                    $commissionDistributeur=doubleval($commission->commission_distributeur);
                    $commissionAgent=doubleval($commission->commission_agent);
                    $reference_partenaire = $data->financialTransactionId;
                    $user = User::where('id', $agent);
                    $balanceBeforeAgent = $user->get()->first()->balance_after;
                    $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($montant);
                    //on met à jour la table transaction

                    $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                        // 'reference_partenaire'=>$referenceID, //$financialTransactionId,
                        'balance_before'=>$balanceBeforeAgent,
                        'balance_after'=>$balanceAfterAgent,
                        'debit'=>$montant,
                        'credit'=>0,
                        'status'=>1, //End successfully
                       // 'paytoken'=>$referenceID,
                        'date_end_trans'=>Carbon::now(),
                        'description'=>$data->status,
                        'message'=>'Le dépôt a été effectué avec succès',
                        'commission'=>$commission->commission_globale,
                        'commission_filiale'=>$commissionFiliale,
                        'commission_agent'=>$commissionAgent,
                        'commission_distributeur'=>$commissionDistributeur,
                        'reference_partenaire'=>$data->financialTransactionId,
                        'terminaison'=>'CALLBACK',
                        'callback_response'=> $data

                    ]);

                    //on met à jour le solde de l'utilisateur

                    //La commmission de l'agent après chaque transaction

                    $commission_agent = Transaction::where("status",1)->where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",$agent)->sum("commission_agent");

                    $debitAgent = DB::table("users")->where("id", $agent)->update([
                        'balance_after'=>$balanceAfterAgent,
                        'balance_before'=>$balanceBeforeAgent,
                        'last_amount'=>$montant,
                        'date_last_transaction'=>Carbon::now(),
                        'user_last_transaction_id'=>$agent,
                        'last_service_id'=>ServiceEnum::DEPOT_OM->value,
                        'reference_last_transaction'=>$reference,
                        'remember_token'=>$reference,
                        'total_commission'=>$commission_agent,
                    ]);
                    DB::commit();
                    $title = "Kiaboo";
                    $message = "Le dépôt MoMo de " . $montant . " F CFA a été effectué avec succès au ".$customer_phone." (ID : ".$reference_partenaire.") le ".$dateTransaction;
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($device_notification, $title, $message);

                }
            }
        }
    }


    /**
     * @OA\Post(
     * path="/api/v1/prod/momo/payment",
     * summary="Request to make a payment MOMO",
     * description="This operation is used to request a payment from a consumer (Payer). The payer will be asked to authorize the payment. The transaction will be executed once the payer has authorized the payment. The requesttopay will be in status PENDING until the transaction is authorized or declined by the payer or it is timed out by the system. Status of the transaction can be validated by using the GET api/v1/prod/momo/payment/<resourceId>",
     * security={{"bearerAuth":{}}},
     * tags={"Merchant payment"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Request to make a payment MOMO",
     *    @OA\JsonContent(
     *       required={"agentNumber","marchandTransactionId","phone","amount"},
     *       @OA\Property(property="agentNumber", type="string", example="679962015"),
     *       @OA\Property(property="marchandTransactionId", type="string", example="12354"),
     *       @OA\Property(
     *           type="object",
     *           property="data",
     *           @OA\Property(property="phone", type="number", example="679962015"),
     *           @OA\Property(property="amount", type="number", example="200"),
     *       )
     *    ),
     * ),
     * @OA\Response(
     *    response=400,
     *    description="Bad request",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="statusCode", type="string", example="ERR-INVALID-DATA-SEND"),
     *       @OA\Property(property="message", type="string", example="Bad request, invalid data was sent in the request"),
     *    )
     * ),
     * @OA\Response(
     *      response=403,
     *      description="you do not have the necessary permissions",
     *      @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example="false"),
     *         @OA\Property(property="statusCode", type="string", example="ERR-NOT-PERMISSION"),
     *         @OA\Property(property="message", type="string", example="you do not have the necessary permissions"),
     *      )
     * ),
     * @OA\Response(
     *     response=422,
     *     description="attribute invalid",
     *     @OA\JsonContent(
     *        @OA\Property(property="success", type="boolean", example="false"),
     *        @OA\Property(property="statusCode", type="string", example="ERR-ATTRIBUTES-INVALID"),
     *        @OA\Property(property="message", type="string", example="attribute not valid"),
     *     )
     *  ),
     * @OA\Response(
     *    response=202,
     *    description="Payment initiated successfully",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="true"),
     *       @OA\Property(property="statusCode", type="string", example="PAYMENT INITIATED"),
     *       @OA\Property(property="message", type="string", example="payment initiate successfully"),
     *      @OA\Property(property="paytoken", type="string", example="Payment token"),
     *     @OA\Property(property="transactionId", type="string", example="Reference transaction for any request"),
     *    ),
     * ),
     * @OA\Response(
     *        response=208,
     *        description="you do not have the necessary permissions",
     *        @OA\JsonContent(
     *           @OA\Property(property="success", type="boolean", example="false"),
     *           @OA\Property(property="statusCode", type="string", example="ERR-MERCHAND-TRANSACTION-ID-DUPLICATE"),
     *           @OA\Property(property="message", type="string", example="The transaction ID used by the merchant already exists"),
     *            @OA\Property(
     *            type="object",
     *            property="data",
     *            @OA\Property(property="status", type="string", example="Transaction status"),
     *            @OA\Property(property="transactionId", type="string", example="transacton id database"),
     *            @OA\Property(property="dateTransaction", type="date", example="Date transaction"),
     *            @OA\Property(property="amount", type="number", example="amount of transaction"),
     *            @OA\Property(property="fees", type="number", example="transaction fees"),
     *            @OA\Property(property="agent", type="string", example="agent who initiate transaction"),
     *            @OA\Property(property="customer", type="number", example="customer phone number"),
     *            @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
     *            )
     *        )
     *   ),
     * @OA\Response(
     *    response=500,
     *    description="an error occurred",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
     *       @OA\Property(property="message", type="string", example="an error occurred"),
     *    )
     *  )
     * )
     */
    public function MOMO_Payment(Request $request){

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::PAYMENT_MOMO->value;
        $user = User::where("telephone",$request->agentNumber)->where('type_user_id', UserRolesEnum::AGENT->value)->get();
        $amount=$request->amount;
        $customer=$request->phone;

      //  $amount=$request->data["amount"];
      //  $customer=$request->data["phone"];

        // Vérifie si l'utilisateur est autorisé à faire cette opération

        if($user->count()==0){
            return response()->json([
                'success'=>false,
                'statusCode'=>'ERR-AGENT-NOT-VALID',
                'message'=>"The agent used is not found",
            ],404);
        }

        if($user->first()->status ==0){
            return response()->json([
                'success'=>false,
                'statusCode'=>'ERR-NOT-PERMISSION',
                'message'=>"The agent used does not have the necessary permissions",
            ],403);
        }

        //On se rassure que l'utilisateur est bien rattaché au compte connecté

        if($user->first()->distributeur_id !=Auth::user()->distributeur_id){
           // if($user->count()==0 || $user->first()->status ==0){
                return response()->json([
                    'success'=>false,
                    'statusCode'=>'ERR-NOT-PERMISSION',
                    'message'=>"The agent used does not have the necessary permissions with your profil",
                ],403);
           // }
        }

        //Verifie le statut de l'id transaction cote marchand
        $distributeurAuquelAppartienAgent = $user->first()->distributeur_id;

        $checkTransactionExternalId = DB::table('transactions')
            ->join('users', 'transactions.source', '=', 'users.id')
            ->select('transactions.*')
            ->where('transactions.marchand_transaction_id', $request->marchandTransactionId)
            ->where('users.distributeur_id', $distributeurAuquelAppartienAgent)
            ->get();

        if($checkTransactionExternalId->count()>0){
            return response()->json([
                'success'=>false,
                'statusCode'=>"ERR-MERCHAND-TRANSACTION-ID-DUPLICATE",
                'message' => "The merchand transaction ID used already exists: ".$request->marchandTransactionId,
                'data'=>[
                    'status' => $checkTransactionExternalId->first()->description,
                    'transactionId'=>$checkTransactionExternalId->first()->reference,
                    'dateTransaction'=>$checkTransactionExternalId->first()->date_transaction,
                    'amount'=>$checkTransactionExternalId->first()->credit,
                    'fees'=>$checkTransactionExternalId->first()->fees_collecte,
                    'agent'=>$user->first()->telephone,
                    'customer'=>$checkTransactionExternalId->first()->customer_phone,
                    'marchandTransactionID'=>$checkTransactionExternalId->first()->marchand_transaction_id,
                ]
            ], 208);
        }


        // On vérifie si les commissions sont paramétrées
        $functionFees = new ApiCommissionController();
        $lesFees =$functionFees->getFeesByService($service,$amount);

        if($lesFees->getStatusCode()!=200){
            return response()->json([
                'success'=>false,
                'statusCode' => "ERR-FEES-INVALID",
                'message' => "Impossible de calculer les frais liés à la transaction",
            ], 400);
        }
        $fees=json_decode($lesFees->getContent());

        //Initie la transaction

        $init_transaction = $apiCheck->init_Payment($amount, $customer, $service,"",$user->first()->id,"2");
        $dataTransactionInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'success'=>false,
                'statusCode'=>'error',
                'message'=>$dataTransactionInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataTransactionInit->transId; //Id de la transaction initiée
        $reference = $dataTransactionInit->reference; //Référence de la transaction initiée
        //On génère le token de la transation
       // $responseToken = $this->MOMO_Collection_GetTokenAccess();
        $responseToken = $this->MOMO_Payment_GetTokenAccess();
        if($responseToken->status()!=200){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;

        //Référence de la transaction
        $referenceID = $this->gen_uuid();
        //On gardee l'UID de la transaction initiee
        $saveUID = Transaction::where('id',$idTransaction)->update([
            "paytoken"=>$referenceID
        ]);
        $customerPhone = "237".$customer;
        $partenaire = Distributeur::where("id",Auth::user()->distributeur_id)->get()->first()->name_distributeur;
        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'X-Reference-Id'=> $referenceID,
                'Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c',
                'X-Target-Environment'=> 'mtncameroon',
                'X-Callback-Url'=> 'https://kiaboopay.com/api/momo/callback',
            ])
            ->Post('https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay', [

                "payeeNote" => "Agent ".$user->first()->telephone." Partenaire ".strtoupper($partenaire),
                "externalId" => $idTransaction,
                "amount" => $amount,
                "currency" => "XAF",
                "payer" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone
                ],
                "payerMessage" => "Agent ".$user->first()->telephone." Partenaire ".strtoupper($partenaire),
            ]);


        Log::info([
            "Service"=>ServiceEnum::PAYMENT_MOMO->name,
            "url"=>"https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay",
            "requete"=>[
                "payeeNote" => "Agent ".$user->first()->telephone." Partenaire ".strtoupper($partenaire),
                "externalId" => $idTransaction,
                "amount" => $amount,
                "currency" => "XAF",
                "payer" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone
                ],
                "payerMessage" => "Agent ".$user->first()->telephone." Partenaire ".strtoupper($partenaire),
            ],
            "marchandTransactionID"=>$request->marchandTransactionId,
            "reponse"=>json_decode($response->status()),
            "body"=>json_decode($response->body()),
        ]);


        if($response->status()==202){
            //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)

            //On change le statut de la transaction dans la base de donnée

            $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                'reference_partenaire'=>$referenceID,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$amount,
                'status'=>2, // Pending
                'paytoken'=>$referenceID,
                'date_end_trans'=>Carbon::now(),
                'description'=>'PENDING',
                'message'=>"Transaction initiée par l'agent N°".$user->first()->id." le ".Carbon::now()." vers le client ".$customerPhone." En attente confirmation du client",
                'fees_collecte'=>$fees->fees_globale,
                'fees_partenaire_service'=>$fees->fees_partenaire_service,
                'fees_kiaboo'=>$fees->fees_kiaboo,
                'marchand_amount'=>doubleval($amount)-doubleval($fees->fees_globale),
                'commission'=>0,//$commission->commission_globale,
                'commission_filiale'=>0,//$commissionFiliale,
                'commission_agent'=>0,//$commissionAgent,
                'commission_distributeur'=>0,//$commissionDistributeur,
                'marchand_transaction_id'=>$request->marchandTransactionId,
            ]);

            //Le solde du compte de l'agent ne sera mis à jour qu'après confirmation de l'agent : Opération traitée dans le callback

            //On recupère toutes les transactions en attente

            return response()->json(
                [
                    'success'=>true,
                    'statusCode'=>"PAYMENT-INITIATE-SUCCESSFULLY",
                    'message'=>"Transaction initiée avec succès. Le client doit confirmer le retrait avec son code secret",
                    'paytoken'=>$referenceID,
                    'transactionId'=>$reference,//$idTransaction,
                ],202
            );

        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "MOMO_PAYMENT",
                'response'=>$response->body(),
                'user' => $user->first()->id,
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

    /**
     * @OA\Get (
     * path="/api/v1/prod/momo/status/{transactionId}",
     * summary="Check MoMo transaction status",
     * description="This operation is used to get the status of a request to momo pay. TransactionId that was passed in the post is used as reference to the request",
     * tags={"Merchant payment"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="transactionId",
     *     description="reference of transaction",
     *     required=true,
     *     in="path",
     *     @OA\Schema(
     *        type="string"
     *     )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Transaction found",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="true"),
     *       @OA\Property(property="statusCode", type="string", example="SUCCESSFULL"),
     *       @OA\Property(property="message", type="string", example="Transaction found"),
     *       @OA\Property(
     *             type="object",
     *             property="data",
     *             @OA\Property(property="status", type="string", example="Transaction status"),
     *             @OA\Property(property="transactionId", type="string", example="transacton id database"),
     *             @OA\Property(property="dateTransaction", type="date", example="Date transaction"),
     *             @OA\Property(property="currency", type="number", example="XAF"),
     *             @OA\Property(property="amount", type="number", example="amount of transaction"),
     *             @OA\Property(property="fees", type="number", example="transaction fees"),
     *             @OA\Property(property="agent", type="string", example="agent who initiate transaction"),
     *             @OA\Property(property="customer", type="number", example="customer phone number"),
     *             @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
     *       )
     *    )
     * ),
     * @OA\Response(
     *     response=202,
     *     description="Transaction pending",
     *     @OA\JsonContent(
     *        @OA\Property(property="success", type="boolean", example="true"),
     *        @OA\Property(property="statusCode", type="string", example="PENDING"),
     *        @OA\Property(property="message", type="string", example="Transaction pending"),
     *        @OA\Property(
     *             type="object",
     *             property="data",
     *             @OA\Property(property="status", type="string", example="Transaction status"),
     *             @OA\Property(property="transactionId", type="string", example="transacton id database"),
     *             @OA\Property(property="dateTransaction", type="date", example="Date transaction"),
     *             @OA\Property(property="currency", type="number", example="XAF"),
     *             @OA\Property(property="amount", type="number", example="amount of transaction"),
     *             @OA\Property(property="fees", type="number", example="transaction fees"),
     *             @OA\Property(property="agent", type="string", example="agent who initiate transaction"),
     *             @OA\Property(property="customer", type="number", example="customer phone number"),
     *             @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
     *        )
     *     )
     *  ),
     *     @OA\Response(
     *      response=402,
     *      description="Transaction failed",
     *      @OA\JsonContent(
     *         @OA\Property(property="false", type="boolean", example="true"),
     *         @OA\Property(property="statusCode", type="string", example="FAILED"),
     *         @OA\Property(property="message", type="string", example="Transaction failed"),
     *      )
     *   ),
     *  @OA\Response(
     *       response=403,
     *       description="you do not have the necessary permissions",
     *       @OA\JsonContent(
     *          @OA\Property(property="success", type="boolean", example="false"),
     *          @OA\Property(property="statusCode", type="string", example="ERR-NOT-PERMISSION"),
     *          @OA\Property(property="message", type="string", example="you do not have the necessary permissions"),
     *       )
     *  ),
     * @OA\Response(
     *    response=404,
     *    description="transaction not found ",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="statusCode", type="string", example="ERR-TRANSACTION-NOT-FOUND"),
     *       @OA\Property(property="message", type="string", example="Transaction not found "),
     *    )
     *  ),
     * @OA\Response(
     *    response=500,
     *    description="an error occurred",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
     *       @OA\Property(property="message", type="string", example="an error occurred"),
     *    )
     *  ),
     * )
     * )
     */

    public function MOMO_Payment_Status($transactionId){
        // On cherche la transaction dans la table transaction

        $transaction = Transaction::where("reference", $transactionId)->where('service_id',ServiceEnum::PAYMENT_MOMO->value)->get();
        if($transaction->count()==0){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>"ERR-TRANSACTION-NOT-FOUND",
                    'message'=>"This id transaction does not exist"
                ],404
            );
        }

        $service = Service::where("id",$transaction->first()->service_id)->get();

        if($service->first()->type_service_id !=TypeServiceEnum::PAYMENT->value){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>"ERR-TRANSACTION-NOT-FOUND",
                    'message'=>"This id transaction does not exist"
                ],404
            );
        }

        $distributeur = User::where("id", $transaction->first()->source)->get()->first()->distributeur_id;

        if(Auth::user()->distributeur_id !=$distributeur){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>'ERR-NOT-PERMISSION',
                    'message'=>"You are not authorized to view this transaction. It does not belong to you.",
                ],403
            );
        }

        //On génère le token de la transation
        $responseToken = $this->MOMO_Payment_GetTokenAccess();

        if($responseToken->status()!=200){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $payToken = $transaction->first()->paytoken;
        $http = "https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/".$payToken;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=> 'Bearer '.$AccessToken,
                'Ocp-Apim-Subscription-Key'=> '886cc9e141ab492f80d9567b3c46d59c',
                'X-Target-Environment'=> 'mtncameroon',
            ])->Get($http);

        $data = json_decode($response->body());

        if($response->status()==200){

            if($data->status=="SUCCESSFUL"){

                return response()->json(
                    [
                        'success'=>true,
                        'statusCode'=>$data->status,
                        'message'=>'Transaction terminée avec succès',
                        'data'=>[
                            'currency'=>'XAF',
                            'transactionId'=>$transactionId,
                            'dateTransaction'=>$transaction->first()->date_transaction,
                            'amount'=>$transaction->first()->credit,
                            'fees'=>$transaction->first()->fees_collecte,
                            'collect'=>$transaction->first()->marchand_amount,
                            'agent'=>User::where("id", $transaction->first()->source)->first()->telephone,
                            'customer'=>$transaction->first()->customer_phone,
                        ]
                    ],200
                );
            }

            if($data->status=="PENDING"){
                // $reason = json_decode($data->reason);
                return response()->json(
                    [
                        'success'=>true,
                        'statusCode'=>'PENDING',
                        'message'=>"PENDING - Transaction en attente de confirmation par le client",
                        'data'=>[
                            'transactionId'=>$transactionId,
                            'dateTransaction'=>$transaction->first()->date_transaction,
                            'currency'=>'XAF',
                            'amount'=>$transaction->first()->credit,
                            'fees'=>$transaction->first()->fees_collecte,
                            'agent'=>User::where("id", $transaction->first()->source)->first()->telephone,
                            'customer'=>$transaction->first()->customer_phone,
                        ]

                    ],202
                );
            }
            if($data->status=="FAILED"){
                return response()->json(
                    [
                        'success'=>false,
                        'statusCode'=>'FAILED',
                        'message'=>$data->status." - Le client n'a pas validé la transaction dans les délais et l'opérateur l'a annulé",

                    ],402
                );
            }


        }else{
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$response->status(),
                    'message'=>$response->body()

                ],404
            );
        }

    }
}
