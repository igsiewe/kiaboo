<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use App\Http\Enums\UserRolesEnum;
use App\Models\ApproDistributeur;
use App\Models\Distributeur;
use App\Models\SousDistributeur;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiApproSousDistributeurController extends BaseController
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
    public function GenereRang(){

        $rang = "";
        $chaine = Transaction::all()->count();
        $longueur = strlen($chaine);

        if($longueur==0){
            $rang = "00001";
        }
        if ( $longueur == 1){
            $rang="0000".($chaine+1);
        }
        if ( $longueur == 2){
            $rang="000".($chaine+1);
        }
        if ( $longueur == 3){
            $rang="00".($chaine+1);
        }
        if ( $longueur == 4){
            $rang="0".($chaine+1);
        }
        if ( $longueur > 4){
            $rang=($chaine+1);
        }

        return $rang;
    }
    public function approSousDistributeur(Request $request){
        $validator = Validator::make($request->all(), [
            'sousdistributeur' => 'required|integer',
            'amount' => 'required|integer|min:50000|max:10000000',
        ]);
        if(Auth::user()->status == 0){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }
        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }

        if ($validator->fails()) {
            return response(['status'=>422,'message' => $validator->errors()->first()], 422);
        }
        $distributeur_id = Auth::user()->distributeur_id;
        $distributeur = Distributeur::where('id', $distributeur_id)->get();
        if($distributeur->count()==0){
            return $this->errorResponse("Distributeur have not been specified in your profil", 404);
        }
        $balanceDistributeur = $distributeur->first()->balance_after;
        $newBalanceDistributeur = doubleval($balanceDistributeur)-doubleval($request->amount);
        if(doubleval($balanceDistributeur)<=doubleval($request->amount)){
            return $this->errorResponse("You don't have enough balance to perform this operation", 404);
        }
        $sousdistributeur = SousDistributeur::where('id', $request->sousdistributeur)->get();

        if($sousdistributeur->count()==0){
            return $this->errorResponse("Sous distributeur not found", 404);
        }
        if($sousdistributeur->first()->status ==0){
            return $this->errorResponse("Sous distributeur is not active", 404);
        }

        if($sousdistributeur->first()->distributeur_id != $distributeur_id){
            return $this->errorResponse("You can't authorize to relaod this sous distributor", 404);
        }
        $balanceSousDistributeur = $sousdistributeur->first()->balance_after;
        $newBalanceSousDistributeur = doubleval($balanceSousDistributeur)+doubleval($request->amount);


        try{
            DB::beginTransaction();
            $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();

            $distributeur->first()->update([
                'balance_after'=>$newBalanceDistributeur,
                'balance_before'=>$balanceDistributeur,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>2,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);
            $sousdistributeur->first()->update([
                'balance_after'=>$newBalanceSousDistributeur,
                'balance_before'=>$balanceSousDistributeur,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>2,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);

            //Debit table transaction pour le distributeur,
            $debitDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>2,
                'balance_before'=>$balanceDistributeur,
                'balance_after'=>$newBalanceDistributeur,
                'debit'=>$request->amount,
                'credit'=>0,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$distributeur_id,
                'fichier'=>"distributeur",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
            ]);

            //Credit table transaction pour le sous distributeur
            $creditSousDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>2,
                'balance_before'=>$balanceSousDistributeur,
                'balance_after'=>$newBalanceSousDistributeur,
                'debit'=>0,
                'credit'=>$request->amount,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$request->sousdistributeur,
                'fichier'=>"sous_distributeur",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
            ]);
            DB::commit();
            return $this->sendResponse($payToken,"Approvisionnement sous distributeur effectué avec succès");

        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }
    }

    public function approAgent(Request $request){
        $validator = Validator::make($request->all(), [
            'agent' => 'required|integer',
            'amount' => 'required|integer|min:50000|max:10000000',
        ]);
        if(Auth::user()->status == 0){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }
        if(Auth::user()->type_user_id != UserRolesEnum::SDISTRIBUTEUR->value){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }

        if ($validator->fails()) {
            return response(['status'=>422,'message' => $validator->errors()->first()], 422);
        }

        $sousDistributeur_id = Auth::user()->sous_distributeur_id;
        $sousDistributeur = SousDistributeur::where('id', $sousDistributeur_id)->get();
        if($sousDistributeur->count()==0){
            return $this->errorResponse("Sous distributeur have not been specified in your profil", 404);
        }
        $balanceSousDistributeur = $sousDistributeur->first()->balance_after;
        $newBalanceSousDistributeur = doubleval($balanceSousDistributeur)-doubleval($request->amount);
        if(doubleval($balanceSousDistributeur)<=doubleval($request->amount)){
            return $this->errorResponse("You don't have enough balance to perform this operation", 404);
        }
        $agent = User::where('id', $request->agent)->get();

        if($agent->count()==0){
            return $this->errorResponse("Agent not found", 404);
        }
        if($agent->first()->status ==0){
            return $this->errorResponse("Agent is not active", 404);
        }

        if($agent->first()->sous_distributeur_id != $sousDistributeur_id){
            return $this->errorResponse("You can't authorize to relaod this agent", 404);
        }
        $balanceAgent = $agent->first()->balance_after;
        $newBalanceAgent = doubleval($balanceAgent)+doubleval($request->amount);


        try{
            DB::beginTransaction();
            $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();
            //Mise à jour sous distributeur

            $sousDistributeur->first()->update([
                'balance_after'=>$newBalanceSousDistributeur,
                'balance_before'=>$balanceSousDistributeur,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>3,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);

            $agent->first()->update([
                'balance_after'=>$newBalanceAgent,
                'balance_before'=>$balanceAgent,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>3,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);
            //Debit table transaction pour le sous distributeur,
            $debitDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>3,
                'balance_before'=>$balanceSousDistributeur,
                'balance_after'=>$newBalanceSousDistributeur,
                'debit'=>$request->amount,
                'credit'=>0,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$sousDistributeur_id,
                'fichier'=>"sous_distributeur",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>Auth::user()->telephone,
            ]);

            //Credit table transaction pour l'agent
            $creditSousDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>2,
                'balance_before'=>$balanceAgent,
                'balance_after'=>$newBalanceAgent,
                'debit'=>0,
                'credit'=>$request->amount,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$request->agent,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>$agent->first()->telephone,
            ]);
            DB::commit();
            return $this->sendResponse($payToken,"Approvisionnement agent effectué avec succès");

        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }
    }

    public function approAgentParCarte(Request $request){
        $validator = Validator::make($request->all(), [
           // 'agent' => 'required|integer',
            'amount' => 'required|integer|min:50000|max:10000000',
            'reference_trans_carte'=>'required|string',
        ]);
        if(Auth::user()->status == 0){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }

        if ($validator->fails()) {
            return response(['status'=>422,'message' => $validator->errors()->first()], 422);
        }

        $sousDistributeur_id =1;// Auth::user()->sous_distributeur_id;
        $sousDistributeur = SousDistributeur::where('id', $sousDistributeur_id)->get();
        $sousDistributeurPhone = $sousDistributeur->first()->phone;

        if($sousDistributeur->count()==0){
            return $this->errorResponse("Sous distributeur have not been specified in your profil", 404);
        }
        $balanceSousDistributeur = $sousDistributeur->first()->balance_after;
        $newBalanceSousDistributeur = doubleval($balanceSousDistributeur)-doubleval($request->amount);
        if(doubleval($balanceSousDistributeur)<=doubleval($request->amount)){
            return $this->errorResponse("A subdistributor don't have enough balance to perform this operation", 404);
        }
        $agent = User::where('id', Auth::user()->id)->get();

        if($agent->count()==0){
            return $this->errorResponse("Agent not found", 404);
        }
        if($agent->first()->status ==0){
            return $this->errorResponse("Agent is not active", 404);
        }

        if($agent->first()->sous_distributeur_id != $sousDistributeur_id){
            return $this->errorResponse("You can't authorize to relaod this agent", 404);
        }
        $balanceAgent = $agent->first()->balance_after;
        $newBalanceAgent = doubleval($balanceAgent)+doubleval($request->amount);


        try{
            DB::beginTransaction();
            $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();
            //Mise à jour sous distributeur

            $sousDistributeur->first()->update([
                'balance_after'=>$newBalanceSousDistributeur,
                'balance_before'=>$balanceSousDistributeur,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>3,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);

            $agent->first()->update([
                'balance_after'=>$newBalanceAgent,
                'balance_before'=>$balanceAgent,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>3,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);
            //Debit table transaction pour le sous distributeur,
            $debitDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>3,
                'balance_before'=>$balanceSousDistributeur,
                'balance_after'=>$newBalanceSousDistributeur,
                'debit'=>$request->amount,
                'credit'=>0,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$sousDistributeur_id,
                'fichier'=>"sous_distributeur",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>Auth::user()->telephone,
                'moyen_payment'=>'carte',
                'reference_trans_carte'=>$request->reference_trans_carte,
                'date_end_trans'=>Carbon::now(),
            ]);

            //Credit table transaction pour l'agent
            $creditSousDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>2,
                'balance_before'=>$balanceAgent,
                'balance_after'=>$newBalanceAgent,
                'debit'=>0,
                'credit'=>$request->amount,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>Auth::user()->id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>$sousDistributeurPhone,
                'moyen_payment'=>'carte',
                'reference_trans_carte'=>$request->reference_trans_carte,
                'date_end_trans'=>Carbon::now(),
            ]);

            $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();

            $transactionsRefresh = DB::table('transactions')
                ->join('services', 'transactions.service_id', '=', 'services.id')
                ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation')
                ->where("fichier","agent")
                ->where("source",Auth::user()->id)
                ->where('transactions.status',1)
                ->orderBy('transactions.date_transaction', 'desc')
                ->limit(5)
                ->get();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Votre compte a été approvisionné avec succès',
                'user'=>$userRefresh,
                'transactions'=>$transactionsRefresh,
            ], 200);

           // return $this->sendResponse($payToken,"Votre compte a été approvisionné avec succès");

        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }
    }
}
