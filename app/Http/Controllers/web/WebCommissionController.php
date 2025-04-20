<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Commission;
use App\Models\Distributeur;
use App\Models\Partenaire;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebCommissionController extends Controller
{
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

    public function listAgentCommissions(){
        $money = "F CFA";
        $commissions_agent  = Transaction::where("service_id",ServiceEnum::PAIEMENT_COMMISSION->value)
            ->join("users","users.id","transactions.created_by")
            ->join("distributeurs","distributeurs.id","users.distributeur_id");

        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value);
        $listDistributeurs = Distributeur::all()->sortBy("name_distributeur");

        $commission_non_percue = DB::table('transactions')
            ->join("users","users.id","transactions.source")
            ->join("distributeurs","distributeurs.id","users.distributeur_id")
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->select(DB::raw('kb_users.telephone as reference, kb_users.name, kb_users.surname, (sum(kb_transactions.credit)+sum(kb_transactions.debit)) as volume, sum(commission_agent) as commission'))
            ->where('users.type_user_id', UserRolesEnum::AGENT->value)
            ->where("transactions.commission_agent_rembourse",0)
            ->whereNotIn("type_services.id", [TypeServiceEnum::APPROVISIONNEMENT->value,TypeServiceEnum::REMBOURSEMENT])
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->groupBy('telephone','users.name','users.surname')
            ->get();

        if(Auth::user()->type_user_id ==UserRolesEnum::DISTRIBUTEUR->value){
            $commissions_agent  = $commissions_agent
                ->where("users.distributeur_id",Auth::user()->distributeur_id);
            $listagents = $listagents->where("distributeur_id",Auth::user()->distributeur_id);
            $listDistributeurs = Distributeur::where("id", Auth::user()->distributeur_id)->orderBy("name_distributeur")->get();

            $commission_non_percue = DB::table('transactions')
                ->join("users","users.id","transactions.source")
                ->join("distributeurs","distributeurs.id","users.distributeur_id")
                ->join("services","services.id","transactions.service_id")
                ->join("type_services", "type_services.id","services.type_service_id")
                ->select(DB::raw('kb_users.telephone as reference, kb_users.name, kb_users.surname, (sum(kb_transactions.credit)+sum(kb_transactions.debit)) as volume, sum(commission_agent) as commission'))
                ->where('users.type_user_id', UserRolesEnum::AGENT->value)
                ->where("users.distributeur_id",Auth::user()->distributeur_id)
                ->where("transactions.commission_agent_rembourse",0)
                ->whereNotIn("type_services.id", [TypeServiceEnum::APPROVISIONNEMENT->value,TypeServiceEnum::REMBOURSEMENT])
                ->where("transactions.fichier","agent")->where('transactions.status',1)
                ->groupBy('telephone','users.name','users.surname')
                ->get();
        }

        $commissions_agent  = $commissions_agent
        ->select("transactions.id","transactions.reference","transactions.ref_remb_com_agent","transactions.date_transaction","transactions.credit","transactions.balance_before","transactions.balance_after","users.name","users.surname","users.telephone","distributeurs.name_distributeur")
        ->orderByDesc('transactions.date_transaction')->get();

        $listagents = $listagents->orderBy("name")->orderBy("surname")->get();
        return view('pages.commission_agent.comagent', compact('commissions_agent','money','listagents','listDistributeurs','commission_non_percue'));

    }

    public function listAgentCommissionsSearch(Request $request){

        $request->validate([
            "startDate" =>"required|date",
            "endDate" =>"required|date",
        ]);
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $money = "F CFA";
        $commissions_agent  = Transaction::where("service_id",ServiceEnum::PAIEMENT_COMMISSION->value)
            ->join("users","users.id","transactions.created_by")
            ->join("distributeurs","distributeurs.id","users.distributeur_id")
            ->whereDate('transactions.created_at', '>=', $startDate)
            ->whereDate('transactions.created_at', '<=', $endDate);

        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value);
        $listDistributeurs = Distributeur::all();
        $commission_non_percue = DB::table('transactions')
            ->join("users","users.id","transactions.source")
            ->join("distributeurs","distributeurs.id","users.distributeur_id")
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->select(DB::raw('kb_users.telephone as reference, kb_users.name, kb_users.surname, (sum(kb_transactions.credit)+sum(kb_transactions.debit)) as volume, sum(commission_agent) as commission'))
            ->where('users.type_user_id', UserRolesEnum::AGENT->value)
            ->where("transactions.commission_agent_rembourse",0)
            ->whereNotIn("type_services.id", [TypeServiceEnum::APPROVISIONNEMENT->value,TypeServiceEnum::REMBOURSEMENT])
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->groupBy('telephone','users.name','users.surname')
            ->get();
        if(Auth::user()->type_user_id ==UserRolesEnum::DISTRIBUTEUR->value){
            $listagents = $listagents
            ->where("distributeur_id",Auth::user()->distributeur_id);

            $commission_non_percue = DB::table('transactions')
                ->join("users","users.id","transactions.source")
                ->join("distributeurs","distributeurs.id","users.distributeur_id")
                ->join("services","services.id","transactions.service_id")
                ->join("type_services", "type_services.id","services.type_service_id")
                ->select(DB::raw('kb_users.telephone as reference, kb_users.name, kb_users.surname, (sum(kb_transactions.credit)+sum(kb_transactions.debit)) as volume, sum(commission_agent) as commission'))
                ->where('users.type_user_id', UserRolesEnum::AGENT->value)
                ->where("users.distributeur_id",Auth::user()->distributeur_id)
                ->where("transactions.commission_agent_rembourse",0)
                ->whereNotIn("type_services.id", [TypeServiceEnum::APPROVISIONNEMENT->value,TypeServiceEnum::REMBOURSEMENT])
                ->where("transactions.fichier","agent")->where('transactions.status',1)
                ->groupBy('telephone','users.name','users.surname')
                ->get();

            $listDistributeurs = Distributeur::where("id", Auth::user()->distributeur_id)->orderBy("name_distributeur")->get();
        }
        if($request->distributeur !=null){
            $commissions_agent = $commissions_agent->where("users.distributeur_id",$request->distributeur);
        }
        if($request->agent !=null){
            $commissions_agent = $commissions_agent->where("transactions.created_by",$request->agent);
        }

        $commissions_agent = $commissions_agent
          ->select("transactions.id","transactions.reference","transactions.ref_remb_com_agent", "transactions.date_transaction", "transactions.credit", "transactions.balance_before", "transactions.balance_after", "users.name", "users.surname", "users.telephone","distributeurs.name_distributeur")
          ->orderByDesc('transactions.date_transaction')->get();

        $listagents = $listagents->orderBy("name")->orderBy("surname")->get();
        return view('pages.commission_agent.comagent', compact('commissions_agent','money','listagents','listDistributeurs','commission_non_percue'))->with(
            [
                "startDate" =>$startDate,
                "endDate" =>$endDate,
                "agent" =>$request->agent,
                "distributeur"=>$request->distributeur,
            ]
        );
    }

    public function getDetailCommission($reference){

        $money = "F CFA";
        $detailOperation  = Transaction::join("users","users.id","transactions.created_by")
            ->join("distributeurs","distributeurs.id","users.distributeur_id")
            ->join("services","services.id","transactions.service_id")
            ->select("transactions.id","transactions.reference","transactions.reference_partenaire","transactions.ref_remb_com_agent","transactions.credit","transactions.debit","transactions.commission_agent","transactions.commission_distributeur","transactions.customer_phone","services.name_service","transactions.date_transaction","users.name","users.surname","users.telephone")
             ->where("ref_remb_com_agent",$reference)
            ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
            ->orderByDesc('transactions.created_at')->get();

        return view('pages.commission_agent.list_com_detail', compact('detailOperation','money'));

    }

    public function listDistributeurCommissions(){
        $money = "F CFA";
        $commissions_distributeur  = Transaction::where("service_id",ServiceEnum::PAIEMENT_COMMISSION_DISTRIBUTEUR->value)
            ->join("users","users.id","transactions.created_by")
            ->join("distributeurs","distributeurs.id","users.distributeur_id");

        $commission = DB::table('transactions')
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->select(DB::raw('date_operation,count(kb_transactions.id) as number, sum(commission_distributeur) as commission'))
            ->where("transactions.commission_distributeur_rembourse",0)
            ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->groupBy('transactions.date_operation')
            ->orderByDesc('transactions.date_operation')
            ->get();

        $listDistributeurs = Distributeur::all()->sortBy("name_distributeur");

        if(Auth::user()->type_user_id ==UserRolesEnum::DISTRIBUTEUR->value){
            $commissions_distributeur  = $commissions_distributeur
                ->where("users.distributeur_id",Auth::user()->distributeur_id);

            $commission = DB::table('transactions')
                ->join("services","services.id","transactions.service_id")
                ->join("users","users.id","transactions.source")
                ->join("type_services", "type_services.id","services.type_service_id")
                ->select(DB::raw('date_operation, count(kb_transactions.id) as number, sum(commission_distributeur) as commission'))
                ->where("transactions.commission_distributeur_rembourse",0)
                ->where("users.distributeur_id",Auth::user()->distributeur_id)
                ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
                ->where("transactions.fichier","agent")->where('transactions.status',1)
                ->groupBy('transactions.date_operation')
                ->orderByDesc('transactions.date_operation')
                ->get();

           $listDistributeurs = Distributeur::where("id", Auth::user()->distributeur_id)->orderBy("name_distributeur")->get();
        }

        $commissions_distributeur  = $commissions_distributeur
            ->select("transactions.id","transactions.reference","transactions.ref_remb_com_agent","transactions.date_transaction","transactions.credit","transactions.balance_before","transactions.balance_after","users.name","users.surname","users.telephone","distributeurs.name_distributeur")
            ->orderByDesc('transactions.date_transaction')->get();

       return view('pages.commission_distributeur.comdistributeur', compact('commissions_distributeur','money','listDistributeurs','commission'));

    }

    public function listDistributeurCommissionsSearch(Request $request){

        $request->validate([
            "startDate" =>"required|date",
            "endDate" =>"required|date",
        ]);
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $money = "F CFA";
        $commissions_distributeur  = Transaction::where("service_id",ServiceEnum::PAIEMENT_COMMISSION_DISTRIBUTEUR->value)
            ->join("users","users.id","transactions.created_by")
            ->join("distributeurs","distributeurs.id","users.distributeur_id")
            ->whereDate('transactions.created_at', '>=', $startDate)
            ->whereDate('transactions.created_at', '<=', $endDate);

        $commission = DB::table('transactions')
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->select(DB::raw('date_operation,count(kb_transactions.id) as number, sum(commission_distributeur) as commission'))
            ->where("transactions.commission_distributeur_rembourse",0)
            ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->groupBy('transactions.date_operation')
            ->orderByDesc('transactions.date_operation')
            ->get();

        $listDistributeurs = Distributeur::all()->sortBy("name_distributeur");

        if(Auth::user()->type_user_id ==UserRolesEnum::DISTRIBUTEUR->value){

            $listDistributeurs = Distributeur::where("id", Auth::user()->distributeur_id)->orderBy("name_distributeur")->get();
            $commission = DB::table('transactions')
                ->join("services","services.id","transactions.service_id")
                ->join("users","users.id","transactions.source")
                ->join("type_services", "type_services.id","services.type_service_id")
                ->select(DB::raw('date_operation, count(kb_transactions.id) as number, sum(commission_distributeur) as commission'))
                ->where("transactions.commission_distributeur_rembourse",0)
                ->where("users.distributeur_id",Auth::user()->distributeur_id)
                ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
                ->where("transactions.fichier","agent")->where('transactions.status',1)
                ->groupBy('transactions.date_operation')
                ->orderByDesc('transactions.date_operation')
                ->get();
        }

        if($request->distributeur !=null){
           // if(Auth::user()->type_user_id ==UserRolesEnum::DISTRIBUTEUR->value) {
                $commissions_distributeur = $commissions_distributeur->where("users.distributeur_id", $request->distributeur);
          //  }
        }
      //  dd($commissions_distributeur->get());
        $commissions_distributeur = $commissions_distributeur
            ->select("transactions.id","transactions.reference","transactions.ref_remb_com_distributeur", "transactions.date_transaction", "transactions.credit", "transactions.balance_before", "transactions.balance_after", "users.name", "users.surname", "users.telephone","distributeurs.name_distributeur")
            ->orderByDesc('transactions.date_transaction')->get();

        return view('pages.commission_distributeur.comdistributeur', compact('commissions_distributeur','money','listDistributeurs','commission'))->with(
            [
                "startDate" =>$startDate,
                "endDate" =>$endDate,
                "distributeur"=>$request->distributeur,
            ]
        );
    }

    public function setRemboursementCommissionDistributeur(){

        if(Auth::user()->type_user_id!=UserRolesEnum::DISTRIBUTEUR->value){
            return redirect()->back()->with('error', 'Votre profil ne vous permet pas d\'effectuer cette opération');
        }

        $commission = DB::table('transactions')
           // ->join("users","users.id","transactions.created_by")
            ->join("services","services.id","transactions.service_id")
            ->join("users","users.id","transactions.source")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->where("users.distributeur_id",Auth::user()->distributeur_id)
            ->where("transactions.commission_distributeur_rembourse",0)
            ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
            ->where("transactions.fichier","agent")->where('transactions.status',1);

        $montantCommission=0;
        if($commission->count()>0){
            $montantCommission = $commission->sum("commission_distributeur");
            $date = Carbon::now();
            $reference = "RB".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();
            try{
                DB::beginTransaction();
                $rembourse = $commission->update([
                    "commission_distributeur_rembourse"=>1,
                    "commission_distributeur_rembourse_date"=>$date,
                    "ref_remb_com_distributeur"=>$reference,
                ]);
                $distributeur = Distributeur::where("id",Auth::user()->distributeur_id)->get();
                $balanceAfter=doubleval($distributeur->first()->balance_after) + doubleval($montantCommission);
                $balanceBefore=$distributeur->first()->balance_after;
                //Creation d'une ligne de credit dans la table transacton (historique de l'operation)
                $Transaction= Transaction::create([
                    'reference'=>$reference,
                    'date_transaction'=>$date,
                    'service_id'=>ServiceEnum::PAIEMENT_COMMISSION_DISTRIBUTEUR->value,
                    'balance_before'=>$balanceBefore,
                    'balance_after'=>$balanceAfter,
                    'debit'=>0,
                    'credit'=>$montantCommission,
                    'status'=>1, //Initiate
                    'created_by'=>Auth::user()->id,
                    'created_at'=>$date,
                    'countrie_id'=>Auth::user()->countrie_id,
                    'source'=>Auth::user()->distributeur_id,
                    'fichier'=>"distributeur",
                    'updated_by'=>Auth::user()->id,
                    'customer_phone'=>"679962015",
                    'description'=>'SUCCESSFULL',
                    'date_operation'=>date('Y-m-d'),
                    'heure_operation'=>date('H:i:s'),
                    'reference_partenaire'=>$reference,
                    'paytoken'=>$reference,
                    'date_end_trans'=>Carbon::now(),
                    'message'=>$reference,
                    'commission'=>0,
                    'commission_filiale'=>0,
                    'commission_agent'=>0,
                    'commission_distributeur'=>0,
                    "commission_distributeur_rembourse"=>1,
                    "commission_distributeur_rembourse_date"=>$date,
                    // "ref_remb_com_agent"=>$reference,
                ]);
                //Mise à jour du solde du distributeur
                $updateSoldeCommissionDistributeur = Distributeur::where("id",Auth::user()->distributeur_id)->update([
                   // "total_commission"=>0,
                    "balance_after" =>$balanceAfter,
                    "last_amount"=>$montantCommission,
                    "balance_before"=>$balanceBefore,
                    "date_last_transaction"=>$date,
                    "user_last_transaction_id"=>Auth::user()->id,
                    "reference_last_transaction"=>$reference,
                  //  "remember_token"=>$reference,
                    "last_service_id"=>ServiceEnum::PAIEMENT_COMMISSION_DISTRIBUTEUR->value

                ]);

                DB::commit();

                return redirect()->back()->with('success', 'Remboursement effectuée avec succès');

            }catch (\Exception $e) {
                DB::rollback();
                Log::error([
                    'erreur Message' => $e->getMessage(),
                    'user' => Auth::user()->id,
                    'service' => 'Remboursement commission distributeur',

                ]);

                return redirect()->back()->with('error', 'Une erreur inattendue s\' est produite. Veuillez contacter votre support');
            }

        }else{
            return redirect()->back()->with('error', 'Une commission trouvée que nous puissions vous rembourser');
        }

    }

    public function getDetailCommissionDistributeur($reference){

        $money = "F CFA";
        $detailOperation  = Transaction::join("users","users.id","transactions.created_by")
            ->join("distributeurs","distributeurs.id","users.distributeur_id")
            ->join("services","services.id","transactions.service_id")
            ->select("transactions.id","transactions.reference","transactions.reference_partenaire","transactions.ref_remb_com_distributeur","transactions.credit","transactions.debit","transactions.commission_agent","transactions.commission_distributeur","transactions.customer_phone","services.name_service","transactions.date_transaction","users.name","users.surname","users.telephone","distributeurs.name_distributeur")
            ->where("ref_remb_com_distributeur",$reference)
            ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value])
            ->orderByDesc('transactions.created_at')->get();

        return view('pages.commission_distributeur.list_com_detail', compact('detailOperation','money'));

    }

    public function grilleCommission(){
        $money = "F CFA";
        $partenaires = Partenaire::all()->where('id','<>',1)->where("status",1)->sortBy("name_partenaire");
        $grilleCommission = Commission::join("services","services.id","commissions.service_id")
            ->join("partenaires","partenaires.id","services.partenaire_id")
            ->select("commissions.id","commissions.borne_min","commissions.borne_max","commissions.taux","commissions.amount","commissions.type_commission","commissions.status","commissions.created_at","commissions.updated_at","services.name_service","services.type_service_id","services.partenaire_id","partenaires.name_partenaire")
            ->orderBy("partenaires.name_partenaire")
            ->orderBy("services.name_service")
            ->orderBy("commissions.borne_min")
            ->get();
        return view("pages.grille_commission.grillecommission", compact('grilleCommission','money','partenaires'));
    }

    public function deleteCommission($id){
        $commission = Commission::where('id',$id);
        if($commission){
            $commission->update([
                "status"=>0,
                "updated_by"=>Auth::user()->id,
                "updated_at"=>Carbon::now()
            ]);
            return redirect()->back()->with('success', 'Plan commissionnement supprimée avec succès');
        }else{
            return redirect()->back()->with('error', 'Commission non trouvée');
        }
    }

    public function addNewCommission(Request $request){
        $request->validate([
            "partenaire" =>"required|integer",
            "service" =>"required|integer",
            "typecommission" =>"required|string",
            "bornemin" =>"required|numeric",
            "bornemax" =>"required|numeric|gt:bornemin",
            "commission" =>"required|numeric",
        ]);
        //On vérifie si l'interval de commission n'existe pas déjà
        $commissionExist = Commission::where("service_id",$request->service)
            ->where("borne_min",'<=',$request->bornemin)
            ->where("borne_max",'>=',$request->bornemax)
            ->where("status",1)
            ->first();

       // dd($commissionExist, $request->all());
        if($commissionExist){
            return redirect()->back()->with('error', 'Une commission existe déjà pour cette borne');
        }
        $addcommission = Commission::create([
            "partenaire_id"=>$request->partenaire,
            "service_id"=>$request->service,
            "distributeur_id"=>1,//"distributeur_id"=>Auth::user()->distributeur_id,
            "type_commission"=>$request->typecommission,
            "borne_min"=>$request->bornemin,
            "borne_max"=>$request->bornemax,
            "taux"=>$request->typecommission=="taux"?$request->commission:0,
            "amount"=>$request->typecommission=="borne"?$request->commission:0,
            "part_agent"=>0.65,
            "part_distributeur"=>0,
            "part_kiaboo"=>0.35,
            "status"=>1,
            "created_by"=>Auth::user()->id,
            "created_at"=>Carbon::now(),
            "updated_by"=>Auth::user()->id,
            "updated_at"=>Carbon::now()
        ]);
        if($addcommission){
            return redirect()->back()->with('success', 'Plan commissionnement ajouté avec succès');
        }else{
            return redirect()->back()->with('error', 'Une erreur inattendue s\' est produite. Veuillez contacter votre support');
        }
    }
}
