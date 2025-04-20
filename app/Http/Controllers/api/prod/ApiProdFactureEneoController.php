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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiProdFactureEneoController extends Controller
{
    public function ENEO_CheckFactureStatus($numFacture){ //GetCashTransferStatus

        if (strlen($numFacture) !=9){
            return response()->json([
                'status'=>'error',
                'message'=>'Le numéro de la facture est incorrect'
            ],404);
        }

        return response()->json([
            'success' => true,
            'message' => "Facture valide",
            'amount'=>rand(5000,200000),
            'numContrat'=> rand(2000000,9999999),
            'numFacture'=> $numFacture,
            'ownerName'=> strtoupper(fake()->name()),
            'customerPhone'=>"6".rand(60000000,99999999),
        ], 200);

    }

    public function ENEO_PayMentFacture(Request $request){

        return response()->json([
            'success' => false,
            'message' => "Demande non approuvée par KIABOO",
        ], 404);


        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'numFacture' => 'required|numeric|digits:9',
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

        $service = ServiceEnum::FACTURE_ENEO->value;
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

        Log::info([
            "Service"=>ServiceEnum::FACTURE_ENEO->name,
            "url"=>"https://proxy.momoapi.mtn.com/disbursement/v1_0/transfer",
            "requete"=>[
                "amount" => $montant,
                "currency" => "XAF",
                "externalId" => $idTransaction,
                "payee" => [
                    "partyIdType" => "MSISDN",
                    "partyId" => $customerPhone,
                ],
                "payerMessage" => "Agent :".Auth::user()->telephone,
                "payeeNote" => "Agent : ".Auth::user()->telephone
            ],
            "reponseStatus"=>json_decode($response->status()),
            "reponseBody"=>json_decode($response->body()),
        ]);
        if($response->status()==202){

            $checkStatus = $this->MOMO_Depot_Status( $accessToken, $subcriptionKey, $referenceID);
            $datacheckStatus = json_decode($checkStatus->getContent());

            if($checkStatus->getStatusCode() !=200){
                $updateTransaction=Transaction::where("id",$idTransaction)->update([
                    'status'=>2, // Le dépôt n'a pas abouti, on passe en statut pending
                    //'reference_partenaire'=>$data->financialTransactionId,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$datacheckStatus->description,
                    'message'=>$datacheckStatus->message." - Vérifier le status dans la liste des en cours",
                ]);

                return response()->json([
                    'status'=>'error',
                    'message'=>$datacheckStatus->message,
                ],$checkStatus->getStatusCode());
            }

            try {
//                DB::beginTransaction();
//                //On Calcule la commission
//                $commission=json_decode($lacommission->getContent());
//                $commissionFiliale = doubleval($commission->commission_kiaboo);
//                $commissionDistributeur=doubleval($commission->commission_distributeur);
//                $commissionAgent=doubleval($commission->commission_agent);
//
//                $user = User::where('id', Auth::user()->id);
//                $balanceBeforeAgent = $user->get()->first()->balance_after;
//                $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($montant);
//                //on met à jour la table transaction
//
//                $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
//                   // 'reference_partenaire'=>$referenceID, //$financialTransactionId,
//                    'balance_before'=>$balanceBeforeAgent,
//                    'balance_after'=>$balanceAfterAgent,
//                    'debit'=>$montant,
//                    'credit'=>0,
//                    'status'=>1, //End successfully
//                    'paytoken'=>$referenceID,
//                    'date_end_trans'=>Carbon::now(),
//                    'description'=>$datacheckStatus->description,
//                    'message'=>'Le dépôt a été effectué avec succès',
//                    'commission'=>$commission->commission_globale,
//                    'commission_filiale'=>$commissionFiliale,
//                    'commission_agent'=>$commissionAgent,
//                    'commission_distributeur'=>$commissionDistributeur,
//                ]);
//
//                //on met à jour le solde de l'utilisateur
//
//                //La commmission de l'agent après chaque transaction
//
//                $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");
//
//                $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
//                    'balance_after'=>$balanceAfterAgent,
//                    'balance_before'=>$balanceBeforeAgent,
//                    'last_amount'=>$montant,
//                    'date_last_transaction'=>Carbon::now(),
//                    'user_last_transaction_id'=>Auth::user()->id,
//                    'last_service_id'=>ServiceEnum::DEPOT_OM->value,
//                    'reference_last_transaction'=>$reference,
//                    'remember_token'=>$referenceID,
//                    'total_commission'=>$commission_agent,
//                ]);

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

                $envoiNotification = $appNotification->SendPushNotification($idDevice, $title, $message); //Push notification sur le telephone de l'agent
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
}
