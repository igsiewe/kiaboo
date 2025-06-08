<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiProdOrangeMoneyController extends Controller
{

    protected $token;
    protected $auth;
    protected $auth_x_token;
    protected $channel;
    protected $pin;
    protected $url;

    public function __construct()
    {
        $this->url="https://omdeveloper-gateway.orange.cm/omapi/1.0.2";
        $this->token="";
        $this->auth="cEZJWTF5Wl9pR0hMRzBiZzBlOEJDUDhlOUxzYTpuRGppWTJ6UDZPY0Q2cktkVFg5RmE0eXoxYW9h"; //Utiliser pour générer le token
        $this->auth_x_token ="c2FuZGJveDpzYW5kYm94";
        $this->channel="691301143";
        $this->pin="2222";
        $getTokenResponse = $this->OM_GetTokenAccess();

        if($getTokenResponse->status()==200){
            $dataToken = json_decode($getTokenResponse->content());
            $this->token = $dataToken->access_token;
        }
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
            ->Post('https://omdeveloper.orange.cm/oauth2/token');
            Log::info([
                'function' => "OM_GetTokenAccess",
                'url'=>"https://omdeveloper.orange.cm/oauth2/token",
                'request'=>[
                    'header'=>[
                    "Authorization"=>"Basic ".$this->auth
                    ],
                    'body'=>'grant_type=client_credentials',
                    'type'=>'application/x-www-form-urlencoded',
                ],
                'response'=>$response->body(),
                'statusCode'=>$response->status(),
            ]);
            if($response->status()==200){
                 return response()->json($response->json());
            }
           else{
                Log::error([
                    'function' => "OM_GetTokenAccess",
                    'code'=> $response->status(),
                    'response'=>$response->body(),
                ]);
                return response()->json([
                    'status'=>'error',
                    'message'=>"Erreur innattendue : ".$response->body()
                ],$response->status());

            }

    }

    public function OM_getPMPayToken(){

        $url = $this->url."/mp/init";
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders([
                    "X-AUTH-TOKEN"=>$this->auth_x_token,
                    "WSO2-Authorization"=>"Bearer ".$this->token,
                    "accept"=>"application/json"
                ]
            )
            ->Post($url);

        if($response->status()==401){
                $data = json_decode($response->body());
                return response()->json([
                    "success"=>false,
                    "statusCode"=>$data->message,
                    "message"=>$data->description,
                ], 403);
        }
        if($response->status()!=200){
            return response()->json([
                "success"=>false,
                "statusCode"=>$response->status(),
                "message"=>$response->body(),
            ], 404);
        }
        $dataResponse = json_decode($response);
        $payToken = $dataResponse->data->payToken;

        return response()->json([
            "success"=>true,
            "payToken"=>$payToken,
            "message"=>$dataResponse->message
        ], $response->status());

    }

    public function OM_Payment(Request $request){

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::PAYMENT_OM->value;
        $user = User::where("telephone",$request->agentNumber)->where('type_user_id', UserRolesEnum::AGENT->value)->get();
        $amount=$request->data["amount"];
        $customer=$request->data["phone"];

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
          //  if($user->count()==0 || $user->first()->status ==0){
                return response()->json([
                    'success'=>false,
                    'statusCode'=>'ERR-NOT-PERMISSION',
                    'message'=>"The agent used does not have the necessary permissions with your profil",
                ],403);
           // }
        }
        //Verifie le statut de l'id transaction cote marchand

       // $checkTransactionExternalId = Transaction::where('marchand_transaction_id',$request->marchandTransactionId)->select('source')->get(); // Je cherche s'l y'a une transaction avec ce numero merchand_id et je recupère tous les aagents qui l'ont fait

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
                'message' => "The merchand transaction ID used exists already : ".$request->marchandTransactionId,
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

        //Référence de la transaction :On génère le payToken
        $dataPayTokenResponse = $this->OM_getPMPayToken();
        $dataPayToken = json_decode($dataPayTokenResponse->content());
        if($dataPayTokenResponse->status()!=200){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$dataPayToken->statusCode,
                    'message'=>$dataPayToken->message,
                ],$dataPayTokenResponse->status()
            );
        }
        $payToken = $dataPayToken->payToken;

        //On gardee l'UID de la transaction initiee
        $saveUID = Transaction::where('id',$idTransaction)->update([
            "paytoken"=>$payToken
        ]);

        $customerPhone = "237".$customer;
        $partenaire = Distributeur::where("id",Auth::user()->distributeur_id)->get()->first()->name_distributeur;
        $url = $this->url."/mp/pay";
        $description ="Transaction initie by ".$user->first()->telephone. " de ".$partenaire;
        $data = [
            "notifUrl"=> "https://kiaboopay.com/api/om/callback/pm",
            "channelUserMsisdn"=> $this->channel,
            "amount"=> $amount,
            "subscriberMsisdn"=> "$customer",
            "pin"=> $this->pin,
            "orderId"=> $request->marchandTransactionId,
            "description"=>$description,
            "payToken"=> $payToken
        ];

        try{
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
                    "subscriberMsisdn": "'.$customer.'",
                    "channelUserMsisdn": "'.$this->channel.'",
                    "amount": "'.$amount.'",
                    "description": "'.$description.'",
                    "orderId": "'.$request->marchandTransactionId.'",
                    "pin": "'.$this->pin.'",
                    "payToken": "'.$payToken.'",
                    "notifUrl": "https://kiaboopay.com/api/om/callback/pm"
                    }',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'X-AUTH-TOKEN: '.$this->auth_x_token,
                    'Content-Type: application/json',
                    'WSO2-Authorization: Bearer '.$this->token,
                ),
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);
            $dataResponse = json_decode($response);
            Log::info([
                "fontion"=>"OM_Payment",
                "url"=>$url,
                "request"=>$data,
                "response"=>$dataResponse
            ]);
        }catch (Exception $e){
            throw $e;
            Log::error([
                "fontion"=>"OM_Payment",
                "url"=>$url,
                "request"=>$data,
                "error"=>$e->getMessage()
            ]);
            return response()->json([
                "fontion"=>"OM_Payment",
                "url"=>$url,
                "request"=>$data,
                "response"=>$e->getMessage()
            ],$e->getCode());
        }


        if($httpcode==200){
            //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)

            //On change le statut de la transaction dans la base de donnée

            $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                'reference_partenaire'=>$payToken,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$amount,
                'status'=>2, // Pending
                'paytoken'=>$payToken,
                'date_end_trans'=>Carbon::now(),
                'description'=>$dataResponse->data->status, //'PENDING',
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
                    'message'=>$dataResponse->data->inittxnmessage,
                    'paytoken'=>$payToken,
                    'transactionId'=>$reference,//$idTransaction,
                    'data'=>[
                        'status'=>$dataResponse->data->status,
                        'dateTransaction'=>Carbon::now(),
                        'currency'=>'XAF',
                        'amount'=>$amount,
                        'fees'=>$fees->fees_globale,
                        'agent'=>$user->first()->telephone,
                        'customer'=>$customer,
                        'marchandTransactionID'=>$request->marchandTransactionId,
                    ]
                ],202
            );

        }else{
            Log::error([
                'code'=> $httpcode,
                'function' => "MOMO_PAYMENT",
                'response'=>$response,
                'user' => $user->first()->id,
                'request' => $request->all()
            ]);
            return response()->json(
                [
                    'status'=>$httpcode,
                    'message'=>$response,
                ],$httpcode
            );
        }
    }




    public function OM_Payment_Push($transactionId){
        // On cherche la transaction dans la table transaction

        $transaction = Transaction::where("reference", $transactionId)->where('service_id',ServiceEnum::PAYMENT_OM->value)->get();
        if($transaction->count()==0){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>"ERR-NO-MP-PAYTOKEN-FOUND",
                    'message'=>"This id transaction does not exist"
                ],404
            );
        }

        $service = Service::where("id",$transaction->first()->service_id)->get();

        if($service->first()->type_service_id !=TypeServiceEnum::PAYMENT->value){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>"ERR-NO-MP-PAYTOKEN-FOUND",
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

        $payToken = $transaction->first()->paytoken;
        $http = $this->url."/mp/push/".$payToken;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                "X-AUTH-TOKEN"=>$this->auth_x_token,
                "WSO2-Authorization"=>"Bearer ".$this->token,
                "accept"=>"application/json"
            ])->Get($http);
        Log::info([
            "fonction"=>"OM_Payment_Push",
            "url"=>$http,
            "status"=>$response->status(),
            "response"=>$response->body(),
        ]);
        $data = json_decode($response->body());

        if($response->status()==200){
                return response()->json(
                    [
                        'success'=>true,
                        'statusCode'=>$data->data->status,
                        'message'=>$data->data->inittxnmessage,
                        'data'=>[
                            'currency'=>'XAF',
                            'transactionId'=>$transactionId,
                            'dateTransaction'=>$transaction->first()->date_transaction,
                            'amount'=>$transaction->first()->credit,
                            'agent'=>User::where("id", $transaction->first()->source)->first()->telephone,
                            'customer'=>$transaction->first()->customer_phone,
                        ]
                    ],200
                );
        }else{
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$data->data->status,
                    'message'=>$data->message

                ],$response->status()
            );
        }

    }



    public function OM_Payment_Status($transactionId){
        // On cherche la transaction dans la table transaction

        $transaction = Transaction::where("reference", $transactionId)->get();
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

        $payToken = $transaction->first()->paytoken;
        $http = $this->url."/mp/paymentstatus/".$payToken;

        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                "X-AUTH-TOKEN"=>$this->auth_x_token,
                "WSO2-Authorization"=>"Bearer ".$this->token,
                "accept"=>"application/json"
            ])->Get($http);

        Log::info([
            "fonction"=>"OM_Payment_Status",
            "url"=>$http,
            "status"=>$response->status(),
            "response"=>$response->body(),
        ]);

        $data = json_decode($response->body());

        if($response->status()==200){
            return response()->json(
                [
                    'success'=>true,
                    'statusCode'=>$data->data->status,
                    'message'=>$data->data->status=="PENDING"?$data->data->inittxnmessage:$data->message,
                    'data'=>[
                        'currency'=>'XAF',
                        'transactionId'=>$transactionId,
                        'dateTransaction'=>$transaction->first()->date_transaction,
                        'amount'=>$transaction->first()->credit,
                        'agent'=>User::where("id", $transaction->first()->source)->first()->telephone,
                        'customer'=>$transaction->first()->customer_phone,
                    ]
                ],200
            );
        }else{
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$data->data->status,
                    'message'=>$data->message

                ],$response->status()
            );
        }

    }


    public function OM_getCashInPayToken(){

        $url = $this->url."/cashin/init";
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders([
                    "X-AUTH-TOKEN"=>$this->auth_x_token,
                    "WSO2-Authorization"=>"Bearer ".$this->token,
                    "accept"=>"application/json"
                ]
            )
            ->Post($url);

        log::info([
            "function"=>"OM_getCashInPayToken",
            "response"=>$response->body(),
            "statusCode"=>$response->status(),
            "data"=>$response->json(),
        ]);
        if($response->status()==401){
            $data = json_decode($response->body());
            return response()->json([
                "success"=>false,
                "statusCode"=>$data->message,
                "message"=>$data->description,
            ], 403);
        }
        if($response->status()!=200){
            return response()->json([
                "success"=>false,
                "statusCode"=>$response->status(),
                "message"=>$response->body(),
            ], 403);
        }
        $dataResponse = json_decode($response);
        $payToken = $dataResponse->data->payToken;

        return response()->json([
            "success"=>true,
            "payToken"=>$payToken,
            "message"=>$dataResponse->message
        ], $response->status());

    }

