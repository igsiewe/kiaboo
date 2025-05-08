<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\ApiNotification;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ApiProdYooMeeController extends Controller
{
    public function YooMee_getUserInfo($customerPhone){


        if ($customerPhone==null || $customerPhone=="") {
            return response()->json([
                'success' => false,
                'message' => "Please provide a customer phone number",
            ], 400);
        }
        $url="https://yoomeemoney.cm/api/";
        $endpoint = $url."users?keywords=$customerPhone&roles=member&statuses=active";
        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiabooProd2024", "Ki@@boo#@2024")
            ->Get($endpoint);

        $customerPhone="";
        $customerName="";
        $customerAccount="";
        $customerId="";
        if($response->status()==401){
            $response = json_decode($response);
            return response()->json([
                'status' => 'echec',
                'customerId'=>$customerId,
                'customerName' => $customerName,
                'customerPhone' => $customerPhone,
                'customerAccount' => $customerAccount,
                'message'=>$response->code,
                'response'=>$response,
            ],404);
        }
        if($response->body()==null || $response->body()=="[]"){ //On teste si l'utilisateur existe
            return response()->json([
                'status' => 'echec',
                'customerId'=>$customerId,
                'customerName' => $customerName,
                'customerPhone' => $customerPhone,
                'customerAccount' => $customerAccount,
                'message'=>'Ce numéro de client n\'existe pas. Veuillez vérifier le numéro de téléphone',
                'response'=>$response,
            ],404);
        }
        if($response->status()==200){

            $element = json_decode($response, associative: true);

            if(!Arr::has($element[0], "name")){ //On teste si l'utilisateur existe
                return response()->json([
                    'status' => 'echec',
                    'customerId'=>$customerId,
                    'customerName' => $customerName,
                    'customerPhone' => $customerPhone,
                    'customerAccount' => $customerAccount,
                    'message'=>'Ce numéro de client n\'existe pas',
                    'response'=>$response,
                ],404);
            }
            $json = json_decode($response, false);
            $data=collect($json)->first();
            $customerName = $data->name;
            $customerPhone = $data->phone;
            $customerAccount = $data->accountNumber; //accountNumber;
            $customerId = $data->id;
            if($customerName==null && $customerAccount==null){
                return response()->json([
                    'status' => 'echec',
                    'customerId'=>$customerId,
                    'customerName' => $customerName,
                    'customerPhone' => $customerPhone,
                    'customerAccount' => $customerAccount,
                    'message'=>'Ce numéro de client n\'existe pas',
                ],404);
            }

            return response()->json([
                'status' => 'success',
                'customerId'=>$customerId,
                'customerName' => $customerName,
                'customerPhone' => $customerPhone,
                'customerAccount' => $customerAccount,
                'message'=>'Client trouvé',
            ],200);
        }else{

            return response()->json(
                [
                    'status'=>$response->status(),
                   // 'message'=>$response->body(),
                    'message'=>"Ressource non trouvée",
                ],$response->status()
            );
        }
    }

    public function YooMee_depot(Request $request){

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:50|max:500000',
            'customerId' => 'required|string', //customerId
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

        $service = ServiceEnum::DEPOT_YOOMEE->value;
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
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Depot($montant, $customerNumber, $service, "",$device,$latitude,$longitude,$place,1, Auth::user()->id,"");
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

        $customerId = $request->customerId;
        $customerAmount =$montant;
        $url="https://yoomeemoney.cm/api/";
        $endpoint = $url."self/payments";
        $description = "Agent ".Auth::user()->telephone;
        $codePin="7424";
        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiabooProd2024", "Ki@@boo#@2024")->withHeaders(
            [
                'confirmationPassword'=> $codePin,
                'accept'=>  'application/json',
                'Content-Type'=> 'application/json'
            ])
            ->Post($endpoint, [
                "amount"=> $customerAmount,
                "description"=> $description,
                "currency"=> "unit",
                "type"=> "CashpointAccount.Memberemoneypurchase",
                "subject"=>$customerId,
                "firstInstallmentIsImmediate"=> true,
                "scheduling"=>"direct"
            ]);
       // dd($response->body(), $response->status());


        if($response->status()==201){

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
                $data = json_decode($response->body());
                //on met à jour la table transaction
                $referenceID = $data->transactionNumber;
                $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                    'reference_partenaire'=>$referenceID, //$financialTransactionId,
                    'balance_before'=>$balanceBeforeAgent,
                    'balance_after'=>$balanceAfterAgent,
                    'debit'=>$montant,
                    'credit'=>0,
                    'status'=>1, //End successfully
                    'paytoken'=>$referenceID,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>"SUCCESSFUL",
                    'message'=>'Le dépôt a été effectué avec succès',
                    'commission'=>$commission->commission_globale,
                    'commission_filiale'=>$commissionFiliale,
                    'commission_agent'=>$commissionAgent,
                    'commission_distributeur'=>$commissionDistributeur,
                    'api_response'=>$response->body(),
                    'application'=>1,
                ]);

                //on met à jour le solde de l'utilisateur

                //La commmission de l'agent après chaque transaction

                $commission_agent = Transaction::where("status",1)->where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");

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

               // $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();
                $userRefresh = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                    ->join("villes", "quartiers.ville_id", "=", "villes.id")
                    ->where('users.id', Auth::user()->id)
                    ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code','users.total_fees','users.total_paiement')->first();

                $transactionsRefresh = DB::table('transactions')
                    ->join('services', 'transactions.service_id', '=', 'services.id')
                    ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                    ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent','transactions.fees')
                    ->where("fichier","agent")
                    ->where("source",Auth::user()->id)
                    ->where('transactions.status',1)
                    ->orderBy('transactions.date_transaction', 'desc')
                    ->limit(5)
                    ->get();

                $title = "Transaction en succès";
                $message = "Le dépôt YOOMEE de " . $montant . " F CFA a été effectué avec succès au ".$customerNumber." (ID transaction:".$referenceID.") le ".Carbon::now()->format('d/m/Y H:i');
                $appNotification = new ApiNotification();
                $envoiNotification = $appNotification->SendPushNotificationCallBack($device, $title, $message);
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
                $title = "Transaction en échec";
                $message = "Le dépôt YOOMEE de " . $montant . " F CFA au ".$customerNumber." (ID transaction: ".$referenceID.") est échec";
                $appNotification = new ApiNotification();
                $envoiNotification = $appNotification->SendPushNotificationCallBack($device, $title, $message);
                Log::error("YooMee_Depot",["response"=>$e->getMessage(),"code"=>$e->getCode(),"Message"=>$e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
                ], 403);
            }

        }else{

            $data = json_decode($response);
            return response()->json(
                [
                    'status'=>$response->status(),
                    'error'=>$response->body(),
                    'message'=>$data->code,
                    'statusCode'=>$response->status(),
                ],404
            );
        }
    }

    public function YooMee_Retrait(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:50|max:500000',
            'customerId' => 'required|string', //customerId
            'deviceId' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::RETRAIT_YOOMEE->value;

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
            ], 403);
        }
        $commission=json_decode($lacommission->getContent());

        $commissionFiliale = doubleval($commission->commission_kiaboo);
        $commissionDistributeur=doubleval($commission->commission_distributeur);
        $commissionAgent=doubleval($commission->commission_agent);

        //Initie la transaction
        $device = $request->deviceId;
        $montant=$request->amount;
        $customerPhone = $request->phone;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Retrait($montant, $customerPhone, $service,"", $device, $latitude, $longitude, $place);

        $dataTransactionInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>$dataTransactionInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataTransactionInit->transId; //Id de la transaction initiée
        $reference = $dataTransactionInit->reference; //Référence de la transaction initiée

        $customerId = $request->customerId;
        $url="https://yoomeemoney.cm/api/";
        $endpoint = $url."self/payment-requests";
        $description = "test kiaboo";

        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiabooProd2024", "Ki@@boo#@2024")->withHeaders(
            [
                'accept'=>  'application/json',
                'Content-Type'=> 'application/json'
            ])
            ->Post($endpoint, [
                "amount"=> $montant,
                "description"=> $description,
                "currency"=> "unit",
                "type"=> "MemberAccount.Memberemoneysale",
                "subject"=> $customerId,
                "expirationDate"=> Carbon::now()->addMinutes(2)->format('Y-m-d H:i:s'),
                "firstInstallmentIsImmediate"=> true,
                "installmentsCount"=> 1,
                "scheduling"=> "direct"
            ]);



        if($response->status()==201){
            //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)

            //On change le statut de la transaction dans la base de donnée
            $data = json_decode($response->body());
            $referenceID=$data->transactionNumber;
            $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                'reference_partenaire'=>$referenceID,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$montant,
                'status'=>2, // Pending
                'paytoken'=>$referenceID,
                'date_end_trans'=>Carbon::now(),
                'description'=>'PENDING',
                'message'=>"Transaction initiée par l'agent N°".Auth::user()->id." le ".Carbon::now()." vers le client ".$customerPhone." En attente confirmation du client",
                'commission'=>$commission->commission_globale,
                'commission_filiale'=>$commissionFiliale,
                'commission_agent'=>$commissionAgent,
                'commission_distributeur'=>$commissionDistributeur,
                'api_response'=>$response->body(),
                'application'=>1
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

            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }
    }

    public function YooMee_getRetraitStatus($referenceID){

        //On se rassure que la transaction est bien en status en attente
        $Transaction = Transaction::where('paytoken',$referenceID)->where('service_id',ServiceEnum::RETRAIT_YOOMEE->value)->where('status',2);

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

        $url="https://yoomeemoney.cm/api/";
        $http = $url."transactions/".$referenceID;

        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiabooProd2024", "Ki@@boo#@2024")->withHeaders(
            [
                'accept'=>  'application/json',
                'Content-Type'=> 'application/json'
            ])->get($http);

        $data = json_decode($response->body());

        if($response->status()==200){

            if($data->paymentRequestStatus== "processed"){
                // $reason = json_decode($data->reason);
                return response()->json(
                    [
                        'status'=>202,
                        'message'=>strtoupper($data->paymentRequestStatus)." - Transaction en attente de confirmation par le client",
                        'data'=>$data,
                    ],202
                );
            }
            if($data->paymentRequestStatus== "denied"){
                $updateTransaction=$Transaction->update([
                    'status'=>3, // Le client n'a pas validé dans les délais et l'opérateur l'a annule
                    'paytoken'=>$referenceID,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>strtoupper($data->paymentRequestStatus),
                    'terminaison'=>'MANUAL',
                ]);
                return response()->json(
                    [
                        'status'=>402,
                        'message'=>strtoupper($data->paymentRequestStatus)." - Le client a rejeté la transaction",

                    ],402
                );
            }
            if($data->paymentRequestStatus== "expired"){
                $updateTransaction=$Transaction->update([
                    'status'=>3, // Le client n'a pas validé dans les délais et l'opérateur l'a annule
                    'paytoken'=>$referenceID,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>strtoupper($data->paymentRequestStatus),
                    'terminaison'=>'MANUAL',
                ]);
                return response()->json(
                    [
                        'status'=>402,
                        'message'=>strtoupper($data->paymentRequestStatus)." - Le client n'a pas validé la transaction dans les délais et l'opérateur l'a annulé",

                    ],402
                );
            }
            if($data->paymentRequestStatus== "open"){
                $montant = $data->amount;
                $user = User::where('id', Auth::user()->id);
                $balanceBeforeAgent = $user->get()->first()->balance_after;
                $balanceAfterAgent = floatval($balanceBeforeAgent) + floatval($montant);
                $reference_partenaire=$data->transactionNumber;
                try{
                    DB::beginTransaction();
                    $updateTransaction=$Transaction->update([
                        'balance_before'=>$balanceBeforeAgent,
                        'balance_after'=>$balanceAfterAgent,
                        'status'=>1, // Successful
                        'paytoken'=>$referenceID,
                        'date_end_trans'=>Carbon::now(),
                        'description'=>strtoupper($data->paymentRequestStatus),
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
                        'last_service_id'=>ServiceEnum::RETRAIT_YOOMEE->value,
                        'reference_last_transaction'=>$reference,
                        'remember_token'=>$referenceID,
                        'total_commission'=>$commission_agent,
                    ]);
                   // $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();
                    $userRefresh = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                        ->join("villes", "quartiers.ville_id", "=", "villes.id")
                        ->where('users.id', Auth::user()->id)
                        ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code','users.total_fees','users.total_paiement')->first();

                    $transactionsRefresh = DB::table('transactions')
                        ->join('services', 'transactions.service_id', '=', 'services.id')
                        ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                        ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent','transactions.fees')
                        ->where("fichier","agent")
                        ->where("source",Auth::user()->id)
                        ->where('transactions.status',1)
                        ->orderBy('transactions.date_transaction', 'desc')
                        ->limit(5)
                        ->get();

                    DB::commit();
                    $title = "Kiaboo";
                    $message = "Le retrait YOOMEE de " . $montant . " F CFA a été effectué avec succès au ".$customer_phone;
                    $subtitle ="Success";
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($device_notification, $title,  $message);

                    return response()->json(
                        [
                            'status'=>200,
                            'message'=>strtoupper($data->paymentRequestStatus)." - Transaction en succès",
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

            return response()->json(
                [
                    'status'=>404,
                    'message'=>$response->body(),
                ],404
            );
        }else{

            return response()->json(
                [
                    'error'=>false,
                    'status'=>$response->status(),
                    'message'=>'Ressource introuvable',
                ],$response->status()
            );
        }
    }

    public function YooMeeCallback(Request $request){

    }

}
