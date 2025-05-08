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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiProdM2UController extends Controller
{
    public function M2U_NameCustomer($customerNumber)
    {

        if (strlen($customerNumber) !=9){
            return response()->json([
                'status'=>'error',
                'message'=>'Le numéro de téléphone incorrect'
            ],404);
        }

        $endpoint = 'https://apps.m2u.money/LocateUser';
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                ])

            ->Post($endpoint, [
                "LoginName"=> "CM949513",
                "APIKey"=> "oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv",
                "AppID"=> "8SZpExWP0fxu6rKQEDva03KVT",
                "PhoneNumber"=>'237'.$customerNumber,
            ]  );

        if($response->status()==401){
            return response()->json([
                'status' => 'echec',
                'message'=>'Aucun client trouvé',
            ],404);
        }

        if($response->status()==200){

            $json = json_decode($response, false);
            $data=collect($json)->first();
            $firstName = $data->FirstName;
            $lastName = $data->LastName;

            if($firstName==null && $lastName==null){
                return response()->json([
                    'status' => 'echec',
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'message'=>"1. Exception 404 \nCe numéro client n'existe pas",
                ],404);
            }

            //Je convertis en tableau associatif
            $element = json_decode($response, associative: true);
            if(!Arr::has($element[0], "Wallets")){ //On teste si l'utilisateur a un wallet actif
                return response()->json([
                    'status' => 'echec',
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'message'=>"2. Exception 204\nCe numéro de client n'a pas de compte actif",
                ],404);
            }

            $accountNumber = $data->Wallets[0]->AccountNumber;
            return response()->json([
                'status' => 'success',
                'firstName' => $firstName,
                'lastName' => $lastName,
                'accountNumber' => $accountNumber,
                'message'=>'Client trouvé',
            ],200);
            //  return response()->json($response->json());
        }else{

            //$body = json_decode($response->body());
            return response()->json([
                'code' => $response->status(),
                'message' =>"3. Exception ".$response->status()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
            ],$response->status());
        }


    }

    public function M2U_getToken(){
        $endpoint = 'https://apps.m2u.money/CPTellerLogin';
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                ])

            ->Post($endpoint, [
                "LoginName" => "CM949513",
                "APIKey" => "oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv",
                "AppID" => "8SZpExWP0fxu6rKQEDva03KVT",
                "PIN" => "765639",
                "PID" => "CM9539-2024",
                "CPID" => "CM9539-001",
                "Password" => "SibSnfeSdksSji2023_@",
                "CountryCode" => "CM",
                "PhoneNumber" => "",
                "IMEI" => "",
                "SerialNumber" => "",
                "DeviceOS" => "",
                "Description" => "",
                "OSVersion" => "",
                "MAC" => "",
                "Roaming" => "0",
                "DeviceType" => "3",
                "AppType" => "1",
                "Authenticator" => "YES"
            ]  );

        if($response->status()==200){
            $json = json_decode($response, false);

            $data=collect($json)->first();

            if($data->OK != 200){
                return response()->json([
                    'status' => 'error',
                    'message'=>"1. Exception ".$data->OK."\nUne erreur s'est produite. Veuillez contacter votre support",
                ],$data->OK);
            }
            $LiveToken = $data->LiveToken;

            return response()->json([
                'status' => 'success',
                'token' => $LiveToken,
            ],200);
        }else{
            return response()->json([
                'code' => $response->status(),
                'message' =>"2. Exception ".$response->status()." Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
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
                'message'=>"1. Exception 403 \nCe service n'est pas actif",
            ],403);
        }
        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if(!$apiCheck->checkUserValidity()){
            return response()->json([
                'status'=>'error',
                'message'=>"2. Exception 401 \nVotre compte est désactivé. Veuillez contacter votre distributeur",
            ],404);
        }

        // Vérifie si le solde de l'utilisateur lui permet d'effectuer cette opération
        if(!$apiCheck->checkUserBalance($montant)){
            return response()->json([
                'status'=>'error',
                'message'=>"3. Exception 403 \nVotre solde est insuffisant pour effectuer cette opération",
            ],403);
        }

        //Vérifie si l'utilisateur n'a pas initié une operation similaire dans les 5 dernières minutes

        if($apiCheck->checkFiveLastTransaction($customerNumber, $montant, $service)){
            return response()->json([
                'status'=>'error',
                'message'=>"4. Exception 403 \nUne transaction similaire a été faite il y'a moins de 5 minutes",
            ],403);
        }

        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$montant);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                "success" => false,
                "message" => "5. Exception ".$lacommission->getStatusCode()." \nImpossible de calculer la commission",
            ], $lacommission->getStatusCode());
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
        $init_transaction = $apiCheck->init_Depot($montant, $customerNumber, $service,"", $device, $latitude, $longitude, $place,1, Auth::user()->id,"");
        $dataInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>"6. Exception ".$init_transaction->getStatusCode()."\n".$dataInit->message,
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

                return response()->json([
                    'status'=>'error',
                    'message'=>"7. Exception ".$checkSolde->getStatusCode()."\nImpossible de vérifier le solde du wallet de l'agent",
                ],$checkSolde->getStatusCode());
            }
            if($checkSolde->getStatusCode()==200){
                $dataCheckSolde = json_decode($checkSolde->getContent());

                if($dataCheckSolde->success==false){

                    return response()->json([
                        'status'=>'error',
                        'message'=>"8. Exception 403 \nLe solde du compte M2U est insuffisant pour effectuer cette opération",
                    ],403);
                }
            }

            //On fait le dépot

            $endpoint = 'https://apps.m2u.money/CPAddMoney';
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
                    "SourceWallet" => "XAF-01-CM9539-001", //Le wallet de l'agent => Du cashPoint
                    "TargetWallet" => $accountNumber, //Le wallet du customer => Le champ AccountNumber
                    "FirstName" => $firstName,
                    "LastName" => $lastName,
                    "PhoneNumber" => $customerNumber,
                    "PartnerTellerID"=>Auth::user()->id,
                    "UseDefaultWallet" => "No",
                    "OTP" => "SibSnfeSdksSji2023_@" //Le password du Teller
                ]  );

            if($response->status()==200) {

                $json = json_decode($response, false);
                $dataResultat = collect($json)->first();
                if ($dataResultat->OK != 200) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "9. Exception ".$dataResultat->OK."\nUne error s'est produite. Veuillez contacter votre support",
                    ], $dataResultat->OK);
                }
                if ($dataResultat->Result != "Success") {
                    return response()->json([
                        'status' => 'error',
                        'message' => "10. Exception ".$dataResultat->OK."\nUne error s'est produite. Veuillez contacter votre support",
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
                        'reference_partenaire' => $dataResultat->TransactionID,
                        'balance_before' => $balanceBeforeAgent,
                        'balance_after' => $balanceAfterAgent,
                        'debit' => $montant,
                        'credit' => 0,
                        'status' => 1, //End successfully
                        'paytoken' => $dataResultat->TransactionID,
                        'date_end_trans' => Carbon::now(),
                        'description' => 'SUCCESSFUL',
                        'message' => $dataResultat->Description,
                        'commission' => $commission->commission_globale,
                        'commission_filiale' => $commissionFiliale,
                        'commission_agent' => $commissionAgent,
                        'commission_distributeur' => $commissionDistributeur,
                        'terminaison'=>'CALLBACK',
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

                    $idDevice = $device;
                    $services = Service::all();

                    $title = "Transaction en succès";
                    $message = "Le dépôt M2U de " . $montant . " F CFA a été effectué avec succès au ".$customerNumber." (ID transaction: ".$dataResultat->TransactionID.") le ".Carbon::now()->format('d/m/Y H:i:s');
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($idDevice, $title, $message);

                    return response()->json([
                        'success' => true,
                        'message' => $dataResultat->Description,
                        'textmessage' => $dataResultat->Description,
                        'reference' => $reference,
                        'data' => $response->body(),
                        'user'=>$userRefresh,
                        'transactions'=>$transactionsRefresh,
                        'services'=>$services
                    ], 200);
                }catch (\Exception $e){
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>"11. Exception ".$e->getCode()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                    ],$e->getCode());
                }
            }else{
                $title = "Transaction en échec";
                $message = "Le dépôt M2U de " . $montant . " F CFA au ".$customerNumber." est en échec";
                $appNotification = new ApiNotification();
                $envoiNotification = $appNotification->SendPushNotificationCallBack($device, $title, $message);
                return response()->json([
                    'code' => $response->status(),
                    'message' =>"Transaction en échec",
                ],$response->status());
            }
        }else{
            return response()->json([
                'status'=>'error',
                'message' =>"13. Exception ".$getToken->getStatusCode()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
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

            $endpoint = 'https://apps.m2u.money/GetCashTransferStatus';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type' => 'application/json',
                        'From' => $token,
                    ])

                ->Post($endpoint, [
                    "LoginName" => "CM949513",
                    "APIKey" => "oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv",
                    "AppID" => "8SZpExWP0fxu6rKQEDva03KVT",
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
                            'message' =>"1. Exception ".$data->OK."(".$data->ReturnCode.")\n".$data->Description,// "Wrong voucher number or security code",
                        ], 403);
                    }
                    if($data->ReturnCode=="1"){
                        return response()->json([
                            'success' => false,
                            'message' => "2. Exception ".$data->OK."(".$data->ReturnCode.")\n".$data->Description,//"This voucher is already used",
                        ], 403);
                    }
                    if($data->ReturnCode=="2"){
                        return response()->json([
                            'success' => false,
                            'message' => "3. Exception ".$data->OK."(".$data->ReturnCode.")\n".$data->Description,//"This transfer number is for another country",
                        ], 403);
                    }
                    if($data->ReturnCode=="3"){
                        return response()->json([
                            'success' => false,
                            'message' => "4. Exception ".$data->OK."(".$data->ReturnCode.")\n".$data->Description,//"This voucher has been cancelled by the originator",
                        ], 403);
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
                            'beneficiaryPhone' =>$data->Beneficiary->PhoneNumber, //substr($data->Beneficiary->PhoneNumber,3),

                            'amount' => $data->Sender->Amount,
                            "securityCode" => $request->SecurityCode,
                            "voucherNumber" => $request->VoucherNumber,
                            //  "transactionNumber" => $data->Sender->TransactionNumber,

                            'data' => $response->body(),
                        ], 200);
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => "5. Exception 403 \nUne erreur inatendue s'est produite, veuillez contacter votre superviseur si le problème persiste",
                        ], 403);
                    }

                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "6. Exception 403\nUne erreur inatendue s'est produite, veuillez contacter votre superviseur si le problème persiste",
                    ], $data->OK);
                }
            }
        }else{
            return response()->json([
                'success' => false,
                'message' => "7. Exception ".$getToken->getStatusCode()."\nUne erreur inatendue s'est produite, veuillez contacter votre superviseur si le problème persiste",
            ], $getToken->getStatusCode());
        }
    }

    public function M2U_RetraitCPPayCash(Request $request){ //CPPayCash
        $validator = Validator::make($request->all(), [

            "SecurityCode" => 'required',
            "VoucherNumber" => 'required',
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
                'message'=>"1. Exception 403 \nVotre compte est désactivé. Veuillez contacter votre distributeur",
            ],403);
        }


        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$request->Amount);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "2. Exception 403\nImpossible de calculer la commission",
            ], 403);
        }
        $commission=json_decode($lacommission->getContent());

        $commissionFiliale = doubleval($commission->commission_kiaboo);
        $commissionDistributeur=doubleval($commission->commission_distributeur);
        $commissionAgent=doubleval($commission->commission_agent);

        //Initie la transaction
        $device = $request->deviceId;
        $telephone = $request->TargetPhoneNumber;
        if(strlen($telephone)==12){
            if(substr($telephone,0,3)=="237"){
                $telephone = substr($telephone,-9);
            }
        }
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Retrait($request->Amount, $telephone, $service,"", $device, $latitude, $longitude, $place);
        $dataInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>"3. Exception ".$init_transaction->getStatusCode()."\n".$dataInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataInit->transId; //Id de la transaction initiée
        $reference = $dataInit->reference; //Référence de la transaction initiée

        //On génére le token pour la transaction

        $getToken=$this->M2U_getToken();
        $datagetToken = json_decode($getToken->getContent());

        if ($getToken->getStatusCode()==200) {

            $token = $datagetToken->token;

            $endpoint = 'https://apps.m2u.money/CPPayCash';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type' => 'application/json',
                        'From' => $token,
                    ])

                ->Post($endpoint, [
                    "LoginName"=>"CM949513",
                    "APIKey"=>"oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv",
                    "AppID"=>"8SZpExWP0fxu6rKQEDva03KVT",
                    "SecurityCode"=>$request->SecurityCode,
                    "VoucherNumber"=>$request->VoucherNumber,
                    "TargetPhoneNumber"=>$request->TargetPhoneNumber, //'237'.$request->TargetPhoneNumber,
                    "FirstName"=>$request->FirstName,
                    "LastName"=>$request->LastName,
                    "PartnerTellerID"=>Auth::user()->id,
                    "WalletNumber"=>"XAF-01-CM9539-001",
                    "OTP"=>"SibSnfeSdksSji2023_@"
                ]  );
            if($response->status()==200) {

                $json = json_decode($response, false);
                $dataResultat = collect($json)->first();
                if ($dataResultat->OK != 200) {
                    return response()->json([
                        'status' => 'error',
                        'message' =>"1 - ".$dataResultat->Description,// 'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }
                if ($dataResultat->ReturnCode != 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' =>"2 - ".$dataResultat->Description,//  'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }
                isset($dataResultat->Result) ? $result = true : $result = false;
                if($result==false){
                    return response()->json([
                        'status' => 'error',
                        'message' =>"4. Exception 404\n".$dataResultat->Description,//  'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }
                if ($result==true && $dataResultat->Result != "Success") {
                    return response()->json([
                        'status' => 'error',
                        'message' =>"4. Exception 404\n".$dataResultat->Description,//  'Une error s\'est produite. Veuillez contacter votre support',
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
                        'reference_partenaire' => $dataResultat->TransactionID, // $reference,
                        'balance_before' => $balanceBeforeAgent,
                        'balance_after' => $balanceAfterAgent,
                        'debit' => 0,
                        'credit' => $request->Amount,
                        'status' => 1, //End successfully
                        'paytoken' => $dataResultat->TransactionID, // $reference,
                        'date_end_trans' => Carbon::now(),
                        'description' => 'SUCCESSFUL',
                        'message' => $dataResultat->Description,
                        'commission' => $commission->commission_globale,
                        'commission_filiale' => $commissionFiliale,
                        'commission_agent' => $commissionAgent,
                        'commission_distributeur' => $commissionDistributeur,
                        'terminaison'=>'CALLBACK',
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

                    $idDevice = $device;
                    $title = "Transaction en succès";
                    $message = "Le retrait M2U Money de " . $request->Amount . " F CFA a été effectué avec succès au ".$request->TargetPhoneNumber." (ID transaction: ".$dataResultat->TransactionID."). le ".Carbon::now()->format('d/m/Y H:i:s');
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($idDevice, $title,  $message);

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
                    Log::error("M2U_Depot",[
                        "data"=>$e->getMessage(),
                        "code"=>$e->getCode(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' =>"5. Exception ".$e->getCode()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                    ],$e->getCode());
                }
            }else{
                Log::error("M2U_Depot",[
                    "data"=>$response->body(),
                    "code"=>$response->status(),
                ]);
                $title = "Transaction en échec";
                $message = "Le retrait M2U Money de " . $request->Amount . " F CFA au ".$request->TargetPhoneNumber." le ".Carbon::now()->format('d/m/Y H:i:s')." est en échec";
                $appNotification = new ApiNotification();
                $envoiNotification = $appNotification->SendPushNotificationCallBack($device, $title,  $message);
                return response()->json([
                    'code' => $response->status(),
                    'message' =>"6. Exception ".$response->status()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                ],$response->status());
            }
        }else{
            Log::error("M2U_Depot",[
                "data"=>$getToken->getContent(),
                "code"=>$getToken->getStatusCode(),
            ]);
            return response()->json([
                'status'=>'error',
                'message' =>"7. Exception ".$getToken->getContent()." Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                'response'=>json_decode($getToken->getContent()),
            ],$getToken->getStatusCode());
        }

    }

    public function M2U_ExecuteCashBack(Request $request){ //Retrait
        $validator = Validator::make($request->all(), [

            "TransactionNumber" => 'required',
            "OTP" => 'required',
            "Amount" => 'required|numeric',
            "CustomerPhoneNumber"=>'required',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $apiCheck = new ApiCheckController();
        $service = ServiceEnum::RETRAIT_M2U_CB->value;

        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if($apiCheck->checkUserValidity()==false){
            return response()->json([
                'status'=>'error',
                'message'=>"1. Exception 401\nVotre compte est désactivé. Veuillez contacter votre distributeur",
            ],404);
        }


        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$request->Amount);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "2. Exception 403\nImpossible de calculer la commission",
            ], 403);
        }
        $commission=json_decode($lacommission->getContent());

        $commissionFiliale = doubleval($commission->commission_kiaboo);
        $commissionDistributeur=doubleval($commission->commission_distributeur);
        $commissionAgent=doubleval($commission->commission_agent);

        //Initie la transaction
        $device = $request->deviceId;
        $telephone = $request->CustomerPhoneNumber;
        if(strlen($telephone)==12){
            if(substr($telephone,0,3)=="237"){
                $telephone = substr($telephone,-9);
            }
        }
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Retrait($request->Amount, $telephone, $service,"", $device, $latitude, $longitude, $place);
        $dataInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'status'=>'error',
                'message'=>"3. Exception ".$init_transaction->getStatusCode() ."\n".$dataInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataInit->transId; //Id de la transaction initiée
        $reference = $dataInit->reference; //Référence de la transaction initiée

        //On génére le token pour la transaction

        $getToken=$this->M2U_getToken();
        $datagetToken = json_decode($getToken->getContent());

        if ($getToken->getStatusCode()==200) {

            $token = $datagetToken->token;

            $endpoint = 'https://apps.m2u.money/ExecuteCashBack';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type' => 'application/json',
                        'From' => $token,
                    ])
                ->Post($endpoint, [
                    "TransactionNumber"=> $request->TransactionNumber,
                    "WalletNumber"=>"XAF-01-CM9539-001",
                    "OTP"=>$request->OTP,
                    "PIN"=>"SibSnfeSdksSji2023_@",
                    "PartnerTellerID"=>Auth::user()->id,
                ]  );
            Log::info("M2U_ExecuteCashBack",[
                "data"=>$response,
                "code"=>$response->status(),
                "request"=>$request->all(),
            ]);
            $json = json_decode($response, false);
            $dataResultat = collect($json)->first();
            $element = json_decode($response, associative: true);

            if($response->status()==200) {

                if(Arr::has($element[0], "OK")) {
                    if ($dataResultat->OK != 200) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>"4. Exception ".$dataResultat->OK."\n".$dataResultat->Description,// 'Une error s\'est produite. Veuillez contacter votre support',
                        ], 404);
                    }
                    if ($dataResultat->OK == "200") {
                        if ($dataResultat->Result == "Success") {
                           //On met à jour la table transaction
                            //Par mesure de sécurité je rappelle les données de l'utilisateur
                            $user = User::where('id', Auth::user()->id);
                            $balanceBeforeAgent = $user->get()->first()->balance_after;
                            $balanceAfterAgent = floatval($balanceBeforeAgent) + floatval($request->Amount);
                            //on met à jour la table transaction
                            try {
                                DB::beginTransaction();
                                $Transaction = Transaction::where('id', $idTransaction)->where('service_id', $service)->update([
                                    'reference_partenaire' => $dataResultat->TransactionID, // $reference,
                                    'balance_before' => $balanceBeforeAgent,
                                    'balance_after' => $balanceAfterAgent,
                                    'debit' => 0,
                                    'credit' => $request->Amount,
                                    'status' => 1, //End successfully
                                    'paytoken' => $request->TransactionNumber, // $reference,
                                    'date_end_trans' => Carbon::now(),
                                    'description' => 'SUCCESSFUL',
                                    'message' => $dataResultat->Description,
                                    'commission' => $commission->commission_globale,
                                    'commission_filiale' => $commissionFiliale,
                                    'commission_agent' => $commissionAgent,
                                    'commission_distributeur' => $commissionDistributeur,
                                    'terminaison'=>'CALLBACK',
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
                                    'reference_last_transaction' => $dataResultat->TransactionID,
                                    'remember_token' => $dataResultat->TransactionID,
                                    'total_commission' => $commission_agent,
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

                                $idDevice = $device;
                                $title = "Transaction en succès";
                                $message = "Le retrait M2U Money de " . $request->Amount . " F CFA a été effectué avec succès par le ".$request->TargetPhoneNumber." (".$dataResultat->TransactionID.") le".Carbon::now()->format('d/m/Y H:i:s');
                                $appNotification = new ApiNotification();
                                $envoiNotification = $appNotification->SendPushNotificationCallBack($idDevice, $title,  $message);


                                return response()->json([
                                    'success' => true,
                                    'message' => $dataResultat->Description.' Votre transaction a été effectuée avec succès',
                                    'textmessage' => $dataResultat->Description,
                                    'reference' => $dataResultat->TransactionID,
                                    'user'=>$userRefresh,
                                    'transactions'=>$transactionsRefresh,
                                ], 200);
                            }catch (\Exception $e){
                                DB::rollBack();
                                Log::error("M2U_CashBack",[
                                    "data"=>$e->getMessage(),
                                    "code"=>$e->getCode(),
                                ]);
                                return response()->json([
                                    'success' => false,
                                    'message' =>"5. Exception ".$e->getCode()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                                ],$e->getCode());
                            }
                        }
                    }
                }else{
                    Log::error("M2U_CashBack",[
                        "data"=>$response->body(),
                        "code"=>$response->status(),
                    ]);
                    $title = "Transaction en échec";
                    $message = "Le retrait CashBack M2U Money de " . $request->Amount . " F CFA au ".$request->TargetPhoneNumber." le ".Carbon::now()->format('d/m/Y H:i:s')." est en échec";
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($device, $title,  $message);
                    return response()->json([
                        'status' => 'error',
                        'message' =>"6. Exception 404\n".$dataResultat->Description,// 'Une error s\'est produite. Veuillez contacter votre support',
                    ], 404);
                }

            }else{
                Log::error("M2U_CashBack",[
                    "data"=>$response->body(),
                    "code"=>$response->status(),
                ]);
                return response()->json([
                    'code' => $response->status(),
                    'message' =>"7. Exception ".$response->status()."\n".$dataResultat->Description,//"4. Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                ],404);
            }
        }else{
            Log::error("M2U_CashBack",[
                "data"=>$getToken->getContent(),
                "code"=>$getToken->getStatusCode() ,
            ]);
            return response()->json([
                'status'=>'error',
                'message' =>"8. Exception ".$getToken->getStatusCode()."\nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                'response'=>json_decode($getToken->getContent()),
            ],$getToken->getStatusCode());
        }

    }

    public function M2U_CashBackStatus(Request $request){ //GetCashTransferStatus

        $validator = Validator::make($request->all(), [
            'TransactionNumber' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        //On génére le token pour la transaction

        $getToken=$this->M2U_getToken();
        $datagetToken = json_decode($getToken->getContent());

        if ($getToken->getStatusCode()==200) {

            $token = $datagetToken->token;

            $endpoint = 'https://apps.m2u.money/CashBackStatus';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type' => 'application/json',
                        'From' => $token,
                    ])

                ->Post($endpoint, [
                    "LoginName" => "CM949513",
                    "APIKey" => "oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv",
                    "AppID" => "8SZpExWP0fxu6rKQEDva03KVT",
                    "PIN" => "SibSnfeSdksSji2023_@", //  $request->PIN,
                    "TransactionNumber" => $request->TransactionNumber,
                ]  );

            $json = json_decode($response, false);
            $data=collect($json)->first();
            $element = json_decode($response, associative: true);

            if($response->status()==200) {

                if(Arr::has($element[0], "OK")) {
                    if ($data->OK == "200") {
                        if($data->TransactionExpired=="YES"){
                            return response()->json([
                                'success' => false,
                                'message' => "1. Exception \n".$data->Description, // $data->Result,
                                'ReturnCode'=>$data->ReturnCode,
                                'TransactionExpired'=> $data->TransactionExpired,
                                'PID'=> $data->PID,
                                'CPID'=> $data->CPID,
                                'Result'=> $data->Result,
                                'AmountToBeReceived' => $data->AmountToBeReceived,
                                'Amount'=> $data->Amount,
                                'Taxes' =>$data->Taxes,
                                'TotalAmount' => $data->TotalAmount,
                                "Description" => $data->Description,
                            ], 404);
                        }  else{
                            return response()->json([
                                'success' => true,
                                'message' =>$data->Result,
                                'ReturnCode'=>$data->ReturnCode,
                                'TransactionExpired'=> $data->TransactionExpired,
                                'PID'=> $data->PID,
                                'CPID'=> $data->CPID,
                                'Result'=> $data->Result,
                                'AmountToBeReceived' => $data->AmountToBeReceived,
                                'Amount'=> $data->Amount,
                                'Taxes' =>$data->Taxes,
                                'TotalAmount' => $data->TotalAmount,
                                "Description" => $data->Description,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => "1. Exception ".$data->OK." \n".$data->Description,
                        ], 404);
                    }
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => "2. Exception 404 \n".$data->Description,
                    ], 404);
                }
            }else{

                return response()->json([
                    'success' => false,
                    'message' => "3. Exception ".$response->status()."\n".$data->Description,
                ], 404);
            }
        }else{

            return response()->json([
                'success' => false,
                'message' => "4. Exception ".$getToken->getStatusCode()."\nUne erreur inatendue s'est produite, veuillez contacter votre superviseur si le problème persiste. Code error : ".$getToken->getStatusCode(),
            ], $getToken->getStatusCode());
        }
    }


    public function M2U_CheckSoldeTeller($amount, $token){
        $endpoint = 'https://apps.m2u.money/CPTransferConfirmation';
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
                "SourceWallet" => "XAF-01-CM9539-001"
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

    public function M2U_WalletCustomer($customerNumber)
    {

        if (strlen($customerNumber) !=9){
            return response()->json([
                'status'=>'error',
                'message'=>'Le numéro de téléphone incorrect'
            ],404);
        }

        $endpoint = 'https://apps.m2u.money/LocateWallet';
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                ])

            ->Post($endpoint, [
                "LoginName"=> "CM949513",
                "APIKey"=> "oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv",
                "AppID"=> "8SZpExWP0fxu6rKQEDva03KVT",
                "WalletNumber"=>'237'.$customerNumber,
            ]  );
        //dd($response);
        if($response->status()==401){
            return response()->json([
                'status' => 'echec',
                'message'=>'Aucun client trouvé',
            ],404);
        }

        if($response->status()==200){
            $json = json_decode($response, false);
            $data=collect($json)->first();
            $firstName = $data->FirstName;
            $lastName = $data->LastName;
            $accountNumber = $data->PID; //accountNumber;
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
            //$body = json_decode($response->body());
            return response()->json([
                'code' => $response->status(),
                'message' =>"1. Exception :".$response->status()." \nUne exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
            ],$response->status());
        }



    }

    public function M2U_Paiement(Request $request){
        $validator = Validator::make($request->all(), [
            "SourceWalletPhone" => 'required',
            "OTP" => 'required',
            "Amount" => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        $apiCheck = new ApiCheckController();
        $code = $apiCheck->genererChaineAleatoire(10);
        $service = ServiceEnum::PAYMENT_M2U->value;
        $customerNumber =$request->SourceWalletPhone;

        // Vérifie si le service est actif
        if($apiCheck->checkStatusService($service)==false){
            return response()->json([
                'status'=>'error',
                'message'=>"1. Exception 403 \nCe service n'est pas actif",
            ],403);
        }
        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if(!$apiCheck->checkUserValidity()){
            return response()->json([
                'status'=>'error',
                'message'=>"2. Exception 401 \nVotre compte est désactivé. Veuillez contacter votre distributeur",
            ],404);
        }

        //Je recupere les info du client

        $endpoint = 'https://apps.m2u.money/LocateUser';
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                ])

            ->Post($endpoint, [
                "LoginName"=> "CM949513",
                "APIKey"=> "oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv",
                "AppID"=> "8SZpExWP0fxu6rKQEDva03KVT",
                "PhoneNumber"=>'237'.$customerNumber,
            ]  );

        if($response->status()==401){
            return response()->json([
                'status' => 'echec',
                'message'=>'Aucun client trouvé',
            ],404);
        }

        if($response->status()==200){
            $json = json_decode($response, false);
            $data=collect($json)->first();
            //Je convertis en tableau associatif
            $element = json_decode($response, associative: true);
            if(!Arr::has($element[0], "Wallets")){ //On teste si l'utilisateur a un wallet actif
                return response()->json([
                    'status' => 'echec',
                    'message'=>"1. Exception 204\nCe numéro de client n'a pas de compte actif",
                ],404);
            }
            $SourceWallet = $data->Wallets[0]->AccountNumber;
            $OPT = $request->OTP;
            $TargetWallet ="XAF-01-CM949513";
            $LoginName="CM949513";
            $AppID="8SZpExWP0fxu6rKQEDva03KVT";
            $APIKey="oh09DFok0T4ecUz1kzw2o9SoVslEwE3eMpvgtpzrhE4uv";
            $Amount=$request->Amount;

            $endpointPaiement = 'https://apps.m2u.money/PaymentRequest';
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                    ])

                ->Post($endpointPaiement, [
                    "LoginName"=> $LoginName,
                    "APIKey"=> $APIKey,
                    "AppID"=> $AppID,
                    "OTP"=>$OPT,
                    "TargetWallet"=>$TargetWallet,
                    "SourceWallet"=>$SourceWallet,
                    "Amount"=>$Amount,
                ]  );
            Log::info("M2UPaiement",[
                "request"=>$request->all(),
                "response"=>$response,
            ]);
            if($response->status()==401){
                return response()->json([
                    'status' => 'echec',
                    'message' => response()->body(),
                ],404);
            }
            if($response->status()==200){
               return response()->json([
                   'status' => 'success',
                   'message' => $response->body(),
               ],200);

            }
        }
        //
    }

}
