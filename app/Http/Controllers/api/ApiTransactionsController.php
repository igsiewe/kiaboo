<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\StatusTransEnum;

use App\Http\Enums\TypeServiceEnum;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ApiTransactionsController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getLastTransaction($nbre){
        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("fichier","agent")
            ->where("source",Auth::user()->id)
            ->where('transactions.status',1)
            ->orderBy('transactions.date_transaction', 'desc')
            ->limit($nbre)
            ->get();

        $user = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();

        if($transactions->isEmpty()){
            return response()->json(['message' => 'Aucune transaction trouvée'], 404);
        }
        $appro=$transactions->where('type_service_id', TypeServiceEnum::APPROVISIONNEMENT->value)->sum("credit");
        $depot=$transactions->where('type_service_id', TypeServiceEnum::ENVOI->value)->sum("debit");
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
            'user'=>$user,


        ], 200);

    }

    public function getTransaction(Request $request){

        $validator = Validator::make($request->all(), [
            'startDate' => 'required|string',
            'endDate' => 'required|string',
            // 'partenaireID' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'echec',
                'message' => $validator->errors(),
            ], 400);
        }

        $startDate = Carbon::createFromFormat('d/m/Y', $request->startDate)->format('Y-m-d');
        $endDate = Carbon::createFromFormat('d/m/Y', $request->endDate)->format('Y-m-d');
        $partenaireId = $request->partenaireID;
        $partenaire = "";

        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("fichier","agent")
            ->where("source",Auth::user()->id)
            ->where('transactions.status',1)
            ->where("transactions.date_transaction",">=",$startDate.' 00:00:00')
            ->where("transactions.date_transaction","<=",$endDate.' 23:59:59');

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

    public function getTransactionId($id){
        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("transactions.id",$id)->get();


        if($transactions->isEmpty() || $transactions->count() == 0){
            return response()->json(['message' => 'Aucune transaction trouvée'], 404);
        }else{
            return response()->json([
                'status'=>"true",
                'message'=> $transactions->count()." transactions trouvées",
                'transactions'=> $transactions
            ], 200);
        }
    }

    public function trans(){
        $query = Transaction::with(['service.typeService'])
            ->where("fichier","agent")
            ->where('status',1)
            ->whereHas('service',function ($query){
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value]);
            });
        $count = $query->count();
        if($count>0){
            return response()->json([
                'status'=>"true",
                'message'=> $count." transactions trouvées",
                'transactions'=> $query->get()
            ], 200);
        }else{
            return response()->json(['message' => 'Aucune transaction trouvée'], 404);
        }

    }


    public function getTransactionPending(){

        //On recupère toutes les transactions en attente de l'utilisateur connecté

        $transactionsEnAttente = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("transactions.fichier","=","agent")
            ->where("transactions.source",Auth::user()->id)
            ->where('transactions.status',StatusTransEnum::PENDING->value)
            ->orderBy('transactions.date_transaction', 'desc')
            ->get();

          //  dd($transactionsEnAttente->count());

        if($transactionsEnAttente->count()==0 || $transactionsEnAttente->isEmpty()){
            return response()->json([
                'status' => 'false',
                'message' => 'You have no pending transactions'
            ], 404);
        }else{
            return response()->json([
                'status'=>"true",
                'message'=> $transactionsEnAttente->count()." transactions trouvées",
                'transactions'=> $transactionsEnAttente
            ], 200);
        }
    }

    /**
     * @OA\Post (
     * path="/api/v1/prod/transaction/{agentId}",
     * summary="get list of last 5 transactions",
     * description="This request provides a list of the last five transactions carried out by a partner. If you want to filter by agent, enter the partner's agent reference {agentId}.",
     * tags={"Transactions"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="startDate",
     *     description="Start date - format dd/mm/yyyy",
     *     required=true,
     *     in="path",
     *     @OA\Schema(
     *        type="date"
     *     )
     * ),
     * @OA\Parameter(
     *      name="endDate",
     *      description="End date - format dd/mm/yyyy",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *         type="date"
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
     *             @OA\Property(property="status", type="string", example="Transaction status"),
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


    public function getLastTransactionSwagger(Request $request, $agentId = null){

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

        $startDate = Carbon::createFromFormat('d/m/Y', $request->startDate)->format('Y-m-d');
        $endDate = Carbon::createFromFormat('d/m/Y', $request->endDate)->format('Y-m-d');
        $partenaireId = $request->partenaireID;
        $partenaire = "";

        $transactions = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("fichier","agent")
            ->where("source",Auth::user()->id)
            ->where('transactions.status',1)
            ->where("transactions.date_transaction",">=",$startDate.' 00:00:00')
            ->where("transactions.date_transaction","<=",$endDate.' 23:59:59');

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