//    /**
//     * @OA\Post(
//     * path="/api/v1/prod/om/cashin",
//     * summary="Request to make a OM deposit",
//     * description="This request is used to deposit money into a customer's account using the OM service",
//     * security={{"bearerAuth":{}}},
//     * tags={"Cashin"},
//     * @OA\RequestBody(
//     *    required=true,
//     *    description="Request to make a OM payment",
//     *    @OA\JsonContent(
//     *       required={"agentNumber","marchandTransactionId","phone","amount"},
//     *       @OA\Property(property="agentNumber", type="string", example="659657424"),
//     *       @OA\Property(property="marchandTransactionId", type="string", example="12354"),
//     *       @OA\Property(
//     *           type="object",
//     *           property="data",
//     *           @OA\Property(property="phone", type="number", example="659657424"),
//     *           @OA\Property(property="amount", type="number", example="200"),
//     *       )
//     *    ),
//     * ),
//     * @OA\Response(
//     *    response=200,
//     *    description="Payment initiated successfully",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="true"),
//     *       @OA\Property(property="statusCode", type="string", example="PAYMENT INITIATED"),
//     *       @OA\Property(property="message", type="string", example="payment initiate successfully"),
//     *      @OA\Property(property="paytoken", type="string", example="Payment token"),
//     *     @OA\Property(property="transactionId", type="string", example="Reference transaction for any request"),
//     *    ),
//     * ),
//     * @OA\Response(
//     *        response=208,
//     *        description="you do not have the necessary permissions",
//     *        @OA\JsonContent(
//     *           @OA\Property(property="success", type="boolean", example="false"),
//     *           @OA\Property(property="statusCode", type="string", example="ERR-MERCHAND-TRANSACTION-ID-DUPLICATE"),
//     *           @OA\Property(property="message", type="string", example="The transaction ID used by the merchant already exists"),
//     *            @OA\Property(
//     *            type="object",
//     *            property="data",
//     *            @OA\Property(property="status", type="string", example="Transaction status"),
//     *            @OA\Property(property="transactionId", type="string", example="transacton id database"),
//     *            @OA\Property(property="dateTransaction", type="date", example="Date transaction"),
//     *            @OA\Property(property="amount", type="number", example="amount of transaction"),
//     *            @OA\Property(property="fees", type="number", example="transaction fees"),
//     *            @OA\Property(property="agent", type="string", example="agent who initiate transaction"),
//     *            @OA\Property(property="customer", type="number", example="customer phone number"),
//     *            @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
//     *            )
//     *        )
//     *   ),
//     *      @OA\Response(
//     *     response=400,
//     *     description="Bad request",
//     *     @OA\JsonContent(
//     *        @OA\Property(property="success", type="boolean", example="false"),
//     *        @OA\Property(property="statusCode", type="string", example="ERR-INVALID-DATA-SEND"),
//     *        @OA\Property(property="message", type="string", example="Bad request, invalid data was sent in the request"),
//     *     )
//     *  ),
//     *  @OA\Response(
//     *       response=403,
//     *       description="you do not have the necessary permissions",
//     *       @OA\JsonContent(
//     *          @OA\Property(property="success", type="boolean", example="false"),
//     *          @OA\Property(property="statusCode", type="string", example="ERR-NOT-PERMISSION"),
//     *          @OA\Property(property="message", type="string", example="you do not have the necessary permissions"),
//     *       )
//     *  ),
//     *  @OA\Response(
//     *      response=422,
//     *      description="attribute invalid",
//     *      @OA\JsonContent(
//     *         @OA\Property(property="success", type="boolean", example="false"),
//     *         @OA\Property(property="statusCode", type="string", example="ERR-ATTRIBUTES-INVALID"),
//     *         @OA\Property(property="message", type="string", example="attribute not valid"),
//     *      )
//     *   ),
//     *
//     * @OA\Response(
//     *    response=500,
//     *    description="an error occurred",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
//     *       @OA\Property(property="message", type="string", example="an error occurred"),
//     *    )
//     *  )
//     * )
//     */
    public function OM_CashIn(Request $request){

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::DEPOT_OM->value;
       // $user = User::where("telephone",$request->agentNumber)->where('type_user_id', UserRolesEnum::AGENT->value)->get();
        $user = User::where("telephone",Auth::user()->telephone)->where('type_user_id', UserRolesEnum::AGENT->value)->get(); //On aurait pu s'en passer et utiliser Auth::user() directement
        $amount=$request->amount;
        $customer=$request->phone;

        // Vérifie si l'utilisateur est autorisé à faire cette opération

        if($user->count()==0){
            return response()->json([
                'success'=>false,
                'statusCode'=>'ERR-AGENT-NOT-VALID',
                'message'=>"1. The agent used is not found",
            ],404);
        }

        if($user->first()->status ==0){
            return response()->json([
                'success'=>false,
                'statusCode'=>'ERR-NOT-PERMISSION',
                'message'=>"2. The agent used does not have the necessary permissions",
            ],403);
        }

        //On se rassure que l'utilisateur est bien rattaché au compte connecté

        if($user->first()->distributeur_id !=Auth::user()->distributeur_id){
            //  if($user->count()==0 || $user->first()->status ==0){
            return response()->json([
                'success'=>false,
                'statusCode'=>'ERR-NOT-PERMISSION',
                'message'=>"3. The agent used does not have the necessary permissions",
            ],403);
            // }
        }
        //Verifie le statut de l'id transaction cote marchand

        // $checkTransactionExternalId = Transaction::where('marchand_transaction_id',$request->marchandTransactionId)->select('source')->get(); // Je cherche s'l y'a une transaction avec ce numero merchand_id et je recupère tous les aagents qui l'ont fait

//        $distributeurAuquelAppartienAgent = $user->first()->distributeur_id;
//
//        $checkTransactionExternalId = DB::table('transactions')
//            ->join('users', 'transactions.source', '=', 'users.id')
//            ->select('transactions.*')
//            ->where('transactions.marchand_transaction_id', $request->marchandTransactionId)
//            ->where('users.distributeur_id', $distributeurAuquelAppartienAgent)
//            ->get();
//
//        if($checkTransactionExternalId->count()>0){
//            return response()->json([
//                'success'=>false,
//                'statusCode'=>"ERR-MERCHAND-TRANSACTION-ID-DUPLICATE",
//                'message' => "The merchand transaction ID used exists already : ".$request->marchandTransactionId,
//                'data'=>[
//                    'status' => $checkTransactionExternalId->first()->description,
//                    'transactionId'=>$checkTransactionExternalId->first()->reference,
//                    'dateTransaction'=>$checkTransactionExternalId->first()->date_transaction,
//                    'amount'=>$checkTransactionExternalId->first()->credit,
//                    'fees'=>$checkTransactionExternalId->first()->fees_collecte,
//                    'agent'=>$user->first()->telephone,
//                    'customer'=>$checkTransactionExternalId->first()->customer_phone,
//                    'marchandTransactionID'=>$checkTransactionExternalId->first()->marchand_transaction_id,
//                ]
//            ], 208);
//        }

        // Vérifie si le service est actif
        if($apiCheck->checkStatusService($service)==false){
            return response()->json([
                'success'=>false,
                'statusCode'=>"ERR-SERVICE-PARTNER-NOT-AVAILABLE",
                'message'=>"4. Ce service n'est pas actif",
            ],403);
        }
        // Vérifie si le solde de l'utilisateur lui permet d'effectuer cette opération
        if(!$apiCheck->checkUserApiBalance($user->first()->id, $amount)){
            return response()->json([
                'success'=>false,
                'statusCode'=>'ERR-INSUFFICIENT-BALANCE',
                'message'=>'5. Votre solde est insuffisant pour effectuer cette opération',
            ],403);
        }

        //Vérifie si l'utilisateur n'a pas initié une operation similaire dans les 5 dernières minutes

        if($apiCheck->checkFiveLastTransaction($customer, $customer, $service)){
            return response()->json([
                'success'=>false,
                'statusCode'=>'ERR-TRANSACTION-SIMILAR-FOUND',
                'message'=>'6. Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ],403);
        }

        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$amount);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "7. Impossible de calculer la commission",
            ], 400);
        }

        //Initie la transaction
       // $marchandTransactionId = $request->marchandTransactionId;
        $marchandTransactionId = "Kiaboo";
        $init_transaction = $apiCheck->init_Depot($amount, $customer, $service, "","", "", "", "",2,$user->first()->id,$marchandTransactionId);
        $dataTransactionInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'success'=>false,
                'statusCode'=>'error',
                'message'=>"8. ".$dataTransactionInit->message,
            ],$init_transaction->getStatusCode());
        }
        $idTransaction = $dataTransactionInit->transId; //Id de la transaction initiée
        $reference = $dataTransactionInit->reference; //Référence de la transaction initiée

        //Référence de la transaction :On génère le payToken
        $dataPayTokenResponse = $this->OM_getCashInPayToken();
        $dataPayToken = json_decode($dataPayTokenResponse->content());

        if($dataPayTokenResponse->status()!=200){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$dataPayToken->statusCode,
                    'message'=>"9. ".$dataPayToken->message,
                ],$dataPayTokenResponse->status()
            );
        }
        $payToken = $dataPayToken->payToken;

        //On gardee l'UID de la transaction initiee
        $saveUID = Transaction::where('id',$idTransaction)->update([
            "paytoken"=>$payToken
        ]);

        $customerPhone = $customer;
        $partenaire = Distributeur::where("id",Auth::user()->distributeur_id)->get()->first()->name_distributeur;

        //On envoie la requête à OM
        $url = $this->url."/cashin/pay";
        $description ="Transaction cashin initiate by ".$user->first()->telephone. " de ".$partenaire;
        $data = [
            "channelUserMsisdn"=> $this->channel,
            "amount"=> $amount,
            "subscriberMsisdn"=> $customer,
            "pin"=> $this->pin,
            "orderId"=> $marchandTransactionId,
            "description"=>$description,
            "payToken"=> $payToken
        ];

        try{
            $curl = curl_init();
             curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
              "channelUserMsisdn": "'.$this->channel.'",
              "amount": "'.$amount.'",
              "subscriberMsisdn": "'.$customer.'",
              "pin": "'.$this->pin.'",
              "orderId": "10",
              "description": "'.$description.'",
              "payToken": "'.$payToken.'",
              "notifUrl": "https://kiaboopay.com/api/om/callback/pm"
            }',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'X-AUTH-TOKEN: '.$this->auth_x_token,
                    'Content-Type: application/json',
                    'WSO2-Authorization: Bearer '.$this->token
                ),
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error_message = "";
            if ($response === false) {
                $error_message = curl_error($curl);
            }
            curl_close($curl);
            $dataResponse = json_decode($response);
        }catch (Exception $e){
            return response()->json([
                'code'=>$e->getCode(),
                'status'=>'error',
                'message'=>"10. ".$e->getMessage()
            ],$e->getCode());
        }


        if($httpcode==200){
            //La transaction s'est bien déroulée
            try{
               // DB::beginTransaction();
                //On Calcule la commission
                $commission=json_decode($lacommission->getContent());
                $commissionFiliale = doubleval($commission->commission_kiaboo);
                $commissionDistributeur=doubleval($commission->commission_distributeur);
                $commissionAgent=doubleval($commission->commission_agent);

                $balanceBeforeAgent = $user->first()->balance_after;
                $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($amount);
                //on met à jour la table transaction

                $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                    // 'reference_partenaire'=>$referenceID, //$financialTransactionId,
                    'balance_before'=>$balanceBeforeAgent,
                    'balance_after'=>$balanceAfterAgent,
                    'debit'=>$amount,
                    'credit'=>0,
                    'status'=>1, //End successfully
                    'paytoken'=>$payToken,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$dataResponse->data->status,
                    'message'=>$dataResponse->message,
                    'commission'=>$commission->commission_globale,
                    'commission_filiale'=>$commissionFiliale,
                    'commission_agent'=>$commissionAgent,
                    'commission_distributeur'=>$commissionDistributeur,
                    'reference_partenaire'=>$payToken,
                ]);

                //on met à jour le solde de l'utilisateur

                //La commmission de l'agent après chaque transaction

                $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",$user->first()->id)->sum("commission_agent");

                $debitAgent = DB::table("users")->where("id", $user->first()->id)->update([
                    'balance_after'=>$balanceAfterAgent,
                    'balance_before'=>$balanceBeforeAgent,
                    'last_amount'=>$amount,
                    'date_last_transaction'=>Carbon::now(),
                    'user_last_transaction_id'=>$user->first()->id,
                    'last_service_id'=>ServiceEnum::DEPOT_OM->value,
                    'reference_last_transaction'=>$reference,
                    'remember_token'=>$payToken,
                    'total_commission'=>$commission_agent,
                ]);
                return response()->json(
                    [
                        'success'=>true,
                        'statusCode'=>"PAYMENT-INITIATE-SUCCESSFULLY",
                        'message'=>$dataResponse->message,
                        'paytoken'=>$payToken,
                        'transactionId'=>$reference,//$idTransaction,
                    ],200
                );
            }catch (Exception $e){
                return response()->json([
                    'code'=>$e->getCode(),
                    'status'=>'error',
                    'message'=>$e->getMessage()
                ],$e->getCode());
            }
        }else{

            return response()->json([
                'code' => $httpcode,
                'message'=>"11. Erreur ".$httpcode." : ".$error_message
            ],$httpcode);
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


        $endpoint = $this->url."/infos/subscriber/customer/".$customerNumber;
        try{
                 $curl = curl_init();

                 curl_setopt_array($curl, array(
                     CURLOPT_URL => $endpoint,
                     CURLOPT_RETURNTRANSFER => true,
                     CURLOPT_ENCODING => '',
                     CURLOPT_MAXREDIRS => 10,
                     CURLOPT_TIMEOUT => 0,
                     CURLOPT_FOLLOWLOCATION => true,
                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                     CURLOPT_CUSTOMREQUEST => 'POST',
                     CURLOPT_POSTFIELDS => '{
                  "channelMsisdn": '.$this->channel.',
                  "pin": '.$this->pin.'
                }',
                     CURLOPT_HTTPHEADER => array(
                         'accept: application/json',
                         'X-AUTH-TOKEN: '.$this->auth_x_token,
                         'Content-Type: application/json',
                         'WSO2-Authorization: Bearer '.$this->token
                     ),
                 ));

                 $response = curl_exec($curl);
                 $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                 curl_close($curl);


                if($httpcode==200){

                    $data = json_decode($response, false);
                    $firstName = $data->data->firstName;
                    $lastName = $data->data->lastName;

                    return response()->json([
                        'status' => 'success',
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                    ],200);

                }else{

                    $error_message = curl_error($curl);
                    return response()->json([
                        'code' => $httpcode,
                        'message'=>"Erreur ".$httpcode." : ".$error_message
                    ],$httpcode);
                }
        }catch (Exception $e){
                 return response()->json([
                     'code'=>$e->getCode(),
                     'status'=>'error',
                     'message'=>$e->getMessage()
                 ],$e->getCode());
        }

    }
}
