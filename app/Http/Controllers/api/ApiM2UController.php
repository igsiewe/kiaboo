<?php

namespace App\Http\Controllers\api;

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

class ApiM2UController extends Controller
{

    public function M2U_NameCustomer($customerNumber)
    {

        if (strlen($customerNumber) !=9){
            return response()->json([
                'status'=>'error',
                'message'=>'Le numéro de téléphone incorrect'
            ],404);
        }

            $endpoint = 'https://sandbox.m2u.money/LocateUser';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                    ])

                ->Post($endpoint, [
                    "LoginName"=> "CM645403",
                    "APIKey"=> "6uW6nO5d3WzxnDrd8s9fyxi7ij0YY20O4DiZy0GCdTdYF",
                    "AppID"=> "2Is8IFAsx23J5805ehy3QayZp",
                    "PhoneNumber"=>'237'.$customerNumber,
                ]  );

                if($response->status()==401){
                    return response()->json([
                        'status' => 'echec',
                        'message'=>'Aucun client trouvé',
                    ],401);
                }

            if($response->status()==200){
                $json = json_decode($response, false);

                $data=collect($json)->first();
                $firstName = $data->FirstName;
                $lastName = $data->LastName;
                $accountNumber = $data->Wallets[0]->AccountNumber;
                if($firstName==null && $lastName==null){
                    return response()->json([
                        'status' => 'echec',
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'message'=>'Ce numéro de client n\'existe pas',
                    ],404);
                }

                return response()->json([
                    'status' => 'success',
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'accountNumber' => $accountNumber,
                    'message'=>'Client trouvé',
                ],200);
                //  return response()->json($response->json());
            }else{
                Log::error([
                    'code'=> $response->status(),
                    'function' => "M2U_NameCustomer",
                    'response'=>$response->body(),
                    'user' => Auth::user()->id,
                    'customerPhone'=>$customerNumber,
                ]);
                $body = json_decode($response->body());
                return response()->json([
                    'code' => $response->status(),
                    'message' =>"1. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                ],$response->status());
            }


    }

    public function M2U_getToken(){
        $endpoint = 'https://sandbox.m2u.money/CPTellerLogin';
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                ])

            ->Post($endpoint, [
                "LoginName" => "CM645403",
                "APIKey" => "6uW6nO5d3WzxnDrd8s9fyxi7ij0YY20O4DiZy0GCdTdYF",
                "AppID" => "2Is8IFAsx23J5805ehy3QayZp",
                "PID" => "CM2408-8360",
                "CPID" => "CM2408-001",
                "Password" => "jopLi@25H",
                "PIN" => "514973",
                "CountryCode" => "CM",
                "PhoneNumber" => "659657424",
                "IMEI" => "",
                "SerialNumber" => "",
                "DeviceOS" => "",
                "Description" => "",
                "OSVersion" => "Mac os",
                "MAC" => "",
                "Roaming" => "0",
                "DeviceType" => "3",
                "AppType" => "1",
                "Longitude" => "-17.46219820000",
                "Latitude" => "14.701588200000",
                "Authenticator" => "YES"
            ]  );

        if($response->status()==200){
            $json = json_decode($response, false);

            $data=collect($json)->first();

            if($data->OK != 200){
                return response()->json([
                    'status' => 'error',
                    'message'=>'Une error s\'est produite. Veuillez contacter votre support',
                ],$data->OK);
            }
            $LiveToken = $data->LiveToken;

            return response()->json([
                'status' => 'success',
                'token' => $LiveToken,
            ],200);
        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "M2U_getToken",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
            ]);

            return response()->json([
                'code' => $response->status(),
                'message' =>"2. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
            ],$response->status());
        }
    }

    public function M2U_depot(Request $request){

        $validator = Validator::make($request->all(), [
            'customerNumber' => 'required|numeric|digits:9',
            'montant' => 'required|numeric|min:50|max:500000',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'accountNumber' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $customerNumber = $request->customerNumber;
        $firstName = $request->firstName;
        $lastName = $request->lastName;
        $accountNumber = $request->accountNumber;
        $montant = $request->montant;

         $apiCheck = new ApiCheckController();
         $rang = $apiCheck->GenereRang();
         $code = $apiCheck->genererChaineAleatoire(10);
         $code = strtoupper($code);
         $service = ServiceEnum::DEPOT_M2U->value;

        // Vérifie si le service est actif
        if($apiCheck->checkStatusService($service)==false){
            return response()->json([
                'status'=>'error',
                'message'=>"Ce service n'est pas actif",
            ],401);
        }

         // Vérifie si l'utilisateur est autorisé à faire cette opération
         if($apiCheck->checkUserValidity()==false){
             return response()->json([
                 'status'=>'error',
                 'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
             ],401);
         }

         // Vérifie si le solde de l'utilisateur lui permet d'effectuer cette opération
         if($apiCheck->checkUserBalance($montant)==false){
             return response()->json([
                 'status'=>'error',
                 'message'=>'Votre solde est insuffisant pour effectuer cette opération',
             ],401);
         }

         //Vérifie si l'utilisateur n'a pas initié une operation similaire dans les 5 dernières minutes

         if($apiCheck->checkFiveLastTransaction($customerNumber, $montant, $service)==true){
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
         $commission=json_decode($lacommission->getContent());

         $commissionFiliale = doubleval($commission->commission_kiaboo);
         $commissionDistributeur=doubleval($commission->commission_distributeur);
         $commissionAgent=doubleval($commission->commission_agent);

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

         //On génére le token pour la transaction

         $getToken=$this->M2U_getToken();
         $datagetToken = json_decode($getToken->getContent());

         if ($getToken->getStatusCode()==200) {

            $token = $datagetToken->token;

            //ON vérifie le soldes du wallet de l'agent au niveau de M2U

            $checkSolde = $this->M2U_CheckSoldeTeller($montant, $token);
            if($checkSolde->getStatusCode()!=200){
                Log::error(
                    [
                        'code'=> $checkSolde->getStatusCode(),
                        'function' => "M2U_CheckSoldeTeller",
                        'response'=>$checkSolde->body(),
                        'user' => Auth::user()->id,
                        'montant' => $montant,
                    ]
                );
                return response()->json([
                    'status'=>'error',
                    'message'=>'Impossible de vérifier le solde du wallet de l\'agent',
                ],$checkSolde->getStatusCode());
            }
            if($checkSolde->getStatusCode()==200){
                $dataCheckSolde = json_decode($checkSolde->getContent());

                if($dataCheckSolde->success==false){
                    Log::error(
                        [
                            'code'=> $checkSolde->getStatusCode(),
                            'function' => "M2U_CheckSoldeTeller",
                            'response'=>$dataCheckSolde->message,
                            'user' => Auth::user()->id,
                            'montant' => $montant,
                        ]
                    );
                    return response()->json([
                        'status'=>'error',
                        'message'=>"Le solde du compte M2U est insuffisant pour effectuer cette opération",
                    ],401);
                }
            }

            //On fait le dépot

            $endpoint = 'https://sandbox.m2u.money/CPAddMoney';
            $response = Http::withOptions(['verify' => false,])
                 ->withHeaders(
                     [
                         'Content-Type' => 'application/json',
                         'From' => $token,
                     ])

                 ->Post($endpoint, [
                        "Amount" => $montant,
                        "TargetType" => "50",
                        "TargetCurrency" => "XAF",
                        "SourceWallet" => "XAF-01-CM2408-001", //Le wallet de l'agent => Du cashPoint
                        "TargetWallet" => $accountNumber, //Le wallet du customer => Le champ AccountNumber
                        "FirstName" => $firstName,
                        "LastName" => $lastName,
                        "PhoneNumber" => $customerNumber,
                        "UseDefaultWallet" => "No",
                     //   "ContactPID" => "CM8205-0471",
                        "OTP" => "jopLi@25H" //Le password du Teller
                 ]  );

             if($response->status()==200) {

                 $json = json_decode($response, false);
                 $dataResultat = collect($json)->first();
                 if ($dataResultat->OK != 200) {
                     return response()->json([
                         'status' => 'error',
                         'message' => 'Une error s\'est produite. Veuillez contacter votre support',
                     ], $dataResultat->OK);
                 }

                 //On met à jour la table transaction

                 //Par mesure de sécurité je rappelle les données de l'utilisateur
                 $user = User::where('id', Auth::user()->id);
                 $balanceBeforeAgent = $user->get()->first()->balance_after;
                 $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($montant);
                 //on met à jour la table transaction

                 try {
                     DB::beginTransaction();
                     $Transaction = Transaction::where('id', $idTransaction)->where('service_id', $service)->update([
                         'reference_partenaire' => $reference,
                         'balance_before' => $balanceBeforeAgent,
                         'balance_after' => $balanceAfterAgent,
                         'debit' => $montant,
                         'credit' => 0,
                         'status' => 1, //End successfully
                         'paytoken' => $reference,
                         'date_end_trans' => Carbon::now(),
                         'description' => 'SUCCESSFULL',
                         'message' => $dataResultat->Description,
                         'commission' => $commission->commission_globale,
                         'commission_filiale' => $commissionFiliale,
                         'commission_agent' => $commissionAgent,
                         'commission_distributeur' => $commissionDistributeur,
                     ]);
                     //La commmission de l'agent après chaque transaction

                     $commission_agent = Transaction::where("status",1)->where("fichier", "agent")->where("commission_agent_rembourse", 0)->where("source", Auth::user()->id)->sum("commission_agent");

                     $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                         'balance_after' => $balanceAfterAgent,
                         'balance_before' => $balanceBeforeAgent,
                         'last_amount' => $montant,
                         'date_last_transaction' => Carbon::now(),
                         'user_last_transaction_id' => Auth::user()->id,
                         'last_service_id' => $service,
                         'reference_last_transaction' => $reference,
                         'remember_token' => $reference,
                         'total_commission' => $commission_agent,
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
                     $message = "Votre dépôt M2U Money de " . $montant . " F CFA a été effectué avec succès au ".$customerNumber;
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
                                 'request' => $request->all()
                             ]);
                         }else{
                             Log::error([
                                 'code'=> 500,
                                 'function' => "MOMO_Depot",
                                 'response'=>$resultNotification,
                                 'user' => Auth::user()->id,
                                 'request' => $request->all()
                             ]);
                         }
                     }

                     return response()->json([
                         'success' => true,
                         'message' => $dataResultat->Description,
                         'textmessage' => $dataResultat->Description,
                         'reference' => $reference,
                         'data' => $response->body(),
                         'user'=>$userRefresh,
                         'transactions'=>$transactionsRefresh,
                     ], 200);
                 }catch (\Exception $e){
                     DB::rollBack();
                     Log::error([
                         'code'=> $e->getCode(),
                         'function' => "depotM2U",
                         'response'=>$e->getMessage(),
                         'user' => Auth::user()->id,
                         'customerPhone'=>$customerNumber,
                     ]);
                     return response()->json([
                         'success' => false,
                         'message' =>"3. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                     ],$e->getCode());
                 }
             }else{
                 Log::error([
                     'code'=> $response->status(),
                     'function' => "depotM2U",
                     'response'=>$response->body(),
                     'user' => Auth::user()->id,
                     'customerPhone'=>$customerNumber,
                     'montant'=>$montant,
                 ]);

                 return response()->json([
                     'code' => $response->status(),
                     'message' =>"4. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                 ],$response->status());
             }
         }else{

             Log::error([
                 'code'=> $getToken->getStatusCode(),
                 'function' => "depotM2U",
                 'response'=>$getToken->getContent(),
                 'user' => Auth::user()->id,
                 'customerPhone'=>$customerNumber,
             ]);

            return response()->json([
                'status'=>'error',
                'message' =>"5. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                'response'=>json_decode($getToken->getContent()),
            ],$getToken->getStatusCode());
        }

    }

    public function M2U_getTransfertStatus(Request $request){ //GetCashTransferStatus

        $validator = Validator::make($request->all(), [
            'customerNumber' => 'required|numeric|digits:9',
            'SecurityCode' => 'required|numeric',
            'VoucherNumber' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
       //Je recupère le PID de lémetteur
        $PID="";
        $nomEmetteur="";

        $emetteur = $this->M2U_NameCustomer($request->customerNumber);
        $dataEmetteur = json_decode($emetteur->getContent());
        if($emetteur->getStatusCode()==200){
            $PID = $dataEmetteur->accountNumber;
            $nomEmetteur = $dataEmetteur->firstName." ".$dataEmetteur->lastName;
        }else{
            return response()->json([
                'success' => false,
                'message' => $dataEmetteur->message,
            ], $emetteur->getStatusCode());
        }

       //On génére le token pour la transaction

        $getToken=$this->M2U_getToken();
        $datagetToken = json_decode($getToken->getContent());

        if ($getToken->getStatusCode()==200) {

            $token = $datagetToken->token;

            $endpoint = 'https://sandbox.m2u.money/GetCashTransferStatus';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type' => 'application/json',
                        'From' => $token,
                    ])

                ->Post($endpoint, [
                    "LoginName" => "CM645403",
                    "APIKey" => "6uW6nO5d3WzxnDrd8s9fyxi7ij0YY20O4DiZy0GCdTdYF",
                    "AppID" => "2Is8IFAsx23J5805ehy3QayZp",
                    "PID" => $PID,
                    "SecurityCode" => $request->SecurityCode,
                    "VoucherNumber" => $request->VoucherNumber,
                ]  );
            if($response->status()==200) {
                $json = json_decode($response, false);
                $data=collect($json)->first();

                if ($data->OK == "200") {
                    if($data->ReturnCode=="-1"){
                        return response()->json([
                            'success' => false,
                            'message' => "Wrong voucher number or security",
                        ], 401);
                    }
                    if($data->ReturnCode=="1"){
                        return response()->json([
                            'success' => false,
                            'message' => "This voucher is already used",
                        ], 401);
                    }
                    if($data->ReturnCode=="2"){
                        return response()->json([
                            'success' => false,
                            'message' => "This transfer number is for another country",
                        ], 401);
                    }
                    if($data->ReturnCode=="3"){
                        return response()->json([
                            'success' => false,
                            'message' => "This voucher has been cancelled by the originator",
                        ], 401);
                    }
                    if($data->ReturnCode=="0"){

                        return response()->json([
                            'success' => true,
                            'message' => "Opération valide",

                            'SenderFirstName'=> $dataEmetteur->firstName,
                            'SenderLastName'=> $dataEmetteur->lastName,
                            'SenderPhoneNumber'=> $request->customerNumber,

                            'beneficiaryFirstName' => $data->Beneficiary->FirstName,
                            'beneficiaryLastname'=> $data->Beneficiary->LastName,
                            'beneficiaryPhone' =>substr($data->Beneficiary->PhoneNumber,3),

                            'amount' => $data->Sender->Amount,
                            "securityCode" => $request->SecurityCode,
                            "voucherNumber" => $request->VoucherNumber,
                          //  "transactionNumber" => $data->Sender->TransactionNumber,

                            'data' => $response->body(),
                        ], 200);
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => "5.Une erreur innatendue s'est produite, veuillez contacter votre superviseur si le problème persiste",
                        ], 401);
                    }

                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "6. Exception : Une erreur innatendue s'est produite, veuillez contacter votre superviseur si le problème persiste",
                    ], 200);
                }
            }
        }else{
            Log::error(
                [
                    'code'=> $getToken->getStatusCode(),
                    'function' => "getTransfertStatus",
                    'response'=>$getToken->getContent(),
                    'user' => Auth::user()->id,
                    'customerPhone'=>$request->customerNumber,
                    "SecurityCode" => $request->SecurityCode,
                    "VoucherNumber" => $request->VoucherNumber,
                ]
            );
            return response()->json([
                'success' => false,
                'message' => "7. Exception : Une erreur innatendue s'est produite, veuillez contacter votre superviseur si le problème persiste",
            ], $getToken->getStatusCode());
        }
   }

    public function M2U_RetraitCPPayCash(Request $request){
        $validator = Validator::make($request->all(), [

            "SecurityCode" => 'required|numeric',
            "VoucherNumber" => 'required|numeric',
            "TargetPhoneNumber" => 'required|numeric',
            "FirstName" => 'required|string',
            "LastName" => 'required|string',
            "Amount" => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $apiCheck = new ApiCheckController();
        $service = ServiceEnum::RETRAIT_M2U->value;

        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if($apiCheck->checkUserValidity()==false){
            return response()->json([
                'status'=>'error',
                'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
            ],401);
        }


        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$request->Amount);
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
        $init_transaction = $apiCheck->init_Retrait($request->Amount, $request->TargetPhoneNumber, $service,"", $device);
        $dataInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>$dataInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataInit->transId; //Id de la transaction initiée
        $reference = $dataInit->reference; //Référence de la transaction initiée

        //On génére le token pour la transaction

        $getToken=$this->M2U_getToken();
        $datagetToken = json_decode($getToken->getContent());

        if ($getToken->getStatusCode()==200) {

            $token = $datagetToken->token;

            $endpoint = 'https://sandbox.m2u.money/CPPayCash';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type' => 'application/json',
                        'From' => $token,
                    ])

                ->Post($endpoint, [
                    "LoginName"=>"CM645403",
                    "APIKey"=>"6uW6nO5d3WzxnDrd8s9fyxi7ij0YY20O4DiZy0GCdTdYF",
                    "AppID"=>"2Is8IFAsx23J5805ehy3QayZp",
                    "SecurityCode"=>$request->SecurityCode,
                    "VoucherNumber"=>$request->VoucherNumber,
                    "TargetPhoneNumber"=>'237'.$request->TargetPhoneNumber,
                    "FirstName"=>$request->FirstName,
                    "LastName"=>$request->LastName,
                    "WalletNumber"=>"XAF-01-CM2408-001",
                    "OTP"=>"jopLi@25H"
                ]  );
            if($response->status()==200) {

                $json = json_decode($response, false);
                $dataResultat = collect($json)->first();
                if ($dataResultat->OK != 200) {
                    return response()->json([
                        'status' => 'error',
                        'message' =>$dataResultat->Description,// 'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }
                if ($dataResultat->ReturnCode != 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' =>$dataResultat->Description,//  'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }
                isset($dataResultat->Result) ? $result = true : $result = false;
                if($result==false){
                    return response()->json([
                        'status' => 'error',
                        'message' =>$dataResultat->Description,//  'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }
                if ($result==true && $dataResultat->Result != "Success") {
                    return response()->json([
                        'status' => 'error',
                        'message' =>$dataResultat->Description,//  'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }
                //On met à jour la table transaction

                //Par mesure de sécurité je rappelle les données de l'utilisateur
                $user = User::where('id', Auth::user()->id);
                $balanceBeforeAgent = $user->get()->first()->balance_after;
                $balanceAfterAgent = floatval($balanceBeforeAgent) + floatval($request->Amount);
                //on met à jour la table transaction

                try {
                    DB::beginTransaction();
                    $Transaction = Transaction::where('id', $idTransaction)->where('service_id', $service)->update([
                        'reference_partenaire' => $reference,
                        'balance_before' => $balanceBeforeAgent,
                        'balance_after' => $balanceAfterAgent,
                        'debit' => 0,
                        'credit' => $request->Amount,
                        'status' => 1, //End successfully
                        'paytoken' => $reference,
                        'date_end_trans' => Carbon::now(),
                        'description' => 'SUCCESSFULL',
                        'message' => $dataResultat->Description,
                        'commission' => $commission->commission_globale,
                        'commission_filiale' => $commissionFiliale,
                        'commission_agent' => $commissionAgent,
                        'commission_distributeur' => $commissionDistributeur,
                    ]);
                    //La commmission de l'agent après chaque transaction

                    $commission_agent = Transaction::where("status",1)->where("fichier", "agent")->where("commission_agent_rembourse", 0)->where("source", Auth::user()->id)->sum("commission_agent");

                    $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                        'balance_after' => $balanceAfterAgent,
                        'balance_before' => $balanceBeforeAgent,
                        'last_amount' => $request->Amount,
                        'date_last_transaction' => Carbon::now(),
                        'user_last_transaction_id' => Auth::user()->id,
                        'last_service_id' => $service,
                        'reference_last_transaction' => $reference,
                        'remember_token' => $reference,
                        'total_commission' => $commission_agent,
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
                    $message = "Le retrait M2U Money de " . $request->Amount . " F CFA a été effectué avec succès au ".$request->TargetPhoneNumber;
                    $subtitle ="Success";
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($idDevice, $title, $message);
                    if($envoiNotification->status()==200){
                        $resultNotification=json_decode($envoiNotification->getContent());
                        $responseNotification=$resultNotification->response ;
                        if($responseNotification->success==true){
                            Log::info([
                                'code'=> 200,
                                'function' => "MOMO_Depot",
                                'response'=>"Notification envoyée avec succès",
                                'user' => Auth::user()->id,
                                'request' => $request->all()
                            ]);
                        }else{
                            Log::error([
                                'code'=> 500,
                                'function' => "MOMO_Depot",
                                'response'=>$resultNotification,
                                'user' => Auth::user()->id,
                                'request' => $request->all()
                            ]);
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'message' => $dataResultat->Description.' Votre transaction a été effectuée avec succès',
                        'textmessage' => $dataResultat->Description,
                        'reference' => $reference,
                        'data' => $response->body(),
                        'user'=>$userRefresh,
                        'transactions'=>$transactionsRefresh,
                    ], 200);
                }catch (\Exception $e){
                    DB::rollBack();
                    Log::error([
                        'code'=> $e->getCode(),
                        'function' => "depotM2U",
                        'response'=>$e->getMessage(),
                        'user' => Auth::user()->id,
                        'customerPhone'=>$request->TargetPhoneNumber,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' =>"3. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                    ],$e->getCode());
                }
            }else{
                Log::error([
                    'code'=> $response->status(),
                    'function' => "depotM2U",
                    'response'=>$response->body(),
                    'user' => Auth::user()->id,
                    'customerPhone'=>$request->TargetPhoneNumber,
                ]);

                return response()->json([
                    'code' => $response->status(),
                    'message' =>"4. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                ],$response->status());
            }
        }else{

            Log::error([
                'code'=> $getToken->getStatusCode(),
                'function' => "depotM2U",
                'response'=>$getToken->getContent(),
                'user' => Auth::user()->id,
                'customerPhone'=>$request->TargetPhoneNumber,
            ]);

            return response()->json([
                'status'=>'error',
                'message' =>"5. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                'response'=>json_decode($getToken->getContent()),
            ],$getToken->getStatusCode());
        }

    }

    public function M2U_CheckSoldeTeller($amount, $token){
        $endpoint = 'https://sandbox.m2u.money/CPTransferConfirmation';
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type' => 'application/json',
                    'From' => $token,
                ])

            ->Post($endpoint, [
                "CountryCode" => "CM",
                "Amount" => $amount,
                "TargetType" => "50",
                "TargetCurrency" => "XAF",
                "SourceWallet" => "XAF-01-CM2408-001"
            ]  );
        if($response->status()==200) {
            $json = json_decode($response, false);
            $data = collect($json)->first();
            if ($data->OK == "200" && $data->Result == "Success") {
                return response()->json([
                    'success' => true,
                    'message' => $data->Description,
                    'data' => $response->body(),
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $data->Description,
                    'data' => $response->body(),
                ], 200);
            }
        }else{
            return response()->json([
                'success' => false,
                'message' => $response->body(),
            ], 200);
        }
    }
}
