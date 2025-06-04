<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\v1\fonctions\MoMo_Controller;
use App\Http\Controllers\api\v1\fonctions\Orange_Controller;
use App\Http\Controllers\ApiLog;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
     *    description="Request to make a OM payment",
     *    @OA\JsonContent(
     *       required={"marchandTransactionId","customerPhone","amount"},
     *       @OA\Property(property="marchandTransactionId", type="string", example="12354"),
     *       @OA\Property(
     *           type="object",
     *           property="data",
     *           @OA\Property(property="customerPhone", type="string", example="659657424"),
     *           @OA\Property(property="amount", type="string", example="200"),
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
     *       @OA\Property(property="paytoken", type="string", example="Payment token"),
     *       @OA\Property(
     *             type="object",
     *             property="data",
     *             @OA\Property(property="status", type="string", example="Transaction status"),
     *             @OA\Property(property="transactionId", type="string", example="transacton id database"),
     *             @OA\Property(property="dateTransaction", type="date", example="Date transaction"),
     *             @OA\Property(property="amount", type="number", example="amount of transaction"),
     *             @OA\Property(property="fees", type="number", example="transaction fees"),
     *             @OA\Property(property="agent", type="string", example="agent who initiate transaction"),
     *             @OA\Property(property="customer", type="number", example="customer phone number"),
     *             @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
     *      )
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
                    'status'=>'error',
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
                    "result"=>false,
                    "message"=>"Exception ".$responseToken->getStatusCode()."\nUne exception a été déclenchée au moment de la génération du token"
                ], $responseToken->getStatusCode());
            }
            $dataAcessToken = json_decode($responseToken->getContent());
            $AccessToken = $dataAcessToken->access_token;

            $customerPhone = "237".$request->customerPhone;

            //On initie le paiement (Obtention du PayToken)
            $responseInitPaiement = $OMFunction->OM_Paiement_init($AccessToken);
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
                    'status'=>200,
                    'message'=>$dataPaiement->message."\n".$dataPaiement->data->status,
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
}
