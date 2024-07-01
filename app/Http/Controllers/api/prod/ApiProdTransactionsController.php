<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use App\Http\Enums\TypeServiceEnum;
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
     * @OA\Parameter(
     *      name="agentId",
     *      description="The reference agent who carried out the transaction",
     *      required=false,
     *      in="query",
     *      @OA\Schema(
     *         type="string"
     *      )
     *  ),
     * @OA\Parameter(
     *     name="startDate",
     *     description="Start date - format yyyy-mm-dd",
     *     required=true,
     *     in="query",
     *     @OA\Schema(
     *        type="string",
     *        format="date"
     *     )
     * ),
     * @OA\Parameter(
     *      name="endDate",
     *      description="End date - format yyyy-mm-dd",
     *      required=true,
     *      in="query",
     *      @OA\Schema(
     *         type="string",
     *         format="date"
     *      )
     *  ),
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
     *             @OA\Property(property="amount", type="number", example="amount of transaction"),
     *             @OA\Property(property="fees", type="number", example="transaction fees"),
     *             @OA\Property(property="merchand_amount", type="number", example="Amount reimbursed to merchant"),
     *             @OA\Property(property="agent", type="string", example="agent who initiate transaction"),
     *             @OA\Property(property="customer", type="number", example="customer phone number"),
     *             @OA\Property(property="marchandTransactionID", type="number", example="id transaction of partner"),
     *             @OA\Property(property="dateEndTransaction", type="date", example="Date end transaction"),
     *             @OA\Property(property="status", type="string", example="Transaction status"),
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


    public function getLastTransactionSwagger(Request $request){


        $validator = Validator::make($request->all(), [
            'startDate' => 'required|string',
            'endDate' => 'required|string',
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
        //$partenaireId = $request->partenaireID;
        $partenaire = "";

        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("fichier","agent")
           // ->where("source",Auth::user()->id)
            ->where('transactions.status',1)
            ->where("transactions.date_transaction",">=",$startDate.' 00:00:00')
            ->where("transactions.date_transaction","<=",$endDate.' 23:59:59');
return response()->json([
            'status'=>"true",
            'message'=> $transactions->count()." transactions trouvées",
            'transactions'=> $transactions->get(),
            'partenaire'=>$partenaire

        ], 200);

        if($request->partenaireID !=0 || $request->partenaireID !=null){
            $transactions = $transactions->where("services.partenaire_id",$request->partenaireID);
            $partenaire = DB::table('partenaires')->where('id',$request->partenaireID)->first()->name_partenaire;
        }

        $transactions = $transactions->orderBy('transactions.date_transaction', 'desc')->get();

        $user = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();

        if($transactions->isEmpty()){
            return response()->json(['message' => 'Aucune transaction trouvée'], 404);
        }

        $appro=$transactions->where( 'type_service_id', TypeServiceEnum::APPROVISIONNEMENT->value)->sum("credit");
        $depot=$transactions->where( 'type_service_id', TypeServiceEnum::ENVOI->value)->sum("debit");
        $retrait=$transactions->where('type_service_id', TypeServiceEnum::RETRAIT->value)->sum("credit");
        $facture=$transactions->where('type_service_id', TypeServiceEnum::FACTURE->value)->sum("debit");
        $commission=$transactions->sum("commission");

        $solde = $appro + $retrait-$depot-$facture;
        return response()->json([
            'status'=>"true",
            'message'=> $transactions->count()." transactions trouvées",
            'appro'=>$appro,
            'depot'=>$depot,
            'retrait'=>$retrait,
            'facture'=>$facture,
            'solde'=>$solde,
            'commission'=>$commission,
            'transactions'=> $transactions,
            'partenaire'=>$partenaire,
            'user'=>$user

        ], 200);

    }

}
