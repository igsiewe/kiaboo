<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiProdOrangeMoneyController extends Controller
{

    protected $token;
    protected $auth;
    protected $auth_x_token;
    protected $channel;
    protected $pin;

    public function __construct()
    {
        $this->token="";
        $this->auth="cEZJWTF5Wl9pR0hMRzBiZzBlOEJDUDhlOUxzYTpuRGppWTJ6UDZPY0Q2cktkVFg5RmE0eXoxYW9h"; //Utiliser pour générer le token
        $this->auth_x_token ="bHluZS1jbGF1ZGUua29tYm91QGtpYWJvby5uZXQ6MjQwNjExOTdhMzI4ZTBlOWNmZGZmNGQ3Zjc=";//base64_encode("lyne-claude.kombou@kiaboo.net:24061197a328e0e9cfdff4d7f7") ;//
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

    public function OM_getPayToken(){

        $url = "https://omdeveloper-gateway.orange.cm/omapi/1.0.2/mp/init";
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders([
                    "X-AUTH-TOKEN"=>$this->auth_x_token,
                    "WSO2-Authorization"=>"Bearer ".$this->token,
                    "accept"=>"application/json"
                ]
            )
            ->Post($url);

        log::info([
            "function"=>"OM_getPayToken.",
            "response"=>$response->body(),
            "statusCode"=>$response->status(),
        ]);
        if($response->status()==401){
                $data = json_decode($response->body());
                return response()->json([
                    "success"=>false,
                    "statusCode"=>$data->message,
                    "message"=>$data->description,
                ], 401);
        }
        if($response->status()!=200){
            return response()->json([
                "success"=>false,
                "statusCode"=>$response->status(),
                "message"=>$response->body(),
            ], 400);
        }
        $dataResponse = json_decode($response);
        $payToken = $dataResponse->data->payToken;

        return response()->json([
            "success"=>true,
            "payToken"=>$payToken,
            "message"=>$dataResponse->message
        ], $response->status());

    }


    /**
     * @OA\Post(
     * path="/api/v1/prod/om/payment",
     * summary="Request to make a OM payment",
     * description="This operation is used to request a payment from a consumer (Payer). The payer will be asked to authorize the payment. The transaction will be executed once the payer has authorized the payment. The requesttopay will be in status PENDING until the transaction is authorized or declined by the payer or it is timed out by the system. Status of the transaction can be validated by using the GET api/v1/prod/om/payment/<resourceId>",
     * security={{"bearerAuth":{}}},
     * tags={"Merchant payment"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Request to make a OM payment",
     *    @OA\JsonContent(
     *       required={"agentNumber","marchandTransactionId","phone","amount"},
     *       @OA\Property(property="agentNumber", type="string", example="659657424"),
     *       @OA\Property(property="marchandTransactionId", type="string", example="12354"),
     *       @OA\Property(
     *           type="object",
     *           property="data",
     *           @OA\Property(property="phone", type="number", example="659657424"),
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
            if($user->count()==0 || $user->first()->status ==0){
                return response()->json([
                    'success'=>false,
                    'statusCode'=>'ERR-NOT-PERMISSION',
                    'message'=>"The agent used does not have the necessary permissions with your profil",
                ],403);
            }
        }
        //Verifie le statut de l'id transaction cote marchand
        $checkTransactionExternalId = Transaction::where('marchand_transaction_id',$request->marchandTransactionId)->get();

        if($checkTransactionExternalId->count()>0){
            $checkDistributeur = User::where('id',$checkTransactionExternalId->first()->source)->get();
            if($checkDistributeur->count()>0){
                Log::info([
                    "user1"=>$user->first()->distributeur_id,
                    "user2"=>$checkDistributeur->first()->distributeur_id
                ]);
                if($user->first()->distributeur_id==$checkDistributeur->first()->distributeur_id){
                    return response()->json([
                        'success'=>false,
                        'statusCode'=>"ERR-MERCHAND-TRANSACTION-ID-DUPLICATE",
                        'message' => "The transaction ID used by the merchant already exists",
                        'data'=>[
                            'status' => $checkTransactionExternalId->first()->description,
                            'transactionId'=>$checkTransactionExternalId->first()->reference,
                            'dateTransaction'=>$checkTransactionExternalId->first()->date_transaction,
                            'amount'=>$checkTransactionExternalId->first()->credit,
                            'fees'=>$checkTransactionExternalId->first()->fees_collecte,
                            'agent'=>$checkDistributeur->first()->telephone,
                            'customer'=>$checkTransactionExternalId->first()->customer_phone,
                            'marchandTransactionID'=>$checkTransactionExternalId->first()->marchand_transaction_id,
                        ]
                    ], 208);
                }
            }
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

        $init_transaction = $apiCheck->init_Payment($amount, $customer, $service,"",$user->first()->id);
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
        $dataPayTokenResponse = $this->OM_getPayToken();
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
        $url = "https://omdeveloper-gateway.orange.cm/omapi/1.0.2/mp/pay";
        $data = [
            "notifUrl"=> "https://kiaboogroup.com/api/callback/om/pm",
            "channelUserMsisdn"=> $this->channel,
            "amount"=> "$amount",
            "subscriberMsisdn"=> "$customer",
            "pin"=> $this->pin,
            "orderId"=> $request->marchandTransactionId,
            "description"=> "Transaction initie by ".$user->first()->telephone. " de ".$partenaire,
            "payToken"=> $payToken
        ];

        $response = Http::withOptions(['verify' => false, $data])
            ->withHeaders([
                "X-AUTH-TOKEN"=>$this->auth_x_token,
                "Content-Type"=>"application/json",
                "WSO2-Authorization"=>"Bearer ".$this->token,
            ]
        )
            ->Post($url);

        Log::info([
            "Service"=>ServiceEnum::PAYMENT_OM->name,
            "url"=>$url,
            "requete"=>$data,
            "response"=>$response->body()
        ]);

        if($response->status()==200){
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
                    'paytoken'=>$payToken,
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


}
