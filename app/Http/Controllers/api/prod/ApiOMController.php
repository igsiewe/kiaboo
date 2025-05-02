<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\ApiNotification;
use App\Http\Controllers\ApiLog;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiOMController extends Controller
{
    protected $token;
    protected $auth;
    protected $auth_x_token;
    protected $channel;
    protected $pin;
    protected $url;
    protected $callbackUrl;

    public function __construct()
    {
        $this->endpoint="https://api-s1.orange.cm/omcoreapis/1.0.2";
        $this->callbackUrl="https://www.kiaboopay.com/api/callback/om/cico";
        $this->auth="RllkYW1SbVAwWWNSUTlNbHRvdkd2NFBjTjlNYTpiTVdoeXFSNUtfZExOZ2ZRaHUzdmh0aV9ZZEFh"; //consumer_key et consumer_secret convertis en base64
        $this->auth_x_token ="S0lBQk9PU0FSTEFQSU9NUFJPRDI1OktJQEJfT1NBUkxAUCFPbV9QUk9fZDIwMjU=";//api_username et api_password convertis en base64
        $this->channel="691566672";
        $this->pin="2025";
//        $getTokenResponse = $this->OM_GetTokenAccess();
//
//        if($getTokenResponse->status()==200){
//            $dataToken = json_decode($getTokenResponse->content());
//            $this->token = $dataToken->access_token;
//        }
    }
    public function OM_GetTokenAccess()
    {
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    "Authorization"=>"Basic ".$this->auth
                ]
            )
            ->withBody('grant_type=client_credentials', 'application/x-www-form-urlencoded')
            ->Post('https://api-s1.orange.cm/token');

        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            $alerte = new ApiLog();
            $alerte->logError($response->status(), "OM_GetTokenAccess", null, json_decode($response->body()));
            return response()->json([
                'status'=>'error',
                'message'=>$response->body()
            ],$response->status());

        }

    }

    public function OM_CustomerName($customerNumber)
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
            $endpoint = $this->endpoint.'/infos/subscriber/customer/'.$customerNumber;
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=>$this->auth_x_token,
                        'Authorization'=>'Bearer '.$token
                    ])
                ->Post($endpoint, [
                    "pin"=> $this->pin,
                    "channelMsisdn"=> $this->channel
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
            }else{
                $alerte = new ApiLog();
                $alerte->logError($response->status(), "OM_CustomerName", null, json_decode($response->body()));
                $body = json_decode($response->body());
                return response()->json([
                    'code' => $response->status(),
                    'message'=>"Exception ".$response->status()."\n".$body->message
                ],$response->status());
            }
        }catch (\Exception $e){
            $alerte = new ApiLog();
            $alerte->logError($e->getCode(), "OM_CustomerName", null, $e->getMessage());
            return response()->json([
                //  'code' => $e->getCode(),
                'message'=>$e->getMessage()
            ],$e->getCode());
        }

    }

    public function OM_Depot_init($token)
    {
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=>$this->auth_x_token,
                    'Authorization'=>'Bearer '.$token
                ])
            ->Post($this->endpoint.'/cashin/init');
        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            $alerte = new ApiLog();
            $alerte->logError($response->status(), "OM_Depot_init", null, json_decode($response->body()));
            return response()->json([
                'code' => $response->status(),
                'message'=>$response->body(),
            ]);
        }
    }

    public function OM_Depot_execute($token, $payToken, $beneficiaire, $montant, $transId)
    {
        //On execute le depot
        //$description = "Dépôt d'argent sur le compte Orange Money de ".$beneficiaire." par ".Auth::user()->telephone." d'un montant de ".$montant." FCFA";
        $description = "Agent :".Auth::user()->telephone;
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=>$this->auth_x_token,
                    'Authorization'=>'Bearer '.$token
                ])

            ->Post($this->endpoint.'/cashin/pay', [
                "channelUserMsisdn"=> $this->channel,
                "amount"=> $montant,
                "subscriberMsisdn"=> $beneficiaire,
                "pin"=> $this->pin,
                "orderId"=> str_replace(".","",$transId),
                "description"=> $description,
                "payToken"=> $payToken
            ]);

        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            $alerte = new ApiLog();
            $alerte->logError($response->status(), "OM_Depot_execute", null, json_decode($response->body()));
            return response()->json([
                'code' => $response->status(),
                'message'=>$response->body(),
            ],$response->status());
        }
    }

    public function OM_Depot(Request $request){

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:100|max:500000',
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
        $rang = $apiCheck->GenereRang();
        $code = $apiCheck->genererChaineAleatoire(10);
        $code = strtoupper($code);
        $service = ServiceEnum::DEPOT_OM->value;
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

        //Initie la transaction
        $device = $request->deviceId;
        $init_transaction = $apiCheck->init_Depot($montant, $customerNumber, $service,"", $device,"","","",1, Auth::user()->id,"");
        $dataInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>$dataInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataInit->transId; //Id de la transaction initiée
        $reference = $dataInit->reference; //Référence de la transaction initiée

        //On genere le token
        $responseToken = $this->OM_GetTokenAccess();

        if($responseToken->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseToken->getStatusCode()."\nUne exception a été déclenchée au moment de la génération du token"
            ], $responseToken->getStatusCode());
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $token = $AccessToken;

        //On initie le dépôt (Obtention du PayToken

        $responseInitDepot = $this->OM_Depot_init($token);
        if($responseInitDepot->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseInitDepot->getStatusCode()."\nUne exception a été déclenché au moment de l'initialisation de la transaction"
            ], $responseInitDepot->getStatusCode());
        }

        $dataInitDepot= json_decode($responseInitDepot->getContent());
        //    $reference = $dataInitDepot->transId;
        $payToken =$dataInitDepot->data->payToken;
        //    $description = $dataInitDepot->data->description;

        // On met à jour le payToken généré dans la table transaction
        $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
            "payToken"=>$payToken,
            "reference_partenaire"=>$payToken,
            "description"=>"PENDING",
            "status"=>2, //Contrairement à MTN où il ya une etape intermediaire entre (code 202 pour indiquer que le code est PENDING, Orange n'en a pas, raison pour laquelle, on place le status à 2 après création du PayToken
        ]);
        //On execute la dépôt OM
        $responseTraiteDepotOM = $this->OM_Depot_execute($token, $payToken, $customerNumber, $montant, $idTransaction);
        if($responseTraiteDepotOM->getStatusCode() !=200){
            $resultat = json_decode($responseTraiteDepotOM->getContent());
            $result = (array)$resultat;
            if (Arr::has($result, 'message')) {
                $data =json_decode($result["message"]);
                $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
                    "reference_partenaire"=>$data->data->txnid,
                    "description"=>$data->data->status,
                    "status"=>3,
                    "date_end_trans"=>Carbon::now(),
                    "api_response"=>$responseTraiteDepotOM->getContent(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Exception ".$result["code"]."\n".$data->message,
                ], $result["code"]);
            }else{
                $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
                    "payToken"=>$payToken,
                    "description"=>"FAILED",
                    "status"=>3,
                    "date_end_trans"=>Carbon::now(),
                    "api_response"=>$responseTraiteDepotOM->getContent(),
                ]);
                return response()->json([
                    "result"=>false,
                    "success"=>false,
                    "message"=>"Exception ".$responseTraiteDepotOM->getStatusCode() ."\nUne exception a été déclenchée au moment du traitement du dépôt"
                ], $responseTraiteDepotOM->getStatusCode() );
            }

        }

        try{
            DB::beginTransaction();
            $resultat = json_decode($responseTraiteDepotOM->getContent());

            //Dépassement de plafond côté Orange Money
            $result = (array)$resultat;
            if (Arr::has($result, 'code')) {
                $data =json_decode($result["message"]);
                $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
                    "reference_partenaire"=>json_decode($result["data"])->txnid,
                    "description"=>json_decode($result["data"])->status,
                    "status"=>3,
                    "date_end_trans"=>Carbon::now(),
                    "api_response"=>$responseTraiteDepotOM->getContent(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Exception ".$result["code"]."\n".$data->message,
                ], $result["code"]);
            }

            //On Calcule la commission
            $commission=json_decode($lacommission->getContent());
            $commissionFiliale = doubleval($commission->commission_kiaboo);
            $commissionDistributeur=doubleval($commission->commission_distributeur);
            $commissionAgent=doubleval($commission->commission_agent);

            $user = User::where('id', Auth::user()->id);
            $balanceBeforeAgent = $user->get()->first()->balance_after;
            $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($montant);
            //on met à jour la table transaction

            $Transaction = Transaction::where('paytoken',$payToken)->where('service_id',$service)->update([
                'reference_partenaire'=>$resultat->data->txnid,
                'balance_before'=>$balanceBeforeAgent,
                'balance_after'=>$balanceAfterAgent,
                'debit'=>$resultat->data->amount,
                'credit'=>0,
                'status'=>1, //End successfully
                'paytoken'=>$resultat->data->payToken,
                'date_end_trans'=>Carbon::now(),
                'description'=>$resultat->data->status,
                'message'=>$resultat->message,
                'commission'=>$commission->commission_globale,
                'commission_filiale'=>$commissionFiliale,
                'commission_agent'=>$commissionAgent,
                'commission_distributeur'=>$commissionDistributeur,
                'application'=>1,
                'api_response'=>$responseTraiteDepotOM->getContent(),

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
                'reference_last_transaction'=>$resultat->data->txnid,
                'remember_token'=>$resultat->data->payToken,
                'total_commission'=>$commission_agent,
            ]);
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

            $title = "Kiaboo";
            $message = "Le dépôt Orange Money de " . $montant . " F CFA a été effectué avec succès au ".$customerNumber." (ID : ".$resultat->data->payToken.") le ".Carbon::now()->format("d/m/Y H:i:s");
            $appNotification = new ApiNotification();
            $envoiNotification = $appNotification->SendPushNotificationCallBack($device, $title, $message);

            return response()->json([
                'success' => true,
                'message' => $resultat->message,
                'textmessage' => $resultat->message,
                'reference' => $resultat->data->txnid,
                'data' => $resultat,
                'user'=>$userRefresh,
                'transactions'=>$transactionsRefresh,
            ], 200);

        }catch (\Exception $e) {
            $alerte = new ApiLog();
            $alerte->logError($e->getCode(), "OM_Depot", null, $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Exception ".$e->getCode()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
            ], $e->getCode());
        }
    }
    public function OM_Depot_Status($referenceId){

        //On génère le token de la transation
        $responseToken = $this->OM_GetTokenAccess();
        if($responseToken->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseToken->getStatusCode()."\nUne exception a été déclenchée au moment de la génération du token"
            ], $responseToken->getStatusCode());
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $token = $AccessToken;
        // On initie le checkstatus
        try{
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=>$this->auth_x_token,
                        'Authorization'=>'Bearer '.$token
                    ])
                ->get($this->endpoint.'/cashin/paymentstatus/'.$referenceId);

            $data = json_decode($response->body());
            if($response->status()==200){
                //On vérifie si le dépôt a été effectué avec succès
                if($data->data->status=="SUCCESSFULL"){
                    $Transaction = Transaction::where('paytoken',$referenceId)->where('service_id',ServiceEnum::DEPOT_OM->value);
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
                            'description' => $data->data->status,
                            'message' => 'Le dépôt a été effectué avec succès',
                            'commission' => $commission->commission_globale,
                            'commission_filiale' => $commissionFiliale,
                            'commission_agent' => $commissionAgent,
                            'commission_distributeur' => $commissionDistributeur,
                            'reference_partenaire' => $data->data->txnid,
                            'terminaison' => 'MANUAL',
                            'api_response' => $response->getContent(),
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
                  //  $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email', 'balance_before', 'balance_after', 'total_commission', 'last_amount', 'sous_distributeur_id', 'date_last_transaction')->first();
                    $userRefresh = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                        ->join("villes", "quartiers.ville_id", "=", "villes.id")
                        ->where('users.id', Auth::user()->id)
                        ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

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
                            'message'=>$data->message,
                            'description'=>$data->data->status,
                            'response'=>$data,
                            'user'=>$userRefresh,
                            'transactions'=>$transactionsRefresh,
                        ],200
                    );
                }

                if($data->data->status=="PENDING"){
                    return response()->json(
                        [
                            'status'=>402,
                            'message'=>$data->data->status." - ".$data->data->inittxnmessage,
                            'description'=>$data->data->status."\n".$data->message,
                        ],402
                    );
                }
                return response()->json(
                    [
                        'status'=>404,
                        'message'=>$data->message,
                    ],404
                );
            }else{

                return response()->json(
                    [
                        'status'=>$response->status(),
                        'message'=>$data->message,
                    ],$response->status()
                );
            }
        }catch (\Exception $e){
            return response()->json([
                'status'=>500,
                'message'=>$response->body(),
            ],500);
        }

    }
    public function OM_Retrait_init($token)
    {
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=>$this->auth_x_token,
                    'Authorization'=>'Bearer '.$token
                ])

            ->Post($this->endpoint.'/cashout/init');

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

    public function OM_Retrait_execute($token, $payToken, $beneficiaire, $montant, $transId)
    {

        //On execute le retrait
        $description = "Retrait initié par ".Auth::user()->telephone;

        try{
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=>$this->auth_x_token,
                        'WSO2-Authorization'=>'Bearer '.$token
                    ])

                ->Post($this->endpoint.'/cashout/pay', [
                    "notifUrl"=> $this->callbackUrl,
                    "channelUserMsisdn"=> $this->channel,
                    "amount"=> $montant,
                    "subscriberMsisdn"=> $beneficiaire,
                    "pin"=> $this->pin,
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
                ],$response->status());
            }
        }catch (\Exception $e){
            return response()->json([
                'message'=>$e->getMessage()
            ],$e->getCode());
        }

    }
    public function OM_Retrait(Request $request){

        $validator = Validator::make($request->all(), [
            'customerPhone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:500|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::RETRAIT_OM->value;

        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if(!$apiCheck->checkUserValidity()){
            return response()->json([
                'status'=>'error',
                'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
            ],403);
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

        $responseToken = $this->OM_GetTokenAccess();

        if($responseToken->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseToken->getStatusCode()."\nUne exception a été déclenchée au moment de la génération du token"
            ], $responseToken->getStatusCode());
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;

        $customerPhone = "237".$request->customerPhone;

        //On initie le retrait (Obtention du PayToken)
        $responseInitRetrait = $this->OM_Retrait_init($AccessToken);
        if($responseInitRetrait->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseInitRetrait->getStatusCode()."\nUne exception a été déclenchée au moment de l'initialisation de la transaction"
            ], $responseInitRetrait->getStatusCode());
        }
        $dataInitRetrait= json_decode($responseInitRetrait->getContent());
        //    $reference = $dataInitDepot->transId;
        $payToken =$dataInitRetrait->data->payToken;
        //    $description = $dataInitDepot->data->description;
        $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
            "payToken"=>$payToken,
        ]);


        $responseTraiteRetraitOM = $this->OM_Retrait_execute($AccessToken, $payToken, $customerPhone, $request->amount, $idTransaction);
        if($responseTraiteRetraitOM->getStatusCode() !=200){
            $dataRetrait=json_decode($responseTraiteRetraitOM->getContent());
            $data =json_decode($dataRetrait->message);
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseTraiteRetraitOM->getStatusCode()."\n".$data->message
            ], $responseTraiteRetraitOM->getStatusCode());
        }

        $dataRetrait= json_decode($responseTraiteRetraitOM->getContent());
        //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)

        //On change le statut de la transaction dans la base de donnée

        $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
            'reference_partenaire'=>$dataRetrait->data->txnid,
            'balance_before'=>0,
            'balance_after'=>0,
            'debit'=>0,
            'credit'=>$request->amount,
            'status'=>2, // Pending
            'paytoken'=>$payToken,
            'date_end_trans'=>Carbon::now(),
            'description'=>$dataRetrait->data->status,
            'message'=>"Transaction initiée par l'agent N°".Auth::user()->telephone." - ".$dataRetrait->message." | ".$dataRetrait->data->status." | ".$dataRetrait->data->inittxnmessage,
            'commission'=>$commission->commission_globale,
            'commission_filiale'=>$commissionFiliale,
            'commission_agent'=>$commissionAgent,
            'commission_distributeur'=>$commissionDistributeur,
            'api_response'=>$responseTraiteRetraitOM->getContent(),
            'application'=>1
        ]);

        //Le solde du compte de l'agent ne sera mis à jour qu'après confirmation de l'agent : Opération traitée dans le callback

        //On recupère toutes les transactions en attente
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

        return response()->json(
            [
                'status'=>200,
                'message'=>$dataRetrait->message."\n".$dataRetrait->data->status." | ".$dataRetrait->data->inittxnmessage,
                'paytoken'=>$payToken,
                'user'=>$userRefresh,
                'transactions'=>$transactionsRefresh,
            ],200
        );

    }

    public function OM_Retrait_Status($referenceID){

        //On se rassure que la transaction est bien en status en attente
        $Transaction = Transaction::where('paytoken',$referenceID)->where('service_id',ServiceEnum::RETRAIT_OM->value)->where('status',2);

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
        $responseToken = $this->OM_GetTokenAccess();
        if($responseToken->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseToken->getStatusCode()."\nUne exception a été déclenchée au moment de la génération du token"
            ], $responseToken->getStatusCode());
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $token = $AccessToken;
        // On initie le checkstatus
        try{
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=>$this->auth_x_token,
                        'WSO2-Authorization'=>'Bearer '.$token
                    ])
                ->get($this->endpoint.'/cashout/paymentstatus/'.$referenceID);

            $data = json_decode($response->body());

            $Transaction = Transaction::where('paytoken',$referenceID)->where('service_id',ServiceEnum::RETRAIT_OM->value);
            if($Transaction->count()==0){
                return response()->json(
                    [
                        'status'=>404,
                        'message'=>"Aucune transaction en attente",

                    ],404
                );
            }


            if($response->status()==200){

                if($data->data->status=="SUCCESSFULL"){
                    $montant = $data->data->amount;
                    $user = User::where('id', Auth::user()->id);
                    $balanceBeforeAgent = $user->get()->first()->balance_after;
                    $balanceAfterAgent = floatval($balanceBeforeAgent) + floatval($montant);
                    $service = $Transaction->first()->service_id;
                    // On vérifie si les commissions sont paramétrées
                    $functionCommission = new ApiCommissionController();
                    $lacommission = $functionCommission->getCommissionByService($service, $montant);
                    if ($lacommission->getStatusCode() != 200) {

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

                    try{
                        DB::beginTransaction();
                        $updateTransaction=$Transaction->update([
                            'balance_before' => $balanceBeforeAgent,
                            'balance_after' => $balanceAfterAgent,
                            'status' => 1, //End successfully
                            'date_end_trans' => Carbon::now(),
                            'description' => $data->data->status,
                            'message' => $data->message,
                            'commission' => $commission,
                            'commission_filiale' => $commissionFiliale,
                            'commission_agent' => $commissionAgent,
                            'commission_distributeur' => $commissionDistributeur,
                            'reference_partenaire' => $data->data->txnid,
                            'terminaison' => 'MANUAL',
                            'api_response' => $response->getContent(),
                        ]);

                        $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");

                        $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                            'balance_after'=>$balanceAfterAgent,
                            'balance_before'=>$balanceBeforeAgent,
                            'last_amount'=>$montant,
                            'date_last_transaction'=>Carbon::now(),
                            'user_last_transaction_id'=>Auth::user()->id,
                            'last_service_id'=>ServiceEnum::RETRAIT_OM->value,
                            'reference_last_transaction'=>$reference,
                            'remember_token'=>$referenceID,
                            'total_commission'=>$commission_agent,
                        ]);
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

                        DB::commit();
                        $title = "Kiaboo";
                        $message = "Le retrait MOMO de " . $montant . " F CFA a été effectué avec succès au ".$customer_phone;
                        $subtitle ="Success";
                        //  $appNotification = new ApiNotification();
                        //  $envoiNotification = $appNotification->SendPushNotificationCallBack($device_notification, $title,  $message);

                        return response()->json(
                            [
                                'status'=>200,
                                'message'=>$data->data->status."\n".$data->message,
                                'user'=>$userRefresh,
                                'transactions'=>$transactionsRefresh,
                                'response'=>$data
                            ],200
                        );
                    }catch(\Exception $e){
                        DB::rollBack();

                        return response()->json(
                            [
                                'status'=>500,
                                'message'=>"Une erreur est survenue lors de la mise à jour de la transaction",
                            ],500
                        );
                    }

                }

                if($data->data->status=="PENDING"){
                    return response()->json(
                        [
                            'status'=>402,
                            'message'=>$data->data->status." - ".$data->data->inittxnmessage,
                            'description'=>$data->data->status."\n".$data->message,
                        ],402
                    );
                }
                return response()->json(
                    [
                        'status'=>404,
                        'message'=>$data->message,
                    ],404
                );
            }else{
                return response()->json(
                    [
                        'error'=>false,
                        'status'=>$response->status(),
                        'message'=>$data->message,
                    ],$response->status()
                );
            }
        }catch (\Exception $e){
            return response()->json([
                'status'=>500,
                'message'=>$response->body(),
            ],500);
        }

    }

    public function OM_Paiement_init($token)
    {
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=>$this->auth_x_token,
                    'Authorization'=>'Bearer '.$token
                ])

            ->Post($this->endpoint.'/mp/init');

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

    public function OM_Payment_execute($token, $payToken, $beneficiaire, $montant, $transId)
    {

        //On execute le retrait
        $description = "Ordoné par ".Auth::user()->telephone;

        try{
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=>$this->auth_x_token,
                        'Authorization'=>'Bearer '.$token
                    ])

                ->Post($this->endpoint.'/mp/pay', [
                    "notifUrl"=> $this->callbackUrl,
                    "channelUserMsisdn"=> $this->channel,
                    "amount"=> $montant,
                    "subscriberMsisdn"=> $beneficiaire,
                    "pin"=> $this->pin,
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
                ],$response->status());
            }
        }catch (\Exception $e){
            return response()->json([
                'message'=>$e->getMessage()
            ],$e->getCode());
        }

    }
    public function OM_Payment(Request $request){

        $validator = Validator::make($request->all(), [
            'customerPhone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:500|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::PAYMENT_OM->value;

        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if(!$apiCheck->checkUserValidity()){
            return response()->json([
                'status'=>'error',
                'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
            ],403);
        }

        // On vérifie si les frais sont paramétrées
        $functionFees = new ApiCommissionController();
        $lesfees =$functionFees->getFeesByService($service,$request->amount);
        if($lesfees->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "Impossible de calculer la commission",
            ], 403);
        }
        $fee=json_decode($lesfees->getContent());
        $fees = doubleval($fee->fees_globale);

        //Initie la transaction
        $device = $request->deviceId;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Payment($request->amount, $request->customerPhone, $service,"", Auth::user()->id,1, $device,$latitude,$longitude,$place);

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

        $responseToken = $this->OM_GetTokenAccess();
        if($responseToken->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseToken->getStatusCode()."\nUne exception a été déclenchée au moment de la génération du token"
            ], $responseToken->getStatusCode());
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;

        $customerPhone = "237".$request->customerPhone;

        //On initie le paiement (Obtention du PayToken)
        $responseInitPaiement = $this->OM_Paiement_init($AccessToken);
        if($responseInitPaiement->getStatusCode() !=200){
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseInitPaiement->getStatusCode()."\nUne exception a été déclenchée au moment de l'initialisation de la transaction"
            ], $responseInitPaiement->getStatusCode());
        }
        $dataInitPaiement= json_decode($responseInitPaiement->getContent());
        //    $reference = $dataInitDepot->transId;
        $payToken =$dataInitPaiement->data->payToken;

        //    $description = $dataInitDepot->data->description;
        $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
            "payToken"=>$payToken,
        ]);

        $responseTraitePaiementOM = $this->OM_Payment_execute($AccessToken, $payToken, $request->customerPhone, $request->amount, $idTransaction);

        if($responseTraitePaiementOM->getStatusCode() !=200){
            $dataRetrait=json_decode($responseTraitePaiementOM->getContent());
            $data =json_decode($dataRetrait->message);
            return response()->json([
                "result"=>false,
                "message"=>"Exception ".$responseTraitePaiementOM->getStatusCode()."\n".$data->message
            ], $responseTraitePaiementOM->getStatusCode());
        }

        $dataPaiement= json_decode($responseTraitePaiementOM->getContent());
        //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)

        //On change le statut de la transaction dans la base de donnée

        $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
            'reference_partenaire'=>$dataPaiement->data->txnid,
            'balance_before'=>0,
            'balance_after'=>0,
            'debit'=>0,
            'credit'=>$request->amount,
            'status'=>2, // Pending
            'paytoken'=>$payToken,
           // 'date_end_trans'=>Carbon::now(),
            'description'=>$dataPaiement->data->status,
            'message'=>"Transaction initiée par l'agent N°".Auth::user()->telephone." - ".$dataPaiement->message." | ".$dataPaiement->data->status." | ".$dataPaiement->data->inittxnmessage,
            'fees'=>$fees,
            'fees_collecte'=>$fees,
            'api_response'=>$responseTraitePaiementOM->getContent(),
            'application'=>1
        ]);

        //Le solde du compte de l'agent ne sera mis à jour qu'après confirmation de l'agent : Opération traitée dans le callback

        //On recupère toutes les transactions en attente
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

        return response()->json(
            [
                'status'=>200,
                'message'=>$dataPaiement->message."\n\n".$dataPaiement->data->status,
                'paytoken'=>$payToken,
                'user'=>$userRefresh,
                'transactions'=>$transactionsRefresh,
            ],200
        );

    }
}
