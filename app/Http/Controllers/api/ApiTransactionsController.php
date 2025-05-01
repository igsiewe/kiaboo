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
            ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("fichier","agent")
            ->where("source",Auth::user()->id)
            ->where('transactions.status',1)
            ->orderBy('transactions.date_transaction', 'desc')
            ->limit($nbre)
            ->get();

      //  $user = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();
        $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
            ->join("villes", "quartiers.ville_id", "=", "villes.id")
            ->where('users.id', Auth::user()->id)
            ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

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
            ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
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

       // $user = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();
        $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
            ->join("villes", "quartiers.ville_id", "=", "villes.id")
            ->where('users.id', Auth::user()->id)
            ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

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
            ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
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
            ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("transactions.fichier","=","agent")
            ->where("transactions.source",Auth::user()->id)
            ->where('transactions.status',StatusTransEnum::PENDING->value)
            ->whereIn('type_services.id',[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
            ->orderBy('transactions.date_transaction', 'desc')
            ->get();

          //  dd($transactionsEnAttente->count());

        if($transactionsEnAttente->count()==0 || $transactionsEnAttente->isEmpty()){
            return response()->json([
                'status' => 'false',
                'message' => "Vous n'avez aucune transaction en attente."
            ], 404);
        }else{
            return response()->json([
                'status'=>"true",
                'message'=> $transactionsEnAttente->count()." transactions trouvées",
                'transactions'=> $transactionsEnAttente
            ], 200);
        }
    }

    public function getTransactionFail(){

        //On recupère toutes les transactions en attente de l'utilisateur connecté

        $transactionsFail = DB::table('transactions')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
            ->where("transactions.fichier","=","agent")
            ->where("transactions.source",Auth::user()->id)
            ->where('transactions.status',StatusTransEnum::CANCELED->value)
            ->whereIn('type_services.id',[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])->limit(10)
            ->orderBy('transactions.date_transaction', 'desc')
            ->get();

        //  dd($transactionsEnAttente->count());

        if($transactionsFail->count()==0 || $transactionsFail->isEmpty()){
            return response()->json([
                'status' => 'false',
                'message' => "Vous n'avez aucune transaction en échec."
            ], 404);
        }else{
            return response()->json([
                'status'=>"true",
                'message'=> $transactionsFail->count()." transactions trouvées",
                'transactions'=> $transactionsFail
            ], 200);
        }
    }

}
