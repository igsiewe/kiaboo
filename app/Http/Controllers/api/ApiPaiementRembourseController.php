<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiPaiementRembourseController extends Controller
{
    public function paiementAgentRembourseFiltre(Request $request){

        $validator = Validator::make($request->all(), [
            'startDate' => 'required|string',
            'endDate' => 'required|string',
        ]);

        $startDate = Carbon::createFromFormat('d/m/Y', $request->startDate)->format('Y-m-d');
        $endDate = Carbon::createFromFormat('d/m/Y', $request->endDate)->format('Y-m-d');

        //Grouper le remboursement des paiements agent par ref_remb_paiement_agent
        $paiements = DB::table('transactions')
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->where('transactions.source', Auth::user()->id)
            ->where("transactions.paiement_agent_rembourse",1)
            ->where("transactions.date_transaction",">=",$startDate.' 00:00:00')
            ->where("transactions.date_transaction","<=",$endDate.' 23:59:59')
            ->select(DB::raw('ref_remb_paiement_agent as reference, DATE_FORMAT(paiement_agent_rembourse_date,"%Y-%m-%d")  as date_remboursement, sum(credit) as montant, sum(fees) as frais'))
            ->where("type_services.id", TypeServiceEnum::PAYMENT->value)
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->where("transactions.ref_remb_paiement_agent","!=",null)
            ->groupBy('transactions.ref_remb_paiement_agent','date_remboursement')
            ->get();


        if($paiements->count() > 0) {
            return response()->json([
                "status" => true,
                "total" => $paiements->sum("montant"),
                "frais" => $paiements->sum("frais"),
                "message"=>$paiements->count()." trouvée(s)",
                "paiements" => $paiements,

            ],200);
        }else{
            return response()->json([
                "status" => false,
                "total"=>0,
                "frais"=>0,
                "message" => "Aucun remboursement trouvé sur la période sélectionnée",
                "paiements"=>[]
            ],404);
        }

    }

    public function paiementAgentRembourse(){

        //Grouper le remboursement des PM agent par ref_remb_paiement_agent
        $paiements = DB::table('transactions')
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->select(DB::raw('ref_remb_paiement_agent as reference, DATE_FORMAT(paiement_agent_rembourse_date,"%Y-%m-%d")  as date_remboursement, sum(credit) as montant, sum(fees) as frais'))
            ->where('transactions.source', Auth::user()->id)
            ->where("transactions.paiement_agent_rembourse",1)
            ->where("type_services.id", TypeServiceEnum::PAYMENT->value)
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->where("transactions.ref_remb_paiement_agent","!=",null)
            ->groupBy('transactions.ref_remb_paiement_agent','date_remboursement')
            ->get();

        if($paiements->count() > 0) {
            return response()->json([
                "status" => true,
                "total" => $paiements->sum("montant"),
                "frais" => $paiements->sum("frais"),
                "message"=>$paiements->count()." trouvée(s)",
                "paiements" => $paiements
            ],200);
        }else{
            return response()->json([
                "status" => false,
                "total"=>0,
                "frais"=>0,
                "message" => "Aucun remboursement n'a encore été éffectué",
                "paiements"=>[]
            ],404);
        }

    }

    public function setRemboursementPaiement(){
        $paiement =Transaction::where('source', Auth::user()->id)->where("paiement_agent_rembourse",0)
            ->join("services","services.id","transactions.service_id")
            ->where('transactions.status',1)->where("transactions.fichier","agent")
            ->where("services.type_service_id",TypeServiceEnum::PAYMENT->value);

        if($paiement->count()>0){
            $montantArembourser=$paiement->sum("credit") - $paiement->sum("fees");
            $totalfeesCollecte=$paiement->sum("fees");
            $date = Carbon::now();
            $chaine = new ApiCheckController();
            $reference = "RP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$chaine->genererChaineAleatoire(1)."".$chaine->GenereRang();
            try{
                DB::beginTransaction();
                $rembourse = $paiement->update([
                    "paiement_agent_rembourse"=>1,
                    "paiement_agent_rembourse_date"=>$date,
                    "ref_remb_paiement_agent"=>$reference,
                ]);

                $balanceAfter=doubleval(Auth::user()->balance_after) + doubleval($montantArembourser);
                $balanceBefore=Auth::user()->balance_after;
                //Creation d'une ligne de débit dans la table transacton (historique de l'operation)
                $Transaction= Transaction::create([
                    'reference'=>$reference,
                    'date_transaction'=>$date,
                    'service_id'=>ServiceEnum::REMBOURSEMENT_PAIEMENT->value,
                    'balance_before'=>$balanceBefore,
                    'balance_after'=>$balanceAfter,
                    'debit'=>$montantArembourser,
                    'credit'=>0,
                    'fees_collecte'=>$totalfeesCollecte,
                    'status'=>1, //Initiate
                    'created_by'=>Auth::user()->id,
                    'created_at'=>$date,
                    'countrie_id'=>Auth::user()->countrie_id,
                    'source'=>Auth::user()->id,
                    'fichier'=>"agent",
                    'updated_by'=>Auth::user()->id,
                    'customer_phone'=>"679962015",
                    'description'=>'SUCCESSFULL',
                    'date_operation'=>date('Y-m-d'),
                    'heure_operation'=>date('H:i:s'),
                    'reference_partenaire'=>$reference,
                    'paytoken'=>$reference,
                    'date_end_trans'=>Carbon::now(),
                    'message'=>$reference,
                    "paiement_agent_rembourse_date"=>$date,
                    "ref_remb_paiement_agent"=>$reference,
                ]);
                //Mise à jour du solde de l'agent
                $updateSoldeCommissionAgent = User::where("id",Auth::user()->id)->update([
                    "total_fees"=>0,
                    "total_paiement"=>0,
                    "balance_after" =>$balanceAfter,
                    "last_amount"=>$montantArembourser,
                    "balance_before"=>$balanceBefore,
                    "date_last_transaction"=>$date,
                    "user_last_transaction_id"=>Auth::user()->id,
                    "reference_last_transaction"=>$reference,
                    "remember_token"=>$reference,
                    "last_service_id"=>ServiceEnum::REMBOURSEMENT_PAIEMENT->value
                ]);

                $paiements = DB::table('transactions')
                    ->join("services","services.id","transactions.service_id")
                    ->join("type_services", "type_services.id","services.type_service_id")
                    ->select(DB::raw('ref_remb_paiement_agent as reference, DATE_FORMAT(paiement_agent_rembourse_date,"%Y-%m-%d")  as date_remboursement, sum(credit) as montant, sum(fees) as frais'))
                    ->where('transactions.source', Auth::user()->id)
                    ->where("transactions.paiement_agent_rembourse",1)
                    ->where("type_services.id", TypeServiceEnum::PAYMENT->value)
                    ->where("transactions.fichier","agent")->where('transactions.status',1)
                    ->groupBy('transactions.ref_remb_paiement_agent','date_remboursement')
                    ->get();

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
                    ->limit(5)
                    ->get();

                $services = Service::all();

                DB::commit();

                return response()->json([
                    "status"=>true,
                    "message"=>"Remboursement effectué avec succès",
                    "total"=>$montantArembourser,
                    "fees"=>$totalfeesCollecte,
                    "paiements"=>$paiements,
                    "user"=>$user,
                    "transactions"=>$transactions,
                    "services"=>$services
                ],200);

            }catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
                ], 500);
            }

        }else{
            return response()->json([
                "status"=>false,
                "message"=>"Aucun  remboursement trouvé à rembourser",
            ],404);
        }

    }
}
