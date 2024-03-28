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

        $url = "http://quality-env.yoomeemoney.cm:8080/api/users?keywords=$customerPhone&roles=member&statuses=active";
        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiaboo2024", "Ki@boo2024")
            ->Get($url);

        $customerPhone="";
        $customerName="";
        $customerAccount="";
        $customerId="";
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
            Log::error([
                'code'=> $response->status(),
                'function' => "YooMee_getUserInfo",
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
            ],401);
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
            ],401);
        }

        //Vérifie si l'utilisateur n'a pas initié une operation similaire dans les 5 dernières minutes

        if($apiCheck->checkFiveLastTransaction($customerNumber, $montant, $service)){
            return response()->json([
                'status'=>'error',
                'message'=>'Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ],401);
        }

        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$montant);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "Impossible de calculer la commission",
            ], 400);
        }

        //Initie la transaction
        $device = $request->deviceId;
        $init_transaction = $apiCheck->init_Depot($montant, $customerNumber, $service, "",$device);
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
        $url = "http://quality-env.yoomeemoney.cm:8080/api/self/payments";
        $description = "test kiaboo";
        $codePin="1235";
        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiaboo2024", "Ki@boo2024")->withHeaders(
            [
                'confirmationPassword'=> $codePin,
                'accept'=>  'application/json',
                'Content-Type'=> 'application/json'
            ])
            ->Post($url, [
                "amount"=> $customerAmount,
                "description"=> $description,
                "currency"=> "unit",
                "type"=> "CashpointAccount.Memberemoneypurchase",
                "subject"=>$customerId,
                "firstInstallmentIsImmediate"=> true,
                "scheduling"=>"direct"
            ]);
       // dd($response->body(), $response->status());
        Log::info([
            "Service"=>ServiceEnum::DEPOT_YOOMEE->name,
            "url"=>$url,
            "requete"=>[
                "amount"=> $customerAmount,
                "description"=> $description,
                "currency"=> "unit",
                "type"=> "CashpointAccount.Memberemoneypurchase",
                "subject"=>$customerNumber,
                "firstInstallmentIsImmediate"=> true,
                "installmentsCount"=> 0,
                "scheduling"=>"direct"
            ],
            "reponseStatus"=>json_decode($response->status()),
            "reponseBody"=>json_decode($response->body()),
        ]);

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
                    ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
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

                $envoiNotification = $appNotification->sendNotificationPushFireBase($idDevice, $title, $subtitle, $message); //Push notification sur le telephone de l'agent
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
                Log::error([
                    'code'=> $response->status(),
                    'function' => "YOOMEE_Depot",
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
                'function' => "YOOMEE_Depot",
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
            ], 400);
        }
        $commission=json_decode($lacommission->getContent());

        $commissionFiliale = doubleval($commission->commission_kiaboo);
        $commissionDistributeur=doubleval($commission->commission_distributeur);
        $commissionAgent=doubleval($commission->commission_agent);

        //Initie la transaction
        $device = $request->deviceId;
        $montant=$request->amount;
        $customerPhone = $request->phone;

        $init_transaction = $apiCheck->init_Retrait($montant, $request->customerPhone, $service,"", $device);

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

        $url = "http://quality-env.yoomeemoney.cm:8080/api/self/payment-requests";
        $description = "test kiaboo";

        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiaboo2024", "Ki@boo2024")->withHeaders(
            [
                'accept'=>  'application/json',
                'Content-Type'=> 'application/json'
            ])
            ->Post($url, [
                "amount"=> $montant,
                "description"=> $description,
                "currency"=> "unit",
                "type"=> "MemberAccount.Memberemoneysale",
                "subject"=> $customerId,
              //  "expirationDate"=> Carbon::now(),
                "firstInstallmentIsImmediate"=> true,
                "installmentsCount"=> 1,
                "scheduling"=> "direct"
            ]);


        Log::info([
            "Service"=>ServiceEnum::RETRAIT_YOOMEE->name,
            "url"=>$url,
            "requete"=>[
                "amount"=> $montant,
                "description"=> $description,
                "currency"=> "unit",
                "type"=> "MemberAccount.Memberemoneysale",
                "subject"=> $customerId,
                "expirationDate"=> Carbon::now(),
                "firstInstallmentIsImmediate"=> true,
                "installmentsCount"=> 1,
                "scheduling"=> "direct"
            ],
            "reponseStatus"=>json_decode($response->status()),
            "reponseBody"=>json_decode($response->body()),
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
            Log::error([
                'code'=> $response->status(),
                'function' => "YOOMEE_Retrait",
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


}
