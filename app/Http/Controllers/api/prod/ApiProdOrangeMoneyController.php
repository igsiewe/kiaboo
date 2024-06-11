<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiProdOrangeMoneyController extends Controller
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

    /**
     * @OA\Post(
     * path="/api/v1/prod/om/payment",
     * summary="Request to make a Orange Money payment",
     * description="This operation is used to request a payment from a consumer (Payer). The payer will be asked to authorize the payment. The transaction will be executed once the payer has authorized the payment. The requesttopay will be in status PENDING until the transaction is authorized or declined by the payer or it is timed out by the system. Status of the transaction can be validated by using the GET api/v1/prod/momo/payment/<resourceId>",
     * security={{"bearerAuth":{}}},
     * tags={"OM - Payment"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Request to make a payment OM",
     *    @OA\JsonContent(
     *       required={"agentNumber","marchandTransactionId","phone","amount"},
     *       @OA\Property(property="agentNumber", type="string", example="679962015"),
     *       @OA\Property(property="marchandTransactionId", type="string", example="12354"),
     *       @OA\Property(
     *           type="object",
     *           property="data",
     *           @OA\Property(property="phone", type="number", example="679962015"),
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

        $getToken = $this->OM_GetTokenAccess();
        $dataAcessToken = json_decode($getToken->getContent());

        if ($getToken->status() != 200) {
            return response()->json([
                'code' => $getToken->status(),
                'error' => '1.error = '.$getToken->getContent(),
            ]);
        }
        $accessToken = $dataAcessToken->access_token;


    }
}
