<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\ApproDistributeur;
use App\Models\Approvisionnement;
use App\Models\Distributeur;
use App\Models\SousDistributeur;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiApproDistributeurController extends BaseController
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
        $chaine = ApproDistributeur::all()->count();
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
    function genererChaineAleatoire($longueur = 10)
    {
        // $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longueurMax = strlen($caracteres);
        $chaineAleatoire = '';
        for ($i = 0; $i < $longueur; $i++)
        {
            $chaineAleatoire .= $caracteres[rand(0, $longueurMax - 1)];
        }
        return $chaineAleatoire;
    }

    public function initApproDistributeur(Request $request)  {

        if(Auth::user()->type_user_id != UserRolesEnum::FRONTOFFICE->value){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }
        $validator = Validator::make($request->all(), [
            'distributeur' => 'required|integer',
            'amount' => 'required|integer|min:100000|max:10000000',
        ]);
        if(Auth::user()->status == 0){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }
        if ($validator->fails()) {
            return response(['status'=>422,'message' => $validator->errors()->first()], 422);
        }

        $distributeur = Distributeur::where('id',$request->distributeur)->where('status',1);
        if ($distributeur->count()==0) {
            return $this->errorResponse('Distributeur not found.', 404);
        }
        if($distributeur->first()->status == 0){
            return $this->errorResponse('Distributeur is not active.', 404);
        }



        $reference = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".I".$this->GenereRang();
        try {
            DB::beginTransaction();
            $approDistributeur = new ApproDistributeur();
            $approDistributeur->reference = $reference;
            $approDistributeur->date_operation = Carbon::now();
            $approDistributeur->distributeur_id = $request->distributeur;
            $approDistributeur->amount = $request->amount;
            $approDistributeur->description = $request->description;
            $approDistributeur->status = 0;
            $approDistributeur->created_by = Auth::user()->id;
            $approDistributeur->countrie_id = Auth::user()->countrie_id;
            $approDistributeur->save();
            DB::commit();
            return $this->sendResponse($approDistributeur, 'Approvisionnement initiated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }

    }

    public function validatedApproDistributeur($reference){

        if(Auth::user()->type_user_id != UserRolesEnum::BACKOFFICE->value){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }
        $approDistributeur = ApproDistributeur::where('reference',$reference)->get();
        $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();

        if($approDistributeur->count()==0){
            return $this->errorResponse('Approvisionnement not found.', 404);
        }

        if($approDistributeur->first()->status==1){
            return $this->errorResponse('The approvsionnement '.$reference.' has already been validated', 404);
        }
        $distributeur_id = $approDistributeur->first()->distributeur_id;

        try {
            DB::beginTransaction();
            //On met à jour le solde du distributeur
            $distributeur = Distributeur::where('id',$distributeur_id)->get();
            $balance_before = $distributeur->first()->balance_after;
            $balance_after = $distributeur->first()->balance_after + $approDistributeur->first()->amount;
            //On met à jour l'approvisionnement dans la table approvisionnement

            $activeApproDistributeur = DB::table("appro_distributeurs")->where('reference',$reference)->where("status",0)->update([
                'status' => 1,
                'updated_by' => Auth::user()->id,
                'validated_by' => Auth::user()->id,
                'date_validation'=> Carbon::now(),
                'reference_validation' => $payToken,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
            ]);

            if($activeApproDistributeur){

                $updateSoldeDistributeur = DB::table("distributeurs")->where('id',$distributeur_id)->update([
                    'balance_after' => $balance_after,
                    'balance_before' => $balance_before,
                    'last_amount' => $approDistributeur->first()->amount,
                    'date_last_transaction' => Carbon::now(),
                    'last_transaction_id' => $approDistributeur->first()->id,
                    'last_service_id' => 1, //1 = Approvisionnement
                    'user_last_transaction_id' => Auth::user()->id,
                    'reference_last_transaction'=>$payToken,
                    'updated_by' => Auth::user()->id,
                    'created_by' => Auth::user()->id,
                ]);

                if($updateSoldeDistributeur){
                    //ON crée l'approvisionnement dans la table transactions
                    $transaction =DB::table("transactions")->insert([
                        'reference' => $payToken,
                        'reference_partenaire' => $approDistributeur->first()->reference,
                        'date_transaction' => Carbon::now(),
                        'service_id' => 1, //1 = Approvisionnement
                        'distributeur_id' => $approDistributeur->first()->distributeur_id,
                        'credit' => $approDistributeur->first()->amount,
                        'debit' => 0,
                        'description'=>'SUCCESSFULL',
                        'balance_before' => $balance_before,
                        'balance_after' => $balance_after,
                        'status' => 1,
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                        'paytoken' => $payToken,
                        'countrie_id' => Auth::user()->countrie_id,
                        'created_at' => Carbon::now(),
                        'source'=>$distributeur_id,
                        'fichier' => 'distributeur',
                        'date_operation'=>date('Y-m-d'),
                        'heure_operation'=>date('H:i:s'),
                        'customer_phone'=>Auth::user()->telephone,
                        'date_end_trans'=>Carbon::now(),

                    ]);
                    DB::commit();
                    return $this->sendResponse($approDistributeur, 'Approvisionnement validated successfully');
                }else{
                    DB::rollBack();
                    return $this->errorResponse('Error on update balance distributor.', 404);
                }

            }else{
                DB::rollBack();
                return $this->errorResponse('Approvisionnement not updated.', 404);
            }
        }
        catch (\Throwable $e) {
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
        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }

        if ($validator->fails()) {
            return response(['status'=>422,'message' => $validator->errors()->first()], 422);
        }

        $Distributeur_id = Auth::user()->distributeur_id;
        $Distributeur = Distributeur::where('id', $Distributeur_id)->get();

        if($Distributeur->count()==0){
            return $this->errorResponse("Distributor have not been specified in your profil", 404);
        }
        $balanceDistributeur = $Distributeur->first()->balance_after;
        $newBalanceDistributeur = doubleval($balanceDistributeur)-doubleval($request->amount);
        if(doubleval($balanceDistributeur)<=doubleval($request->amount)){
            return $this->errorResponse("You don't have enough balance to perform this operation", 404);
        }
        $agent = User::where('id', $request->agent)->get();

        if($agent->count()==0){
            return $this->errorResponse("Agent not found", 404);
        }
        if($agent->first()->status ==0){
            return $this->errorResponse("Agent is not active", 404);
        }

        if($agent->first()->distributeur_id != $Distributeur_id){
            return $this->errorResponse("You can't authorize to relaod this agent", 404);
        }
        $balanceAgent = $agent->first()->balance_after;
        $newBalanceAgent = doubleval($balanceAgent)+doubleval($request->amount);


        try{
            DB::beginTransaction();
            $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();
            //Mise à jour sous distributeur

            $Distributeur->first()->update([
                'balance_after'=>$newBalanceDistributeur,
                'balance_before'=>$balanceDistributeur,
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
            //Debit table transaction pour le distributeur,
            $debitDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>3,
                'balance_before'=>$balanceDistributeur,
                'balance_after'=>$newBalanceDistributeur,
                'debit'=>$request->amount,
                'credit'=>0,
                'status'=>1,
                'description'=>'SUCCESSFULL',
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$Distributeur_id,
                'distributeur_id'=>$Distributeur_id,
                'agent_id'=>$request->agent,
                'balance_before_partenaire'=>$balanceAgent,
                'balance_after_partenaire'=>$newBalanceAgent,
                'fichier'=>"distributeur",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>$agent->first()->telephone,
                'date_end_trans'=>Carbon::now(),
                'moyen_payment'=>"Cash",
            ]);

            //Credit table transaction pour l'agent
            $creditSousDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>3,
                'balance_before'=>$balanceAgent,
                'balance_after'=>$newBalanceAgent,
                'debit'=>0,
                'status'=>1,
                'description'=>'SUCCESSFULL',
                'credit'=>$request->amount,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$request->agent,
                'agent_id'=>$request->agent,
                'distributeur_id'=>$Distributeur_id, //A enlever
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>Auth::user()->telephone,
                'balance_before_partenaire'=>$balanceDistributeur,
                'balance_after_partenaire'=>$newBalanceDistributeur,
                'date_end_trans'=>Carbon::now(),
                'moyen_payment'=>"Cash",
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

        $Distributeur_id =1;// Auth::user()->sous_distributeur_id;
        $Distributeur = Distributeur::where('id', $Distributeur_id)->get();
        $sousDistributeurPhone = $Distributeur->first()->phone;

        if($Distributeur->count()==0){
            return $this->errorResponse("Distributor have not been specified in your profil", 404);
        }
        $balanceDistributeur = $Distributeur->first()->balance_after;
        $newBalanceDistributeur = doubleval($balanceDistributeur)-doubleval($request->amount);
        if(doubleval($balanceDistributeur)<=doubleval($request->amount)){
            return $this->errorResponse("A distributor don't have enough balance to perform this operation", 404);
        }
        $agent = User::where('id', Auth::user()->id)->get();

        if($agent->count()==0){
            return $this->errorResponse("Agent not found", 404);
        }
        if($agent->first()->status ==0){
            return $this->errorResponse("Agent is not active", 404);
        }

        if($agent->first()->distributeur_id != $Distributeur_id){
            return $this->errorResponse("You can't authorize to relaod this agent", 404);
        }
        $balanceAgent = $agent->first()->balance_after;
        $newBalanceAgent = doubleval($balanceAgent)+doubleval($request->amount);


        try{
            DB::beginTransaction();
            $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();
            //Mise à jour sous distributeur

            $Distributeur->first()->update([
                'balance_after'=>$newBalanceDistributeur,
                'balance_before'=>$balanceDistributeur,
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
                'balance_before'=>$balanceDistributeur,
                'balance_after'=>$newBalanceDistributeur,
                'debit'=>$request->amount,
                'credit'=>0,
                'status'=>1,
                'description'=>'SUCCESSFULL',
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$Distributeur_id,
                'fichier'=>"distributeur",
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
                'status'=>1,
                'description'=>'SUCCESSFULL',
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
