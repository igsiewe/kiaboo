<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\v1\fonctions\MoMo_Controller;
use App\Http\Controllers\ApiLog;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isEmpty;


class ApiProdMoMoMonneySwaggerController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/prod/mtn/customer/name/{customerPhone}",
     *     summary="Get customer information",
     *     tags={"MTN - Customer information"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="customerPhone",
     *         in="path",
     *         required=true,
     *         description="Customer phone number",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Customer information",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="firstname", type="string", example="DUPOND"),
     *              @OA\Property(property="lastname", type="string", example="Hanry")
     *          )
     *      ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le numéro de téléphone n'est pas valide")
     *         )
     *     )
     * )
     */
    public function MoMoGetName($customerPhone)
    {

        $MoMoFunction = new MoMo_Controller();

        $responseToken = $MoMoFunction->MOMO_Disbursement_GetTokenAccess();
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

        $response = $MoMoFunction->MOMO_Customer($accessToken, $customerPhone);
        if($response->status()!=200){
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>"Le numéro de téléphone n'est pas valide",
                ],$response->status()
            );
        }
        $data=json_decode($response->getContent());
        return response()->json($data, 200);


    }

    /**
     * @OA\Post(
     *     path="/api/v1/prod/mtn/payment",
     *     summary="Process a MoMo payment",
     *     tags={"MTN - Payment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customerPhone","amount", "externalId"},
     *             @OA\Property(property="customerPhone", type="string", example="670000000"),
     *             @OA\Property(property="amount", type="string", example="2500"),
     *             @OA\Property(property="externalId", type="string", example="TR-2025-0001"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment request sent successfully"),
     *             @OA\Property(property="transactionId", type="string", example="tx-123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Missing required fields")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to initiate payment")
     *         )
     *     )
     * )
     */

    public function MoMoPayment(Request $request){

        $validator = Validator::make($request->all(), [
            'customerPhone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:200|max:500000',
            'externalId' => 'required|string|max:25',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::PAYMENT_MOMO->value;

        $user = User::where("id",Auth::user()->id)->where('type_user_id', UserRolesEnum::AGENT->value)->get();

        $amount=$request->amount;
        $customer=$request->customerPhone;


        // Vérifie si l'utilisateur est autorisé à faire cette opération

        // On vérifie si les frais sont paramétrées
        $functionFees = new ApiCommissionController();
        $lesfees =$functionFees->getFeesByService($service,$request->amount);
        if($lesfees->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "Impossible de calculer la commission",
            ], 403);
        }


        if(!$apiCheck->checkUserValidity()){
            return response()->json([
                'status'=>false,
                'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
            ],403);
        }

        $fee=json_decode($lesfees->getContent());
        $fees = doubleval($fee->fees_globale);

        //Initie la transaction
        $device = $request->deviceId;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;

        $init_transaction = $apiCheck->init_Payment($amount, $customer, $service,"", Auth::user()->id,2, $device,$latitude,$longitude,$place);

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
        //On génère le token de la transation
        $MoMoFunction = new MoMo_Controller();

        $responseToken = $MoMoFunction->MOMO_Payment_GetTokenAccess();

        if($responseToken->status()!=200){
            return response()->json(
                [
                    'success'=>false,
                    'statusCode'=>$responseToken->status(),
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $accessToken = $dataAcessToken->access_token;

        //Référence de la transaction
        $referenceID = $MoMoFunction->gen_uuid();
        //On gardee l'UID de la transaction initiee
        $saveUID = Transaction::where('id',$idTransaction)->update([
            "paytoken"=>$referenceID
        ]);
        $customerPhone = "237".$customer;
        $response = $MoMoFunction->MOMO_Payment($accessToken,$referenceID, $idTransaction, $amount, $customerPhone);

        if($response->status()==202){
            //Le client a été notifié. Donc on reste en attente de sa confirmation (Saisie de son code secret)
            //On change le statut de la transaction dans la base de donnée
            $Transaction = Transaction::where('id',$idTransaction)->where('service_id',$service)->update([
                'reference_partenaire'=>$referenceID,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$amount,
                'status'=>2, // Pending
                'paytoken'=>$referenceID,
                'date_end_trans'=>Carbon::now(),
                'description'=>'PENDING',
                'message'=>"Transaction initiée par l'agent N°".$user->first()->id." ".$user->first()->telephone,
                'fees'=>$fees,
                'marchand_transaction_id'=>$request->marchandTransactionId,
            ]);

            //Le solde du compte de l'agent ne sera mis à jour qu'après confirmation de l'agent : Opération traitée dans le callback
            //On recupère toutes les transactions en attente
            return response()->json(
                [
                    'success'=>true,
                    'statusCode'=>"PAYMENT-INITIATE-SUCCESSFULLY",
                    'message'=>"Transaction initiée avec succès. Le client doit confirmer le paiement avec son code secret",
                    'paytoken'=>$referenceID,
                    'transactionId'=>$reference,//$idTransaction,
                ],202
            );

        }else{

            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->getContent(),
                ],$response->status()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/prod/mtn/payment/status/{paytoken}",
     *     summary="Get transaction status",
     *     tags={"MTN - Payment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="paytoken",
     *         in="path",
     *         required=true,
     *         description="PayToken",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="financialTransactionId", type="string", example="123456789"),
     *                 @OA\Property(property="externalId", type="string", example="ext-abc-001"),
     *                 @OA\Property(property="amount", type="string", example="2500"),
     *                 @OA\Property(property="currency", type="string", example="XAF"),
     *                 @OA\Property(property="payer", type="object",
     *                     @OA\Property(property="partyIdType", type="string", example="MSISDN"),
     *                     @OA\Property(property="partyId", type="string", example="237690000000")
     *                 ),
     *                 @OA\Property(property="payeeNote", type="string", example="Payment for invoice 123"),
     *                 @OA\Property(property="status", type="string", example="SUCCESSFUL")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=202,
     *          description="Transaction pending because wainting for customer validation",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="statusCode", type="string", example="PENDING"),
     *              @OA\Property(property="message", type="string", example="Wainting for customer validation"),
     *                  @OA\Property(property="data", type="object",
     *                  @OA\Property(property="financialTransactionId", type="string", example="123456789"),
     *                  @OA\Property(property="externalId", type="string", example="ext-abc-001"),
     *                  @OA\Property(property="amount", type="string", example="2500"),
     *                  @OA\Property(property="currency", type="string", example="XAF"),
     *                  @OA\Property(property="payer", type="object",
     *                      @OA\Property(property="partyIdType", type="string", example="MSISDN"),
     *                      @OA\Property(property="partyId", type="string", example="237690000000")
     *                  ),
     *                  @OA\Property(property="payeeNote", type="string", example="Payment for invoice 123"),
     *                  @OA\Property(property="status", type="string", example="PENDING")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *           response=402,
     *           description="Transaction failed",
     *           @OA\JsonContent(
     *               @OA\Property(property="success", type="boolean", example=false),
     *               @OA\Property(property="statusCode", type="string", example="FAILED"),
     *               @OA\Property(property="message", type="string", example="Transaction failed because, it was not approved"),
     *                  @OA\Property(property="data", type="object",
     *                  @OA\Property(property="financialTransactionId", type="string", example="123456789"),
     *                  @OA\Property(property="externalId", type="string", example="ext-abc-001"),
     *                  @OA\Property(property="amount", type="string", example="2500"),
     *                  @OA\Property(property="currency", type="string", example="XAF"),
     *                  @OA\Property(property="payer", type="object",
     *                      @OA\Property(property="partyIdType", type="string", example="MSISDN"),
     *                      @OA\Property(property="partyId", type="string", example="237690000000")
     *                  ),
     *                  @OA\Property(property="payeeNote", type="string", example="Payment for invoice 123"),
     *                  @OA\Property(property="status", type="string", example="FAILED")
     *              )
     *           )
     *       ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
     *             @OA\Property(property="message", type="string", example="An internal server error occurred")
     *         )
     *     )
     * )
     */

    public function MoMoPaymentStatus($payToken)
    {
        // On cherche la transaction dans la table transaction

        $Transaction = Transaction::where("paytoken", $payToken)->where('service_id', ServiceEnum::PAYMENT_MOMO->value)->where("created_by", Auth::user()->id);

        if ($Transaction->count() == 0) {
            return response()->json(
                [
                    'success' => false,
                    'statusCode' => "ERR-TRANSACTION-NOT-FOUND",
                    'message' => "PayToken not found",
                    'paytoken' => $payToken,
                ], 404
            );
        }

        //On génère le token de la transation
        $MoMoFunction = new MoMo_Controller();
        $responseToken = $MoMoFunction->MOMO_Payment_GetTokenAccess();

        if ($responseToken->status() != 200) {
            return response()->json(
                [
                    'success' => false,
                    'statusCode' => $responseToken->status(),
                    'message' => $responseToken["message"],
                ], $responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $accessToken = $dataAcessToken->access_token;
        $payToken = $Transaction->first()->paytoken;

        $response = $MoMoFunction->MOMO_PaymentStatus($accessToken, $payToken);

        $data = json_decode($response->getContent());
        $status = $data->data->status;

        if ($Transaction->first()->status == 1) {
            return response()->json(
                [
                    'success' => true,
                    'statusCode' => "SUCCESSFUL",
                    'message' => "Transaction successful",
                    'data' => $data->data,
                ], 200
            );
        }
        if ($Transaction->first()->status == 2) {
            if ($response->status() == 200) {
                $reference = $Transaction->first()->reference;
                $montant = $Transaction->first()->credit;
                $user = User::where('id', $Transaction->first()->created_by);
                try {
                    DB::beginTransaction();
                    if ($status == "SUCCESSFUL") {
                        $montantACrediter = doubleval($montant) - doubleval($Transaction->first()->fees);
                        $balanceBeforeAgent = $user->get()->first()->balance_after;
                        $balanceAfterAgent = floatval($balanceBeforeAgent) + floatval($montantACrediter); //On a déduit les frais de la transaction.
                        $reference_partenaire = $data->data->financialTransactionId;
                        $agent = $user->first()->id;
                        $total_fees = $user->first()->total_fees + $Transaction->first()->fees;

                        $update = $Transaction->update([
                            'status' => 1,
                            'reference_partenaire' => $reference_partenaire,
                            'description' => $data->data->status,
                            'message' => $data->data->status,
                            'date_end_trans' => Carbon::now(),
                            'balance_after' => $balanceAfterAgent,
                            'balance_before' => $balanceBeforeAgent,
                            'terminaison' => 'MANUEL',
                        ]);
                        //On met à jour le solde de l'agent
                        $CreditAgent = DB::table("users")->where("id", $agent)->update([
                            'balance_after' => $balanceAfterAgent,
                            'balance_before' => $balanceBeforeAgent,
                            'last_amount' => $montant,
                            'total_fees' => $total_fees,
                            'date_last_transaction' => Carbon::now(),
                            'user_last_transaction_id' => $agent,
                            'last_service_id' => ServiceEnum::PAYMENT_MOMO->value,
                            'reference_last_transaction' => $reference,
                            'remember_token' => $reference,
                        ]);
                        DB::commit();

                        return response()->json(
                            [
                                'success' => true,
                                'statusCode' => $status,
                                'message' => 'Transaction successful',
                                'data' => $data->data,
                            ], 200);

                    }
                    if ($status == "FAILED") {
                        $update = $Transaction->update([
                            'status' => 3,
                            'reference_partenaire' => $data->financialTransactionId,
                            'description' => $status,
                            'message' => $data->data->reason,
                            'date_end_trans' => Carbon::now(),
                            'terminaison' => 'MANUEL',
                        ]);
                        DB::commit();
                        return response()->json(
                            [
                                'success' => false,
                                'statusCode' => 'FAILED',
                                'message' => $data->data->status . " - Le client n'a pas validé la transaction dans les délais et l'opérateur l'a annulé",
                                'data' => $data->data,
                            ], 402
                        );
                    }
                    if ($status == "PENDING") {
                        // $reason = json_decode($data->reason);
                        $update = $Transaction->update([
                            'status' => 2,
                            'reference_partenaire' => $data->data->financialTransactionId,
                            'description' => $status,
                        ]);
                        DB::commit();
                        return response()->json(
                            [
                                'success' => true,
                                'statusCode' => 'PENDING',
                                'message' => "La transaction est en status en attente. Le client doit confirmer la transaction en saisissant son code secret.",
                                'data' => $data->data,
                            ], 202
                        );
                    }
                    DB::rollback();
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Transaction en cours de traitement chez l'opérateur",
                            'data' => $data->data,
                        ], 403
                    );
                } catch (\Exception $e) {
                    DB::rollback();
                    return response()->json(
                        [
                            'success' => false,
                            'statusCode' => $e->getCode(),
                            'message' => $e->getMessage(),
                        ], 404
                    );
                }
            } else {
                return response()->json(
                    [
                        'success' => false,
                        'statusCode' => $response->status(),
                        'message' => $response->body()

                    ], 404
                );
            }
        }
        if ($Transaction->first()->status == 3) {
            return response()->json(
                [
                    'success' => false,
                    'statusCode' => "FAILED",
                    'message' => "Transaction failed",
                    'data' => $data->data,
                ], 402
            );
        }

    }

    /**
     * @OA\Post(
     *     path="/api/v1/prod/mtn/cashin",
     *     summary="Process a MoMo cashin",
     *     tags={"MTN - CashIn"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customerPhone","amount"},
     *             @OA\Property(property="customerPhone", type="string", example="670000000"),
     *             @OA\Property(property="amount", type="string", example="2500"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CashIn end successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="statusCode", type="string", example="SUCCESSFUL"),
     *             @OA\Property(property="reference", type="string", example="123456789"),
     *             @OA\Property(property="message", type="string", example="Payment request sent successfully"),
     *
     *         )
     *     ),
     *    @OA\Response(
     *          response=201,
     *          description="CashIn initiated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="statusCode", type="string", example="PENDING"),
     *              @OA\Property(property="paytoken", type="string", example="a84d7-8544-98854zd"),
     *              @OA\Property(property="message", type="string", example="Payment request sent successfully but it is still pending status"),
     *          )
     *      ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Missing required fields")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to initiate payment")
     *         )
     *     )
     * )
     */

    public function MoMoCashIn(Request $request){
        $validator = Validator::make($request->all(), [
            'customerPhone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:50|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $customerNumber = $request->customerPhone;
        $montant = $request->amount;

        $apiCheck = new ApiCheckController();

        $service = ServiceEnum::DEPOT_MOMO->value;
        // Vérifie si le service est actif
        if($apiCheck->checkStatusService($service)==false){
            return response()->json([
                'success'=>false,
                'message'=>"Ce service n'est pas actif",
            ],403);
        }
        // Vérifie si l'utilisateur est autorisé à faire cette opération
        if(!$apiCheck->checkUserValidity()){
            return response()->json([
                'success'=>false,
                'message'=>'Votre compte est désactivé. Veuillez contacter votre distributeur',
            ],401);
        }

        // Vérifie si le solde de l'utilisateur lui permet d'effectuer cette opération
        if(!$apiCheck->checkUserBalance($montant)){
            return response()->json([
                'success'=>false,
                'message'=>'Votre solde est insuffisant pour effectuer cette opération',
            ],403);
        }

        //Vérifie si l'utilisateur n'a pas initié une operation similaire dans les 5 dernières minutes

        if($apiCheck->checkFiveLastTransaction($customerNumber, $montant, $service)){
            return response()->json([
                'success'=>false,
                'message'=>'Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ],403);
        }

        // On vérifie si les commissions sont paramétrées
        $functionCommission = new ApiCommissionController();
        $lacommission =$functionCommission->getCommissionByService($service,$montant);
        if($lacommission->getStatusCode()!=200){
            return response()->json([
                'success' => false,
                'message' => "Impossible de calculer la commission",
            ], 403);
        }

        // On recupere les charges de services
        //  $idTypeService = Service::where('id',$service)->first()->type_service_id;
        //  $lacharge = $functionCommission->getChargeService($idTypeService,$montant);

        //Initie la transaction
        $device = $request->deviceId;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $place = $request->place;
        $init_transaction = $apiCheck->init_Depot($montant, $customerNumber, $service, "",$device, $latitude, $longitude, $place,1, Auth::user()->id,"");
        $dataInit = json_decode($init_transaction->getContent());

        if($init_transaction->getStatusCode() !=200){
            return response()->json([
                'success'=>false,
                'message'=>$dataInit->message,
            ],$init_transaction->getStatusCode());
        }

        $idTransaction = $dataInit->transId; //Id de la transaction initiée
        $reference = $dataInit->reference; //Référence de la transaction initiée

        //On génère le token de la transation
        $MoMoFunction = new MoMo_Controller();
        $responseToken = $MoMoFunction->MOMO_Disbursement_GetTokenAccess();

        if($responseToken->status()!=200){
            return response()->json(
                [
                    'success'=>false,
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $accessToken = $dataAcessToken->access_token;
        $referenceID = $MoMoFunction->gen_uuid();
        //On gardee l'UID de la transaction initiee
        $saveUID = Transaction::where('id',$idTransaction)->update([
            'reference_partenaire'=>$referenceID,
            "paytoken"=>$referenceID
        ]);

        $customerPhone = "237".$customerNumber;
        $response = $MoMoFunction->MOMO_CashIn($accessToken,$referenceID, $idTransaction, $montant, $customerPhone);

        $saveResponse = Transaction::where('id',$idTransaction)->update([
            'api_response'=>$response->status(),
        ]);

        if($response->status()==202){

            $updateTransaction=Transaction::where("id",$idTransaction)->update([
                'status'=>2, // Le dépôt n'a pas abouti, on passe en statut pending
                //'reference_partenaire'=>$data->financialTransactionId,
                'description'=>"PENDING",
                'message'=>"La transaction s'est exécutée correctement et doit être confirmée par le callback",
                'api_response'=>$response->status(),
            ]);

            $checkStatus = $MoMoFunction->MOMO_CashInStatus($accessToken, $referenceID);
            $datacheckStatus = json_decode($checkStatus->getContent());

            if($checkStatus->getStatusCode() !=200) {
                //La transaction est attente
                $updateTransaction=Transaction::where("id",$idTransaction)->where("status",2)->update([
                    'description'=>$datacheckStatus->data->status,
                    'message'=>$datacheckStatus->message." - En cours de traitement",
                ]);
                if($checkStatus->getStatusCode() ==201) {
                    return response()->json([
                        'success'=>false,
                        'statusCode'=>"PENDING",
                        'paytoken'=>$referenceID,
                        'message'=>$datacheckStatus->message,
                    ],$checkStatus->getStatusCode());
                }
                $transaction = Transaction::where("id",$idTransaction)->first();
                if($transaction->status==1){
                    return response()->json([
                        'success' => true,
                        'statusCode'=>"SUCCESSFULL",
                        'reference' => $reference,// $resultat->data->data->txnid,
                        'message' =>"Le dépôt a été effectué avec succès", // $resultat->message,
                    ], 200);
                }else{
                    return response()->json([
                        'success'=>false,
                        'message'=>$datacheckStatus->message,
                    ],$checkStatus->getStatusCode());
                }
            }else{
                $transaction = Transaction::where("id",$idTransaction)->first();
                if($transaction->status==1){
                    return response()->json([
                        'success' => true,
                        'statusCode'=>"SUCCESSFULL",
                        'reference' => $reference,// $resultat->data->data->txnid,
                        'message' =>"Le dépôt a été effectué avec succès", // $resultat->message,
                    ], 200);
                }else{
                    return response()->json([
                        'success'=>false,
                        'message'=>$datacheckStatus->message,
                    ],$checkStatus->getStatusCode());
                }

            }

        }else{
            return response()->json(
                [
                    'status'=>$response->status(),
                    'error'=>$response->getContent(),
                    'message'=>"Une erreur innatendue s'est produite",
                ],$response->status()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/prod/mtn/cashin/status/{payToken}",
     *     summary="Get cashin transaction status",
     *     tags={"MTN - CashIn"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="payToken",
     *         in="path",
     *         required=true,
     *         description="PayToken",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="financialTransactionId", type="string", example="123456789"),
     *                 @OA\Property(property="externalId", type="string", example="ext-abc-001"),
     *                 @OA\Property(property="amount", type="string", example="2500"),
     *                 @OA\Property(property="currency", type="string", example="XAF"),
     *                 @OA\Property(property="payer", type="object",
     *                     @OA\Property(property="partyIdType", type="string", example="MSISDN"),
     *                     @OA\Property(property="partyId", type="string", example="237690000000")
     *                 ),
     *                 @OA\Property(property="payeeNote", type="string", example="Payment for invoice 123"),
     *                 @OA\Property(property="status", type="string", example="SUCCESSFUL")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=201,
     *          description="Transaction pending or created",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="financialTransactionId", type="string", example="123456789"),
     *                  @OA\Property(property="externalId", type="string", example="ext-abc-001"),
     *                  @OA\Property(property="amount", type="string", example="2500"),
     *                  @OA\Property(property="currency", type="string", example="XAF"),
     *                  @OA\Property(property="payer", type="object",
     *                      @OA\Property(property="partyIdType", type="string", example="MSISDN"),
     *                      @OA\Property(property="partyId", type="string", example="237690000000")
     *                  ),
     *                  @OA\Property(property="payeeNote", type="string", example="Payment for invoice 123"),
     *                  @OA\Property(property="status", type="string", example="PENDING or CREATED")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *           response=406,
     *           description="Transaction failed",
     *           @OA\JsonContent(
     *               @OA\Property(property="success", type="boolean", example=false),
     *               @OA\Property(property="data", type="object",
     *                   @OA\Property(property="financialTransactionId", type="string", example="123456789"),
     *                   @OA\Property(property="externalId", type="string", example="ext-abc-001"),
     *                   @OA\Property(property="amount", type="string", example="2500"),
     *                   @OA\Property(property="currency", type="string", example="XAF"),
     *                   @OA\Property(property="payer", type="object",
     *                       @OA\Property(property="partyIdType", type="string", example="MSISDN"),
     *                       @OA\Property(property="partyId", type="string", example="237690000000")
     *                   ),
     *                   @OA\Property(property="payeeNote", type="string", example="Payment for invoice 123"),
     *                   @OA\Property(property="status", type="string", example="FAILED"),
     *                   @OA\Property(property="raison", type="string", example="REJETED BY CUSTOMER")
     *               )
     *           )
     *       ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An internal server error occurred")
     *         )
     *     )
     * )
     */
    public function MoMoCashInStatus($payToken){


        // On cherche la transaction dans la table transaction
        $Transaction = Transaction::where("created_by", Auth::user()->id)->where("paytoken","=", $payToken)->where('service_id', ServiceEnum::DEPOT_MOMO->value);
       // dd($Transaction->count(),$payToken,ServiceEnum::DEPOT_MOMO->value,Auth::user()->id);

        if ($Transaction->get()->count() == 0) {
                return response()->json(
                    [
                        'success' => false,
                        'statusCode' => "ERR-TRANSACTION-NOT-FOUND",
                        'message' => "PayToken not found",
                    ], 404
                );

        }

        //On génère le token de la transation
        $MoMoFunction = new MoMo_Controller();
        $responseToken = $MoMoFunction->MOMO_Disbursement_GetTokenAccess();

        if($responseToken->status()!=200){
            return response()->json(
                [
                    'success'=>false,
                    'message'=>$responseToken["message"],
                ],$responseToken->status()
            );
        }

        $dataAcessToken = json_decode($responseToken->getContent());
        $accessToken = $dataAcessToken->access_token;
        $response = $MoMoFunction->MOMO_CashInStatus($accessToken, $payToken);
        $data = json_decode($response->getContent());

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
                    'data'=>$data->data,
                ],$response->status()
            );
        }
    }

}
