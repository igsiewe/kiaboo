<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiProdTransactionsController extends Controller
{

    /**
     * @OA\Post(
     * path="/api/v1/prod/transactions",
     * summary="get list of transactions",
     * description="This request provides a list of the last five transactions carried out by a partner. If you want to filter by agent, enter the partner's agent reference {agentId}.",
     * tags={"Transactions"},
     * security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *     required=true,
     *     description="Request to get transaction",
     *     @OA\JsonContent(
     *        required={"startDate","endDate"},
     *        @OA\Property(property="agentId", type="string", example="679962015"),
     *        @OA\Property(property="startDate", format="date", example="2024-01-01"),
     *        @OA\Property(property="endDate", format="date", example="2024-01-31"),
     *     ),
     *  ),
     *
     * @OA\Response(
     *     response=200,
     *     description="Transaction found",
     *     @OA\JsonContent(
     *        @OA\Property(property="success", type="boolean", example="true"),
     *        @OA\Property(property="statusCode", type="string", example="SUCCESS"),
     *        @OA\Property(property="message", type="string", example="Transaction found"),
     *        @OA\Property(
     *             type="object",
     *             property="data",
     *             @OA\Property(property="transactionsId", type="string", example="transactionId"),
     *             @OA\Property(property="dateTransaction", type="date", example="Date transaction"),
     *             @OA\Property(property="serviceName", type="string", example="Name service"),
     *             @OA\Property(property="amount", type="number", example="amount of transaction"),
     *             @OA\Property(property="fees", type="number", example="transaction fees"),
     *             @OA\Property(property="merchand_amount", type="number", example="Amount reimbursed to merchant"),
     *             @OA\Property(property="agent", type="string", example="agent who initiate transaction"),
     *             @OA\Property(property="customer", type="number", example="customer phone number"),
     *             @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
     *             @OA\Property(property="dateEndTransaction", type="date", example="Date end transaction"),
     *             @OA\Property(property="status", type="string", example="Transaction status"),
     *             @OA\Property(property="logoService", type="string", example="Service logo name"),
     *        )
     *     )
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
     *  @OA\Response(
     *      response=422,
     *      description="attribute invalid",
     *      @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example="false"),
     *         @OA\Property(property="statusCode", type="string", example="ERR-ATTRIBUTES-INVALID"),
     *         @OA\Property(property="message", type="string", example="attribute not valid"),
     *      )
     *   ),
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


    public function getTransactionSwagger(Request $request){
        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response(
                [
                    'success'=>false,
                    'statusCode' => 'ERR-ATTRIBUTES-INVALID',
                    'message' => $validator->errors()->first()

                ], 422);
        }

        $startDate =$request->startDate;// Carbon::createFromFormat('d/m/Y', $request->startDate)->format('Y-m-d');
        $endDate =$request->endDate;// Carbon::createFromFormat('d/m/Y', $request->endDate)->format('Y-m-d');
        $telephoneAgent= $request->agentId;
        $listAgent=User::where('distributeur_id',Auth::user()->distributeur_id)->where('type_user_id',UserRolesEnum::AGENT->value)->pluck('id')->toArray();

        if($listAgent == null || empty($listAgent)){
            return response()->json([
                "success"=> false,
                "statusCode"=>"ERR-AGENT-NOT-FOUND",
                "message"=>"Agent ID not found"
            ], 404);
        }

        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->join('users','users.id','=','transactions.source')
            ->select('transactions.reference as transactionId','transactions.paytoken','transactions.date_transaction as dateTransaction','transactions.credit as amount' ,'transactions.commission_agent_rembourse as fees','transactions.balance_before','transactions.balance_after' ,'transactions.customer_phone as customer','transactions.description as status','services.name_service as serviceName','type_services.name_type_service as type_service','users.telephone as agent','transactions.marchand_transaction_id as marchandTransactionID','transactions.date_end_trans as dateEndTransaction','services.logo_service as logoService')
            ->where("fichier","agent")
            ->where('transactions.status',1)
            ->where("transactions.date_transaction",">=",$startDate.' 00:00:00')
            ->where("transactions.date_transaction","<=",$endDate.' 23:59:59')
            ->whereIn('transactions.source',$listAgent);

        if($telephoneAgent !=0 || $telephoneAgent !=null){

            $agent=User::where('telephone',$telephoneAgent)->where('distributeur_id',Auth::user()->distributeur_id)->get();
            if($agent->count() == 0){
                return response()->json([
                    "success"=> false,
                    "statusCode"=>"ERR-AGENT-NOT-FOUND",
                    "message"=>"Agent ID not found"
                ], 404);
            }
            $agentId = $agent->first()->id;
            $transactions = $transactions->where("transactions.source",$agentId);
        }

        $transactions = $transactions->orderBy('transactions.date_transaction', 'desc')->get();

        if($transactions->isEmpty()){
            return response()->json([
                "success"=> false,
                "statusCode"=>"ERR-TRANSACTION-NOT-FOUND",
                "message"=>"Transaction not found "
            ], 404);

        }

        return response()->json([
            'success'=>true,
            'statusCode' => 'SUCCESS',
            'message'=> $transactions->count()." transactions found",
            'data'=>$transactions,
        ], 200);

    }

    public function getLastTransactionSwagger($nbre=5){

        $listAgent=User::where('distributeur_id',Auth::user()->distributeur_id)->where('type_user_id',UserRolesEnum::AGENT->value)->pluck('id')->toArray();

        if($listAgent == null || empty($listAgent)){
            return response()->json([
                "success"=> false,
                "statusCode"=>"ERR-AGENT-NOT-FOUND",
                "message"=>"Agent ID not found"
            ], 404);
        }

        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->join('users','users.id','=','transactions.source')
            ->select('transactions.reference as transactionId','transactions.paytoken','transactions.date_transaction as dateTransaction','transactions.credit as amount' ,'transactions.commission_agent_rembourse as fees','transactions.balance_before','transactions.balance_after' ,'transactions.customer_phone as customer','transactions.description as status','services.name_service as serviceName','type_services.name_type_service as type_service','users.telephone as agent','transactions.marchand_transaction_id as marchandTransactionID','transactions.date_end_trans as dateEndTransaction','services.logo_service as logoService')
            ->where("fichier","agent")
           // ->where('transactions.status',1)
            ->whereIn('transactions.source',$listAgent)
            ->orderBy('transactions.date_transaction', 'desc')
            ->limit($nbre)
            ->get();

        if($transactions->isEmpty()){
            return response()->json([
                "success"=> false,
                "statusCode"=>"ERR-TRANSACTION-NOT-FOUND",
                "message"=>"Transaction not found "
            ], 404);

        }

        return response()->json([
            'success'=>true,
            'statusCode' => 'SUCCESS',
            'message'=> $transactions->count()." transactions found",
            'data'=>$transactions,
        ], 200);

    }

    public function getDataDashBoard(){

        $agent = User::where('distributeur_id',Auth::user()->distributeur_id)->where('type_user_id',UserRolesEnum::AGENT->value)
            ->select('id','name','surname','telephone','email','status','balance_after','balance_after as balance','sum_payment','sum_refund');

        $listAgent=$agent->pluck('id')->toArray();

        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->join('users','users.id','=','transactions.source')
            ->select('transactions.reference as transactionId','transactions.paytoken','transactions.date_transaction as dateTransaction','transactions.credit as amount' ,'transactions.commission_agent_rembourse as fees','transactions.balance_before','transactions.balance_after' ,'transactions.customer_phone as customer','transactions.description as status','services.name_service as serviceName','type_services.name_type_service as type_service','users.telephone as agent','transactions.marchand_transaction_id as marchandTransactionID','transactions.date_end_trans as dateEndTransaction','services.logo_service as logoService')
            ->where("fichier","agent")
            // ->where('transactions.status',1)
            ->whereIn('transactions.source',$listAgent)
            ->orderBy('transactions.date_transaction', 'desc')
            ->limit(5)
            ->get();
        if($agent->count()>0){
            return response()->json([
                'success'=>true,
                'statusCode' => 'SUCCESS',
                'numberAgent'=> $agent->count(),
                'totalBalance'=> $agent->sum('balance_after'),
                'sumPayment'=> $agent->sum('sum_payment'),
                'sumRefund'=> $agent->sum('sum_refund'),
                'transactions'=>$transactions,
                'agents'=>$agent->get(),
            ], 200);
        }else{
            return response()->json([
                'success'=>false,
                'statusCode' => 'ERR-AGENT-NOT-FOUND',
                'message'=> 'Agent not found',
            ], 404);
        }
    }

    public function getLastUserTransaction($nbre){

        $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
            ->join("villes", "quartiers.ville_id", "=", "villes.id")
            ->where('users.id', Auth::user()->id)
            ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code','users.total_fees','users.total_paiement')->first();

        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent','transactions.fees')
            ->where("fichier","agent")
            ->where("source",Auth::user()->id)
            ->where('transactions.status',1)
            ->orderBy('transactions.date_transaction', 'desc')
            ->limit($nbre)
            ->get();

        return response()->json([
            'status'=>"true",
            'transactions'=> $transactions,
            'user'=> $user
        ],200);
    }
}
