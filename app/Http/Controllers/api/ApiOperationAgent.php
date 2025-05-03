<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\api\prod\ApiProdMoMoMoneyController;
use App\Http\Controllers\BaseController;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\favoris;
use App\Models\sous_distributeur;
use App\Models\stock_uv_circulation;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiOperationAgent extends BaseController
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

    public function setTransactionDepotOM(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_phone' => 'required',
            'montant' => 'required|integer|min:100|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez remplir tous les champs',
                'errors' => $validator->errors(),
            ], 400);
        }

        if(Auth::user()->status == 0){
            return response()->json([
                'success' => false,
                'message' => 'Le compte de l\'agent est désactivé'
            ], 401);
        }

        if(Auth::user()->type_user_id != UserRolesEnum::AGENT->value){
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette opération'
            ], 401);
        }

        if(Auth::user()->balance_after < $request->amount){
            return response()->json([
                'success' => false,
                'message' => 'Your balance does not allow you to perform this transaction'
            ], 401);
        }

        //On se rassure qu'il ne s'agit pas d'une double transaction
        $dateActuelle = Carbon::now();
        $dateAvant = Carbon::now()->addMinutes(-5);

        $dateActuelle = Carbon::parse($dateActuelle);
        $dateAvant = Carbon::parse($dateAvant);

        $checkTransaction = Transaction::where('created_by', Auth::user()->id)
            ->where('service_id', ServiceEnum::DEPOT_OM->value)
            ->where('debit', floatval($request->montant))
            ->where("status",1)
            ->where('customer_phone', $request->customer_phone)
            ->whereBetween('created_at', [$dateAvant, $dateActuelle])
            ->get();

        if ($checkTransaction->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ], 400);
        }


        try {
            DB::beginTransaction();
            //On crée la référence de la transaction
            $reference = "DP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();

            // 1. On crée une transaction dans la table transaction avec status = 0: Initiate
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'date_transaction'=>Carbon::now(),
                'service_id'=>ServiceEnum::DEPOT_OM->value,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>$request->montant,
                'credit'=>0,
                'status'=>0, //Initiate
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>Auth::user()->id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'customer_phone'=>$request->customer_phone,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
            ]);

            // 2. On execute l'API


            //On Calcule la commission
            $com = new ApiCommissionController();
            $idService = ServiceEnum::DEPOT_OM->value;
            $lacommission =$com->getCommissionByService($idService,$request->montant);
            $commission=json_decode($lacommission->getContent());

            if($commission->status==false){
                DB::rollback();
                Log::error([
                    'Erreur Message' => $commission->message,
                    'User' => Auth::user()->id,
                    'Service' => 'Dépot OM',
                    'Montant'=>$request->montant,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $commission->message,
                ], 404);
            }

            $commissionFiliale = doubleval($commission->commission_kiaboo);
            $commissionDistributeur=doubleval($commission->commission_distributeur);
            $commissionAgent=doubleval($commission->commission_agent);

            //On traite le depot par appel API Depot ORange
            $description = $reference.'->'.Auth::user()->id;
            $api = new ApiOrangeMoneyController();
            $response = $api->OM_Cashin_execute($request->customer_phone, $request->montant, $reference, $description);
            $data = $response->getContent();
            $resultat = json_decode($data);

            // dd($resultat);

            if ($response->status() != 200 || $response->getStatusCode() !=200) {
                return response()->json([
                    'success' => false,
                    'message' => $resultat->message,
                ], $response->status());
            }

            //Dépassement de plafond côté Orange Money
            $result = (array)$resultat;
            if (Arr::has($result, 'code')) {
                Log::error([
                    'Erreur Message' => $resultat,
                    'User' => Auth::user()->id,
                    'Service' => 'Dépot OM',
                    'Montant'=>$request->montant,
                ]);
                $data =json_decode($result["message"]);
                return response()->json([
                    'success' => false,
                    'message' => $data->message,
                ], $result["code"]);
            }



            //Par mesure de securité je rappelle les données de l'utilisateur

            $user = User::where('id', Auth::user()->id);
            $balanceBeforeAgent = $user->get()->first()->balance_after;
            $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($request->montant);
            //on met à jour la table transaction

            $Transaction->update([
                'reference_partenaire'=>$resultat->data->txnid,
                'balance_before'=>$balanceBeforeAgent,
                'balance_after'=>$balanceAfterAgent,
                'debit'=>$resultat->data->amount,
                'credit'=>0,
                'status'=>1, //End successfully
                'paytoken'=>$resultat->data->payToken,
                'date_end_trans'=>Carbon::now(),
                'description'=>$resultat->data->status,
                'message'=>$resultat->message,
                'commission'=>$commission->commission_globale,
                'commission_filiale'=>$commissionFiliale,
                'commission_agent'=>$commissionAgent,
                'commission_distributeur'=>$commissionDistributeur,
            ]);

            //on met à jour le solde de l'utilisateur

            //La commmission de l'agent après chaque transaction
            //Forumule 1
                $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");
            //Formule 2
              //  $commission_actuelle = $user->get()->first()->total_commission;
              //  $commission_agent =doubleval($commission_actuelle) + doubleval($commission);

            $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                'balance_after'=>$balanceAfterAgent,
                'balance_before'=>$balanceBeforeAgent,
                'last_amount'=>$request->montant,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>ServiceEnum::DEPOT_OM->value,
                'reference_last_transaction'=>$resultat->data->txnid,
                'remember_token'=>$resultat->data->payToken,
                'total_commission'=>$commission_agent,
            ]);

            DB::commit();
            $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();

            $transactionsRefresh = DB::table('transactions')
                ->join('services', 'transactions.service_id', '=', 'services.id')
                ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                ->select('transactions.id','transactions.reference as reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
                ->where("fichier","agent")
                ->where("source",Auth::user()->id)
                ->where('transactions.status',1)
                ->orderBy('transactions.date_transaction', 'desc')
                ->limit(5)
                ->get();
            return response()->json([
                'success' => true,
                'message' => $resultat->message,
                'textmessage' => $resultat->message,
                'reference' => $resultat->data->txnid,
                'data' => $resultat,
                'user'=>$userRefresh,
                'transactions'=>$transactionsRefresh,
            ], 200);

            // all good
        } catch (\Exception $e) {
            DB::rollback();
            Log::error([
                'erreur Message' => $e->getMessage(),
                'user' => Auth::user()->id,
                'service' => 'Dépot OM',

            ]);

            return response()->json([
                'success' => false,
                'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
            ], 400);
        }
    }

    public function setTransactionRetraitOM(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_phone' => 'required',
            'montant' => 'required|integer|min:100|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez remplir tous les champs',
                'errors' => $validator->errors(),
            ], 400);
        }

        if(Auth::user()->status == 0){
            return response()->json([
                'success' => false,
                'message' => 'Le compte de l\'agent est désactivé'
            ], 401);
        }

        if(Auth::user()->type_user_id != UserRolesEnum::AGENT->value){
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette opération'
            ], 401);
        }

        try {
            DB::beginTransaction();
            //On crée la référence de la transaction
            $reference = "DP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();

            // 1. On crée une transaction dans la table transaction avec status = 0: Initiate
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'date_transaction'=>Carbon::now(),
                'service_id'=>ServiceEnum::RETRAIT_OM->value,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$request->montant,
                'status'=>0, //Initiate
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>Auth::user()->id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'customer_phone'=>$request->customer_phone,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
            ]);

            // 2. On execute l'API

            //On Calcule la commission
            $com = new ApiCommissionController();
            $idService = ServiceEnum::RETRAIT_OM->value;
            $lacommission =$com->getCommissionByService($idService,$request->montant);
            $commission=json_decode($lacommission->getContent());

            if($commission->status==false){
                DB::rollback();
                Log::error([
                    'Erreur Message' => $commission->message,
                    'User' => Auth::user()->id,
                    'Service' => 'Retrait OM',
                    'Montant'=>$request->montant,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $commission->message,
                ], 404);
            }

            $commissionFiliale = doubleval($commission->commission_kiaboo);
            $commissionDistributeur=doubleval($commission->commission_distributeur);
            $commissionAgent=doubleval($commission->commission_agent);

            //On traite le retrait par appel API Depot ORange
            $description = $reference.'->'.Auth::user()->id;
            $api = new ApiOrangeMoneyController();

            $responseToken = $api->OM_GetTokenAccess();
            $dataAcessToken = json_decode($responseToken->getContent());
            $AccessToken = $dataAcessToken->access_token;
            $token = $AccessToken;

            $response = $api->OM_CashOut_execute($request->customer_phone, $request->montant, $reference, $description, $token);
            $data = $response->getContent();


            $resultat = json_decode($data);

            // dd($resultat);

            if ($response->status() != 200 || $response->getStatusCode() !=200) {
                // DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => $resultat->message,
                ], $response->status());
            }

            //On cherche d'autres types d'erreur
            $result = (array)$resultat;
            if (Arr::has($result, 'code')) {
                Log::error([
                    'Erreur Message' => $resultat,
                    'User' => Auth::user()->id,
                    'Service' => 'Retrait OM',
                    'Montant'=>$request->montant,
                ]);
                $data =json_decode($result["message"]);
                return response()->json([
                    'success' => false,
                    'message' => $data->message,
                ], $result["code"]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $resultat->message,
                'textmessage' => $resultat->message,
                'reference' => $resultat->data->txnid,
                'data' => $resultat,
            ], 200);

            // all good
        } catch (\Exception $e) {
            DB::rollback();
            Log::error([
                'erreur Message' => $e->getMessage(),
                'user' => Auth::user()->id,
                'service' => 'Retrait OM',
              //  'PayToken'=>$payToken,

            ]);

            return response()->json([
                'success' => false,
                'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
            ], 400);
        }
    }

    public function setTransactionDepotMOMO(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_phone' => 'required',
            'montant' => 'required|integer|min:100|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 400);
        }

        if(Auth::user()->status == 0){
            return response()->json([
                'success' => false,
                'message' => 'Le compte de l\'agent est désactivé'
            ], 401);
        }

        if(Auth::user()->type_user_id != UserRolesEnum::AGENT->value){
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette opération'
            ], 401);
        }

        if(Auth::user()->balance_after < $request->montant){
            return response()->json([
                'success' => false,
                'message' => 'Your balance does not allow you to perform this transaction'
            ], 401);
        }

        //On se rassure qu'il ne s'agit pas d'une double transaction
        $dateActuelle = Carbon::now();
        $dateAvant = Carbon::now()->addMinutes(-5);

        $dateActuelle = Carbon::parse($dateActuelle);
        $dateAvant = Carbon::parse($dateAvant);

        $checkTransaction = Transaction::where('created_by', Auth::user()->id)
            ->where('service_id', ServiceEnum::DEPOT_MOMO->value)
            ->where('debit', floatval($request->montant))
            ->where("status",1)
            ->where('customer_phone', $request->customer_phone)
            ->whereBetween('created_at', [$dateAvant, $dateActuelle])
            ->get();

        if ($checkTransaction->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ], 400);
        }


        try {
            DB::beginTransaction();
            //On crée la référence de la transaction
            $reference = "DP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();

            // 1. On crée une transaction dans la table transaction avec status = 0: Initiate
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'date_transaction'=>Carbon::now(),
                'service_id'=>ServiceEnum::DEPOT_MOMO->value,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>$request->montant,
                'credit'=>0,
                'status'=>0, //Initiate
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>Auth::user()->id,
             //   'agent_id'=>Auth::user()->id, //Ajouter le 12/10/2023
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'customer_phone'=>$request->customer_phone,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
            ]);

            // 2. On execute l'API


            //On Calcule la commission
            $com = new ApiCommissionController();
            $idService = ServiceEnum::DEPOT_MOMO->value;

            $lacommission =$com->getCommissionByService($idService,$request->montant);
            $commission=json_decode($lacommission->getContent());

            if($commission->status==false){
                DB::rollback();
                Log::error([
                    'Erreur Message' => $commission->message,
                    'User' => Auth::user()->id,
                    'Service' => 'Dépot OM',
                    'Montant'=>$request->montant,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $commission->message,
                ], 404);
            }

            $commissionFiliale = doubleval($commission->commission_kiaboo);
            $commissionDistributeur=doubleval($commission->commission_distributeur);
            $commissionAgent=doubleval($commission->commission_agent);
            //On traite le depot par appel API Depot MOMO

            $api = new ApiProdMoMoMoneyController();
            $description = $reference.'->'.Auth::user()->id;

            $response = $api->MOMO_Depot($request->customer_phone, $request->montant);
            $data = $response->getContent();

            $resultat = json_decode($data);
          //  return $resultat;
            if ($resultat->status != 202) {
                return response()->json([
                    'success' => false,
                    'message' => $resultat->message,
                ], 400);
            }

            //Par mesure de securité je rappelle les données de l'utilisateur

            $user = User::where('id', Auth::user()->id);
            $balanceBeforeAgent = $user->get()->first()->balance_after;
            $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($request->montant);
            //on met à jour la table transaction

            $Transaction->update([
                'reference_partenaire'=>$resultat->reference,
                'balance_before'=>$balanceBeforeAgent,
                'balance_after'=>$balanceAfterAgent,
                'debit'=>$request->montant,
                'credit'=>0,
                'status'=>1, //End successfully
                'paytoken'=>$resultat->payToken,
                'date_end_trans'=>Carbon::now(),
                'description'=>"SUCCESSFULL",
                'message'=>$resultat->message,
                'commission'=>$commission->commission_globale,
                'commission_filiale'=>$commissionFiliale,
                'commission_agent'=>$commissionAgent,
                'commission_distributeur'=>$commissionDistributeur,
            ]);

            //on met à jour le solde de l'utilisateur
            //La commmission de l'agent après chaque transaction

            //Forumule 1
                $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");
            //Formule 2
            //  $commission_actuelle = $user->get()->first()->total_commission;
            //  $commission_agent =doubleval($commission_actuelle) + doubleval($commission);

            $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                'balance_after'=>$balanceAfterAgent,
                'balance_before'=>$balanceBeforeAgent,
                'last_amount'=>$request->montant,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>ServiceEnum::DEPOT_MOMO->value,
                'reference_last_transaction'=>$resultat->reference,
                'remember_token'=>$resultat->payToken,
                'total_commission'=>$commission_agent,
            ]);

            DB::commit();
            $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();

            $transactionsRefresh = DB::table('transactions')
                ->join('services', 'transactions.service_id', '=', 'services.id')
                ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent','transactions.fees')
                ->where("fichier","agent")
                ->where("source",Auth::user()->id)
                ->where('transactions.status',1)
                ->orderBy('transactions.date_transaction', 'desc')
                ->limit(5)
                ->get();
            return response()->json([
                'success' => true,
                'message' => 'Dépot effectué avec succès',
                'textmessage' => $resultat->message,
                'reference' => $resultat->reference,
                'data' => $resultat,
                'user'=>$userRefresh,
                'transactions'=>$transactionsRefresh,
            ], 200);

            // all good
        } catch (\Exception $e) {
            DB::rollback();
            Log::error([
                'Erreur Message' => $e->getMessage(),
                'User' => Auth::user()->id,
                'Service' => 'Dépot OM',
            ]);

            return response()->json([
                'success' => false,
                'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
            ], 400);
        }
    }


    function checkUserValidity(){

        if(Auth::user()->status==0){
            return false;
        }

        if(Auth::user()->type_user_id != UserRolesEnum::AGENT->value){
            return false;
        }

        return true;

    }

    function checkUserBalance($montant)
    {
       if (Auth::user()->balance_after < $montant) {
            return false;
        }
        return true;
    }

    function checkFiveLastTransaction($beneficiaire, $montant, $service){
        $dateActuelle = Carbon::now();
        $dateAvant = Carbon::now()->addMinutes(-5);

        $dateActuelle = Carbon::parse($dateActuelle);
        $dateAvant = Carbon::parse($dateAvant);

        $checkTransaction = Transaction::where('created_by', Auth::user()->id)
            ->where('service_id', $service)
            ->where('debit', floatval($montant))
            ->where("status",1)
            ->where('customer_phone', $beneficiaire)
            ->whereBetween('created_at', [$dateAvant, $dateActuelle])
            ->get();

        if ($checkTransaction->count() > 0) {
            return false;
        }
        return true;
    }

    function init_Depot_OM($montant, $beneficiaire){

        $reference = "DP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();
        $ApiOM = new ApiOrangeMoneyController();
        $responsePayToken = $ApiOM->OM_CashIn_init();

        if ($responsePayToken->getStatusCode()==200){
            $dataPayToken = json_decode($responsePayToken->getContent());
            $payToken = $dataPayToken->data->payToken;
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'paytoken'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>ServiceEnum::DEPOT_OM->value,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>$montant,
                'credit'=>0,
                'status'=>0, //Initiate
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>Auth::user()->id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
              //  'agent_id'=>Auth::user()->id, //Ajouter le 12/10/2023
                'customer_phone'=>$beneficiaire,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
            ]);

            if($Transaction) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dépot OM initié avec succès',
                    'reference' => $reference,
                    'payToken' => $payToken,
                    'data' => $Transaction,
                    'transId'=>$Transaction->id,
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur inattentue s\' est produite. Veuillez contacter votre support.',
                ], 404);
            }
        }
        return response()->json([
            'success' => false,
            'message' => $responsePayToken->getContent(),
        ], 404);

    }

    function start_Depot_OM($transId, $payToken, $montant, $beneficiaire){

        $ApiOM = new ApiOrangeMoneyController();
        $response = $ApiOM->OM_Cashin_execute($payToken,$beneficiaire, $montant, $transId);

        if ($response->getStatusCode()==200){

            try{
                DB::beginTransaction();
                $data = $response->getContent();
                $resultat = json_decode($data);
                if ($response->status() != 200 || $response->getStatusCode() !=200) {
                    return response()->json([
                        'success' => false,
                        'message' => $resultat->message,
                    ], $response->status());
                }

                //Dépassement de plafond côté Orange Money
                $result = (array)$resultat;
                if (Arr::has($result, 'code')) {
                    $data =json_decode($result["message"]);
                    return response()->json([
                        'success' => false,
                        'message' => $data->message,
                    ], $result["code"]);
                }
                //On Calcule la commission
                $com = new ApiCommissionController();
                $idService = ServiceEnum::DEPOT_OM->value;
                $lacommission =$com->getCommissionByService($idService,$montant);
                if($lacommission->getStatusCode()!=200){
                    return response()->json([
                        'success' => false,
                        'message' => "Impossible de calculer la commission",
                    ], 400);
                }
                $commission=json_decode($lacommission->getContent());

                $commissionFiliale = doubleval($commission->commission_kiaboo);
                $commissionDistributeur=doubleval($commission->commission_distributeur);
                $commissionAgent=doubleval($commission->commission_agent);

                //Par mesure de securité je rappelle les données de l'utilisateur

                $user = User::where('id', Auth::user()->id);
                $balanceBeforeAgent = $user->get()->first()->balance_after;
                $balanceAfterAgent = floatval($balanceBeforeAgent) - floatval($montant);
                //on met à jour la table transaction

                $Transaction = Transaction::where('paytoken',$payToken)->where('service_id',ServiceEnum::DEPOT_OM->value)->update([
                    'reference_partenaire'=>$resultat->data->txnid,
                    'balance_before'=>$balanceBeforeAgent,
                    'balance_after'=>$balanceAfterAgent,
                    'debit'=>$resultat->data->amount,
                    'credit'=>0,
                    'status'=>1, //End successfully
                    'paytoken'=>$resultat->data->payToken,
                    'date_end_trans'=>Carbon::now(),
                    'description'=>$resultat->data->status,
                    'message'=>$resultat->message,
                    'commission'=>$commission->commission_globale,
                    'commission_filiale'=>$commissionFiliale,
                    'commission_agent'=>$commissionAgent,
                    'commission_distributeur'=>$commissionDistributeur,
                ]);

                //on met à jour le solde de l'utilisateur

                //La commmission de l'agent après chaque transaction

                $commission_agent = Transaction::where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");

                $debitAgent = DB::table("users")->where("id", Auth::user()->id)->update([
                    'balance_after'=>$balanceAfterAgent,
                    'balance_before'=>$balanceBeforeAgent,
                    'last_amount'=>$montant,
                    'date_last_transaction'=>Carbon::now(),
                    'user_last_transaction_id'=>Auth::user()->id,
                    'last_service_id'=>ServiceEnum::DEPOT_OM->value,
                    'reference_last_transaction'=>$resultat->data->txnid,
                    'remember_token'=>$resultat->data->payToken,
                    'total_commission'=>$commission_agent,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $resultat->message,
                    'textmessage' => $resultat->message,
                    'reference' => $resultat->data->txnid,
                    'data' => $resultat,
                ], 200);
            }catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
                ], 400);
            }
        }
        return response()->json([
            'success' => false,
            'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste ".$response->getContent(), //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
        ], 400);
    }

    public function depotOM(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:500|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        if($this->checkUserValidity()==false){
            return response()->json([
                'success' => false,
                'message' => "Vous ne disposez pas les droits pour effectuer cette opération",
            ], 400);
        }
        if($this->checkUserBalance($request->amount)==false){
            return response()->json([
                'success' => false,
                'message' => "Votre solde est insuffisant pour effectuer cette transaction",
            ], 400);
        }

        if($this->checkFiveLastTransaction($request->phone, $request->amount, ServiceEnum::DEPOT_OM->value)==false){
            return response()->json([
                'success' => false,
                'message' => 'Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ], 400);
        }

        $initDepot=$this->init_Depot_OM($request->amount,$request->phone);

        if ($initDepot->getStatusCode()==200){
            $dataInitDepot= json_decode($initDepot->getContent());
            $reference = $dataInitDepot->transId;
            $payToken =$dataInitDepot->payToken;
            $montant = $request->amount;
            $beneficiaire = $request->phone;
            $description = $dataInitDepot->data->description;
            $traiteDepot = $this->start_Depot_OM($reference, $payToken, $montant, $beneficiaire);

            if($traiteDepot->getStatusCode()==200){
                $resultat = json_decode($traiteDepot->getContent());
               // dd($resultat->data->data);
                $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();
                $transactionsRefresh = DB::table('transactions')
                    ->join('services', 'transactions.service_id', '=', 'services.id')
                    ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                    ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent','transactions.fees')
                    ->where("fichier","agent")
                    ->where("source",Auth::user()->id)
                    ->where('transactions.status',1)
                    ->orderBy('transactions.date_transaction', 'desc')
                    ->limit(5)
                    ->get();
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => $resultat->message,
                    'textmessage' => $resultat->message,
                    'reference' => $resultat->data->data->txnid,
                    'data' => $resultat,
                    'user'=>$userRefresh,
                    'transactions'=>$transactionsRefresh,
                ], 200);
            }else{
                Log::error([
                    'function' => 'depotOM',
                    'user' => Auth::user()->id,
                    'Montant'=>$montant,
                    'erreur Message' => $traiteDepot->getContent(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' =>"Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                ], 400);
            }
        }
        Log::error([
            'function' => 'depotOM',
            'user' => Auth::user()->id,
            'erreur Message' => $initDepot->getContent(),
        ]);
        return response()->json([
            'success' => false,
            'message' => $initDepot->getContent(),
        ], 404);
    }

    function init_Depot_M2U($montant, $beneficiaire){

        $reference = "DP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();
        $ApiOM = new ApiOrangeMoneyController();
        $responsePayToken = $ApiOM->OM_CashIn_init();

        if ($responsePayToken->getStatusCode()==200){
            $dataPayToken = json_decode($responsePayToken->getContent());
            $payToken = $dataPayToken->data->payToken;
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'paytoken'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>ServiceEnum::DEPOT_OM->value,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>$montant,
                'credit'=>0,
                'status'=>0, //Initiate
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>Auth::user()->id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                //  'agent_id'=>Auth::user()->id, //Ajouter le 12/10/2023
                'customer_phone'=>$beneficiaire,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
            ]);

            if($Transaction) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dépot OM initié avec succès',
                    'reference' => $reference,
                    'payToken' => $payToken,
                    'data' => $Transaction,
                    'transId'=>$Transaction->id,
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur inattentue s\' est produite. Veuillez contacter votre support.',
                ], 404);
            }
        }
        return response()->json([
            'success' => false,
            'message' => $responsePayToken->getContent(),
        ], 404);

    }

    public function depotM2U (Request $request){
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:500|max:500000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        if($this->checkUserValidity()==false){
            return response()->json([
                'success' => false,
                'message' => "Vous ne disposez pas les droits pour effectuer cette opération",
            ], 400);
        }
        if($this->checkUserBalance($request->amount)==false){
            return response()->json([
                'success' => false,
                'message' => "Votre solde est insuffisant pour effectuer cette transaction",
            ], 400);
        }

        if($this->checkFiveLastTransaction($request->phone, $request->amount, ServiceEnum::DEPOT_M2U->value)==false){
            return response()->json([
                'success' => false,
                'message' => 'Une transaction similaire a été faite il y\'a moins de 5 minutes',
            ], 400);
        }

        $initDepot=$this->init_Depot_M2U($request->amount,$request->phone);

        if ($initDepot->getStatusCode()==200){
            $dataInitDepot= json_decode($initDepot->getContent());
            $reference = $dataInitDepot->transId;
            $payToken =$dataInitDepot->payToken;
            $montant = $request->amount;
            $beneficiaire = $request->phone;
            $description = $dataInitDepot->data->description;
            $traiteDepot = $this->start_Depot_OM($reference, $payToken, $montant, $beneficiaire);

            if($traiteDepot->getStatusCode()==200){
                $resultat = json_decode($traiteDepot->getContent());
                // dd($resultat->data->data);
                $userRefresh = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction')->first();
                $transactionsRefresh = DB::table('transactions')
                    ->join('services', 'transactions.service_id', '=', 'services.id')
                    ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                    ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
                    ->where("fichier","agent")
                    ->where("source",Auth::user()->id)
                    ->where('transactions.status',1)
                    ->orderBy('transactions.date_transaction', 'desc')
                    ->limit(5)
                    ->get();
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => "SUCCESSFULL", // $resultat->message,
                    'textmessage' => "Terminé avec succès", // $resultat->message,
                    'reference' => $this->genererChaineAleatoire(10),// $resultat->data->data->txnid,
                    'data' => [],// $resultat,
                    'user'=>$userRefresh,
                    'transactions'=>$transactionsRefresh,
                ], 200);
            }else{
                Log::error([
                    'function' => 'depotOM',
                    'user' => Auth::user()->id,
                    'Montant'=>$montant,
                    'erreur Message' => $traiteDepot->getContent(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' =>"Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
                ], 400);
            }
        }
        Log::error([
            'function' => 'depotOM',
            'user' => Auth::user()->id,
            'erreur Message' => $initDepot->getContent(),
        ]);
        return response()->json([
            'success' => false,
            'message' => $initDepot->getContent(),
        ], 404);
    }
}
