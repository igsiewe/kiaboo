<?php

namespace App\Http\Controllers\api\v1\fonctions;

use App\Http\Controllers\ApiLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class Orange_Controller extends Controller
{
    protected $token;
    protected $auth;
    protected $auth_x_token;
    protected $channel;
    protected $pin;
    protected $url;
    protected $endpoint;
    protected $callbackUrl;

    public function __construct()
    {
        $this->endpoint="https://api-s1.orange.cm/omcoreapis/1.0.2";
        $this->callbackUrl="https://www.kiaboopay.com/api/callback/om/cico";
        $this->auth="RllkYW1SbVAwWWNSUTlNbHRvdkd2NFBjTjlNYTpiTVdoeXFSNUtfZExOZ2ZRaHUzdmh0aV9ZZEFh"; //consumer_key et consumer_secret convertis en base64
        $this->auth_x_token ="S0lBQk9PU0FSTEFQSU9NUFJPRDI1OktJQEJfT1NBUkxAUCFPbV9QUk9fZDIwMjU=";//api_username et api_password convertis en base64
        $this->channel="691566672";
        $this->pin="2025";
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
            ->Post('https://api-s1.orange.cm/token');

        if($response->status()==200){
            return response()->json($response->json());
        }
        else{
            return response()->json([
                'status'=>'error',
                'message'=>$response->body()
            ],$response->status());

        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/prod/om/money/customer/name/{customerPhone}",
     *     summary="Get customer information",
     *     tags={"OM - Customer information"},
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
    public function OM_Customer($customerNumber)
    {

        $responseToken = $this->OM_GetTokenAccess();
        if($responseToken->getStatusCode() !=200){
            return $responseToken;
        }
        $dataAcessToken = json_decode($responseToken->getContent());
        try{
            $AccessToken = $dataAcessToken->access_token;
            $token = $AccessToken;
            $endpoint = $this->endpoint.'/infos/subscriber/customer/'.$customerNumber;

            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=>$this->auth_x_token,
                        'Authorization'=>'Bearer '.$token
                    ])
                ->Post($endpoint, [
                    "pin"=> $this->pin,
                    "channelMsisdn"=> $this->channel
                ]  );

            if($response->status()==200){
                $data = json_decode($response, false);
                $firstName = $data->data->firstName;
                $lastName = $data->data->lastName;
                if($firstName==null || $firstName==""){
                    return response()->json([
                        'status' => false,
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                    ],404);
                }
                return response()->json([
                    'status' => true,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                ],200);
            }else{
                $body = json_decode($response->body());
                return response()->json([
                    'status' => false,
                    'message'=> $body->message
                ],$response->status());
            }
        }catch (\Exception $e){
            return response()->json(
                [
                    'status'=> false,
                    'messsage'=>  $e->getMessage(),

                ],$e->getCode()
            );
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

    public function OM_Paiement_init($token)
    {
        $response = Http::withOptions(['verify' => false,])
            ->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=>$this->auth_x_token,
                    'Authorization'=>'Bearer '.$token
                ])

            ->Post($this->endpoint.'/mp/init');

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

    public function OM_Payment_execute($token, $payToken, $beneficiaire, $montant, $transId)
    {

        //On execute le retrait
        $description = "Agent : ".Auth::user()->telephone;

        try{
            $response = Http::withOptions(['verify' => false,])
                ->withHeaders(
                    [
                        'Content-Type'=> 'application/json',
                        'X-AUTH-TOKEN'=>$this->auth_x_token,
                        'Authorization'=>'Bearer '.$token
                    ])

                ->Post($this->endpoint.'/mp/pay', [
                    "notifUrl"=> $this->callbackUrl,
                    "channelUserMsisdn"=> $this->channel,
                    "amount"=> $montant,
                    "subscriberMsisdn"=> $beneficiaire,
                    "pin"=> $this->pin,
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
                ],$response->status());
            }
        }catch (\Exception $e){
            return response()->json([
                'message'=>$e->getMessage()
            ],$e->getCode());
        }

    }

    public function OM_Payment_Status($token, $payToken){

        try {
            $http = $this->endpoint."/mp/paymentstatus/".$payToken;

            $response = Http::withOptions(['verify' => false,])->withHeaders(
                [
                    "X-AUTH-TOKEN"=>$this->auth_x_token,
                    "Authorization"=>"Bearer ".$token,
                    "accept"=>"application/json"
                ])->Get($http);
            $data = json_decode($response->body());
dd($data->message);
            if($response->status()==200){
                return response()->json(
                    [
                        'success'=>true,
                        'message'=>$data->message,
                        'data'=>[
                            "id"=> $data->data->id,
                            "subscriberMsisdn"=>$data->data->subscriberMsisdn,
                            "amount"=> $data->data->amount,
                            "payToken"=> $data->data->payToken,
                            "status"=> $data->data->status,
                            "txnid"=> $data->data->txnid,
                            "inittxnstatus"=> $data->data->inittxnstatus,
                            "confirmtxnmessage"=> $data->data->confirmtxnmessage,
                            "orderId"=> $data->data->orderId,
                            "description"=> $data->data->description,
                            "createtime"=> $data->data->createtime,
                        ]
                    ],$response->status());
            }
            else{
                return response()->json(
                    [
                        'success'=>false,
                        'message'=>$data->message,
                        'data'=>[
                            "id"=> $data->data->id,
                            "subscriberMsisdn"=>$data->data->subscriberMsisdn,
                            "amount"=> $data->data->amount,
                            "payToken"=> $data->data->payToken,
                            "status"=> $data->data->status,
                            "txnid"=> $data->data->txnid,
                            "inittxnstatus"=> $data->data->inittxnstatus,
                            "confirmtxnmessage"=> $data->data->confirmtxnmessage,
                            "orderId"=> $data->data->orderId,
                            "description"=> $data->data->description,
                            "createtime"=> $data->data->createtime,
                        ]
                    ],$response->status());

            }
        }catch (\Exception $e){
            return response()->json(
                [
                    'success'=> false,
                    'messsage'=>  $e->getMessage(),

                ],$e->getCode()
            );
        }

    }

    public function OM_PaymentPush($token, $payToken){
        try {
            $http = $this->endpoint."/mp/push/".$payToken;
            $response = Http::withOptions(['verify' => false,])->withHeaders(
                [
                    'Content-Type'=> 'application/json',
                    'X-AUTH-TOKEN'=>$this->auth_x_token,
                    'Authorization'=>'Bearer '.$token
                ])->Get($http);
            $data = json_decode($response->body());
            if($response->status()==200){
                return response()->json(
                    [
                        'success'=>true,
                        'message'=>$data->message,
                        'data'=>[
                            "id"=> $data->data->id,
                            "subscriberMsisdn"=>$data->data->subscriberMsisdn,
                            "amount"=> $data->data->amount,
                            "payToken"=> $data->data->payToken,
                            "status"=> $data->data->status,
                            "txnid"=> $data->data->txnid,
                            "inittxnstatus"=> $data->data->inittxnstatus,
                            "confirmtxnmessage"=> $data->data->confirmtxnmessage,
                            "orderId"=> $data->data->orderId,
                            "description"=> $data->data->description,
                            "createtime"=> $data->data->createtime,
                        ]
                    ],$response->status());
            }
            else{
                return response()->json(
                    [
                        'success'=>false,
                        'message'=>$data->message,
                        'data'=>[
                            "id"=> $data->data->id,
                            "subscriberMsisdn"=>$data->data->subscriberMsisdn,
                            "amount"=> $data->data->amount,
                            "payToken"=> $data->data->payToken,
                            "status"=> $data->data->status,
                            "txnid"=> $data->data->txnid,
                            "inittxnstatus"=> $data->data->inittxnstatus,
                            "confirmtxnmessage"=> $data->data->confirmtxnmessage,
                            "orderId"=> $data->data->orderId,
                            "description"=> $data->data->description,
                            "createtime"=> $data->data->createtime,
                        ]
                    ],$response->status());

            }
        }catch (\Exception $e){
            return response()->json(
                [
                    'success'=> false,
                    'messsage'=>  $e->getMessage(),

                ],$e->getCode()
            );
        }

    }
}
