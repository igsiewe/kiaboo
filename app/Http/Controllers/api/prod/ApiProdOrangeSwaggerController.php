<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\v1\fonctions\Orange_Controller;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isEmpty;

class ApiProdOrangeSwaggerController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/v1/prod/om/money/payment",
     * summary="Request to make a OM payment",
     * description="This operation is used to request a payment from a consumer (Payer). The payer will be asked to authorize the payment. The transaction will be executed once the payer has authorized the payment. The requesttopay will be in status PENDING until the transaction is authorized or declined by the payer or it is timed out by the system. Status of the transaction can be validated by using the GET api/v1/prod/om/payment/<resourceId>",
     * security={{"bearerAuth":{}}},
     * tags={"OM - Payment"},
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *        required={"customerPhone","amount", "marchandTransactionId"},
     *        @OA\Property(property="customerPhone", type="string", example="670000000"),
     *        @OA\Property(property="amount", type="string", example="2500"),
     *        @OA\Property(property="marchandTransactionId", type="string", example="TR-2025-0001"),
     *     )
     * ),
     * @OA\Response(
     *      response=200,
     *      description="Transaction initiated successfuly",
     *      @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example="true"),
     *         @OA\Property(property="paytoken", type="string", example="PM2015.4525.4121"),
     *         @OA\Property(property="message", type="string", example="Transaction initiated successfuly"),
     *      )
     *   ),
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

        $validator = Validator::make($request->all(), [
            'customerPhone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:200|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
        try{
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
            $init_transaction = $apiCheck->init_Payment($request->amount, $request->customerPhone, $service,"", Auth::user()->id,2, $device,$latitude,$longitude,$place);

            $dataTransactionInit = json_decode($init_transaction->getContent());

            if($init_transaction->getStatusCode() !=200){
                return response()->json([
                    'success'=>false,
                    'message'=>$dataTransactionInit->message,
                ],$init_transaction->getStatusCode());
            }
            $idTransaction = $dataTransactionInit->transId; //Id de la transaction initiée
            $reference = $dataTransactionInit->reference; //Référence de la transaction initiée
            //On génère le token de la transation
            $OMFunction = new Orange_Controller();
            $responseToken = $OMFunction->OM_GetTokenAccess();
            if($responseToken->getStatusCode() !=200){
                return response()->json([
                    "success"=>false,
                    "message"=>"Exception ".$responseToken->getStatusCode()." Une exception a été déclenchée au moment de la génération du token"
                ], $responseToken->getStatusCode());
            }
            $dataAcessToken = json_decode($responseToken->getContent());
            $AccessToken = $dataAcessToken->access_token;

            $customerPhone = "237".$request->customerPhone;

            //On initie le paiement (Obtention du PayToken)
            $responseInitPaiement = $OMFunction->OM_Paiement_init($AccessToken);
            if($responseInitPaiement->getStatusCode() !=200){
                return response()->json([
                    "success"=>false,
                    "message"=>"Exception ".$responseInitPaiement->getStatusCode()." Une exception a été déclenchée au moment de l'initialisation de la transaction"
                ], $responseInitPaiement->getStatusCode());
            }
            $dataInitPaiement= json_decode($responseInitPaiement->getContent());
            //    $reference = $dataInitDepot->transId;
            $payToken =$dataInitPaiement->data->payToken;

            //    $description = $dataInitDepot->data->description;
            $updateTransactionTableWithPayToken = Transaction::where("id", $idTransaction)->update([
                "payToken"=>$payToken,
            ]);

            $responseTraitePaiementOM = $OMFunction->OM_Payment_execute($AccessToken, $payToken, $request->customerPhone, $request->amount, $idTransaction);

            if($responseTraitePaiementOM->getStatusCode() !=200){
                $dataPaiement=json_decode($responseTraitePaiementOM->getContent());
                $data =json_decode($dataPaiement->message);
                return response()->json([
                    "result"=>false,
                    "message"=>"Exception ".$responseTraitePaiementOM->getStatusCode()."\n".$data->message
                ], $responseTraitePaiementOM->getStatusCode());
            }

            $dataPaiement= json_decode($responseTraitePaiementOM->getContent());
            //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)

            //On change le statut de la transaction dans la base de donnée
            // $montantAPercevoir = doubleval($request->amount) - doubleval($fees);
            $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                'reference_partenaire'=>$dataPaiement->data->txnid,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$request->amount, //Lorsque la transaction passera en succès on va déduire de ce montant les frais du paiement
                'fees'=>$fees,
                'status'=>2, // Pending
                'paytoken'=>$payToken,
                'description'=>$dataPaiement->data->status,
                'message'=>"Transaction initiée par l'agent N°".Auth::user()->telephone." - ".$dataPaiement->message." | ".$dataPaiement->data->status." | ".$dataPaiement->data->inittxnmessage,
                'api_response'=>$responseTraitePaiementOM->getContent(),
                'application'=>1
            ]);

            return response()->json(
                [
                    'status'=>true,
                    'message'=>$dataPaiement->message." ".$dataPaiement->data->status,
                    'paytoken'=>$payToken,
                ],200
            );
        }catch(\Exception $e){
            Log::error($e->getCode()." ".$e->getMessage(),$e->getTrace());
            return response()->json(
                [
                    'success'=>false,
                    'message'=>"Une erreur interne s'est produite. Veuillez vérifier votre connexion internet ou informer votre support."

                ],500
            );
        }
    }

    /**
     * @OA\Get (
     * path="/api/v1/prod/om/money/payment/push/{payToken}",
     * summary="Perform the OM Payment confirmation transaction",
     * description="Open a prompt to the user to perform the OM Payment confirmation transaction",
     * tags={"OM - Payment"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="payToken",
     *     description="reference of transaction",
     *     required=true,
     *     in="path",
     *     @OA\Schema(
     *        type="string"
     *     )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="successful operation",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="true"),
     *       @OA\Property(
     *             type="object",
     *             property="data",
     *             @OA\Property(property="createtime", type="string", example="0"),
     *             @OA\Property(property="amount", type="string", example="0"),
     *             @OA\Property(property="channelUserMsisdn", type="string", example="string"),
     *             @OA\Property(property="inittxnmessage", type="string", example="string"),
     *             @OA\Property(property="confirmtxnmessage", type="string", example="string"),
     *             @OA\Property(property="confirmtxnstatus", type="string", example="string"),
     *             @OA\Property(property="subscriberMsisdn", type="string", example="string"),
     *             @OA\Property(property="txnmode", type="string", example="string"),
     *             @OA\Property(property="inittxnstatus", type="string", example="string"),
     *             @OA\Property(property="payToken", type="string", example="string"),
     *             @OA\Property(property="txnid", type="string", example="string"),
     *             @OA\Property(property="status", type="string", example="string"),
     *       )
     *    )
     * ),
     *  @OA\Response(
     *       response=400,
     *       description="Invalid PayToken supplied",
     *       @OA\JsonContent(
     *          @OA\Property(property="success", type="boolean", example="false"),
     *          @OA\Property(property="message", type="string", example="Invalid payToken supplied"),
     *       )
     *  ),
     * @OA\Response(
     *    response=404,
     *    description="No mp found for input pay token",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="message", type="string", example="No mp found for input pay token"),
     *    )
     *  ),
     * @OA\Response(
     *    response=500,
     *    description="an error occurred",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="message", type="string", example="an error occurred"),
     *    )
     *  ),
     * )
     * )
     */

    public function OM_PaymentPush($payToken){
        // On cherche la transaction dans la table transaction
dd(Auth::user());
        $transaction = Transaction::where("paytoken", $payToken)->where("service_id",ServiceEnum::PAYMENT_OM->value)->where("created_by",Auth::user()->created_by)->get();
        dd($transaction, $payToken,ServiceEnum::PAYMENT_OM->value, Auth::user()->created_by);
        if( isEmpty($transaction)){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>"ERR-NO-MP-PAYTOKEN-FOUND",
                    'message'=>"This id transaction does not exist"
                ],404
            );
        }

        //On génère le token de la transation
        $OMFunction = new Orange_Controller();
        $responseToken = $OMFunction->OM_GetTokenAccess();
        if($responseToken->getStatusCode() !=200){
            return response()->json([
                "success"=>false,
                "message"=>"Exception ".$responseToken->getStatusCode()." Une exception a été déclenchée au moment de la génération du token"
            ], $responseToken->getStatusCode());
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $accessToken = $dataAcessToken->access_token;
        $response = $OMFunction->OM_PaymentPush($accessToken, $payToken);
        $data = json_decode($response);

        $data = json_decode($response->body());

        if($response->status()==200){
            return response()->json(
                [
                    'success'=>true,
                    'data'=>$data->data,
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

    /**
     * @OA\Get (
     * path="/api/v1/prod/om/money/payment/status/{payToken}",
     * summary="Check OM transaction status",
     * description="This operation is used to get the status of a request to momo pay. payToken that was passed in the post is used as reference to the request",
     * tags={"OM - Payment"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="payToken",
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
     *             @OA\Property(property="customer", type="number", example="customer phone number"),
     *             @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
     *       )
     *    )
     * ),
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

    public function OM_PaymentStatus($payToken){
        // On cherche la transaction dans la table transaction

        $transaction = Transaction::where("paytoken", $payToken)->where("service_id", ServiceEnum::PAYMENT_OM->value)->where("created_by", Auth::user()->created_by)->get();
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
        $OMFunction = new Orange_Controller();
        $responseToken = $OMFunction->OM_GetTokenAccess();
        if($responseToken->getStatusCode() !=200){
            return response()->json([
                "success"=>false,
                "message"=>"Exception ".$responseToken->getStatusCode()." Une exception a été déclenchée au moment de la génération du token"
            ], $responseToken->getStatusCode());
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        $accessToken = $dataAcessToken->access_token;
        $payToken = $transaction->first()->paytoken;

        $response = $OMFunction->OM_Payment_Status($accessToken, $payToken);
        $data = json_decode($response);
        if($response->status()==200){
            return response()->json(
                [
                    'success'=>true,
                    'statusCode'=>$data->data->status,
                    'message'=>$data->data->status=="PENDING"?$data->data->inittxnmessage:$data->message,
                    'data'=>[
                        'currency'=>'XAF',
                        'payToken'=>$payToken,
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

}
