<?php

namespace App\Http\Controllers\api;

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
use mysql_xdevapi\Result;

class ApiOMController extends Controller
{
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

    public function OM_Depot_init($token)
    {
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

    public function OM_Depot_execute($token, $payToken, $beneficiaire, $montant, $transId)
    {

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

    public function OM_Depot(Request $request){

        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:50|max:500000',
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

        //On genere le PayToken du depot
        $responseToken = $this->OM_GetTokenAccess();

        if($responseToken->getStatusCode() !=200){
            Log::error([
               "Resultat"=>false,
               "Code"=>$responseToken->getStatusCode(),
               "message"=>$responseToken->getContent(),
               "user"=>Auth::user()->id,
                "function"=>"OM_Depot",
            ]);
            return response()->json([
                "result"=>false,
                "message"=>"Exception : Une exception a été déclenché au moment de la génération du token"
            ], 401);
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $AccessToken = $dataAcessToken->access_token;
        $token = $AccessToken;

        //On initie le dépôt (Obtention du PayToken

        $responseInitDepot = $this->OM_Depot_init($token);
        if($responseInitDepot->getStatusCode() !=200){
            Log::error([
                "Resultat"=>false,
                "Code"=>$responseInitDepot->getStatusCode(),
                "message"=>$responseInitDepot->getContent(),
                "user"=>Auth::user()->id,
                "function"=>"OM_Depot",
            ]);
            return response()->json([
                "result"=>false,
                "message"=>"Exception : Une exception a été déclenché au moment de l'initialisation de la transaction"
            ], 401);
        }

        $dataInitDepot= json_decode($responseInitDepot->getContent());

    //    $reference = $dataInitDepot->transId;
        $payToken =$dataInitDepot->data->payToken;

    //    $description = $dataInitDepot->data->description;

        $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
           "payToken"=>$payToken,
        ]);

        $responseTraiteDepotOM = $this->OM_Depot_execute($token, $payToken, $customerNumber, $montant, $idTransaction);
        if($responseTraiteDepotOM->getStatusCode() !=200){
            Log::error([
                "Resultat"=>false,
                "Code"=>$responseTraiteDepotOM->getStatusCode(),
                "message"=>$responseTraiteDepotOM->getContent(),
                "user"=>Auth::user()->id,
                "function"=>"OM_Depot",
            ]);
            return response()->json([
                "result"=>false,
                "message"=>"Exception : Une exception a été déclenché au moment du traitement du dépôt"
            ], 401);
        }

        try{
            DB::beginTransaction();
            $resultat = json_decode($responseTraiteDepotOM->getContent());

            //Dépassement de plafond côté Orange Money
            $result = (array)$resultat;
            if (Arr::has($result, 'code')) {
                $data =json_decode($result["message"]);
                return response()->json([
                    'success' => false,
                    'message' => $data->message,
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
            $message = "Le dépôt OM de " . $montant . " F CFA a été effectué avec succès au ".$customerNumber;
            $subtitle ="Success";
            $appNotification = new ApiNotification();
            $envoiNotification = $appNotification->sendNotificationPushFireBase($idDevice, $title, $subtitle, $message);
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
                'message' => $resultat->message,
                'textmessage' => $resultat->message,
                'reference' => $resultat->data->txnid,
                'data' => $resultat,
                'user'=>$userRefresh,
                'transactions'=>$transactionsRefresh,
            ], 200);



        }catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
            ], 400);
        }
    }
}
