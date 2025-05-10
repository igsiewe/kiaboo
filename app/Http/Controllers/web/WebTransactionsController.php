<?php

namespace App\Http\Controllers\web;

use App\Exports\TransactionExport;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\StatusTransEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\ApproDistributeur;
use App\Models\Distributeur;
use App\Models\Partenaire;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use function PHPUnit\Framework\isEmpty;

class WebTransactionsController extends BaseController
{

    public function listTransactions(){
       // phpinfo() ;die;
        $money = "F CFA";
        $listpartenaires = Partenaire::where("id","<>",1)->orderBy("name_partenaire")->get();
        $listservices = Service::where("id","<>",1)->where("display",1)->orderBy("name_service")->get();


        $auth = Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value ? User::where("type_user_id",UserRolesEnum::AGENT->value)->where("distributeur_id",Auth::user()->distributeur_id)->pluck('id') :  User::where("type_user_id",UserRolesEnum::AGENT->value)->pluck('id');

        $query = Transaction::with(['service.typeService','auteur.distributeur'])
            ->where("fichier","agent")
            ->where('status',StatusTransEnum::VALIDATED->value)
            ->whereHas('service',function ($query){
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::PAYMENT->value]);
            })->whereHas('auteur',function ($query) use ($auth){
                $query->whereIn("id",$auth);
            });

        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value)->where('application',1);

        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
           $listagents =   $listagents->where("distributeur_id", Auth::user()->distributeur_id) ;
        }

        $transactions  =$query->orderByDesc('transactions.date_transaction')->limit(100)->get();

        $excelFiltre =0;
        $listagents =    $listagents->orderBy("name")->orderBy("surname")->get();
        return view('pages.transactions.transactions', compact('transactions','money','listagents','listpartenaires','listservices','excelFiltre'));
    }


    public function listTransactionsFiltre(Request $request){

        $request->validate([
            "startDate" =>"required|date",
            "endDate" =>"required|date",
        ]);
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $money = "F CFA";
        $listpartenaires = Partenaire::where("id","<>",1)->orderBy("name_partenaire")->get();
        $listservices = Service::where("id","<>",1)->where("display",1)->orderBy("name_service")->get();

        $result = Carbon::parse($endDate)->gte(Carbon::parse($startDate));
        if ($result==false){
            return redirect()->back()->withInput()->withErrors(['error' => 'La date de début doit être inférieure à la date de fin']);
        }

        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value)->where('application',1);
        $auth = Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value ? User::where("type_user_id",UserRolesEnum::AGENT->value)->where("distributeur_id",Auth::user()->distributeur_id)->pluck('id') :  User::where("type_user_id",UserRolesEnum::AGENT->value)->pluck('id');

         $query = Transaction::with(['service.typeService','auteur.distributeur'])
            ->where("fichier","agent")
            ->where('status',StatusTransEnum::VALIDATED->value)
            ->whereHas('service',function ($query){
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::PAYMENT->value]);
            })->whereHas('auteur',function ($query) use ($auth){
                 $query->whereIn("id",$auth);
             })->whereDate('transactions.created_at', '>=', $startDate)
            ->whereDate('transactions.created_at', '<=', $endDate);


        if($request->agent != null){
           $query = $query->whereHas('auteur',function ($query) use ($request){
                $query->where("id",$request->agent);
            });
        }
        if($request->partenaire != null){
           $query = $query->whereHas('service',function ($query) use ($request){
                $query->where("partenaire_id",$request->partenaire);
            });
        }
        if($request->service != null){
           $query = $query->where("service_id",$request->service);
        }


        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $listagents =   $listagents->where("distributeur_id", Auth::user()->distributeur_id) ;
        }

        $transactions  = $query->orderByDesc('transactions.date_transaction')->get();

        $listagents =$listagents->orderBy("name")->orderBy("surname")->get();


        if($request->partenaire != null){
            $listservices = Service::where("partenaire_id",$request->partenaire)->orderBy("name_service")->get();
        }
        $excelFiltre=1;
        return view('pages.transactions.transactions', compact('transactions','money','listagents','listpartenaires','listservices','excelFiltre'))->with(
            [
                "partenaire" =>$request->partenaire,
                "service" =>$request->service,
                "startDate" =>$startDate,
                "endDate" =>$endDate,
                "agent" =>$request->agent,
            ]
        );

    }
    public function topupAgent(){
        $money = "F CFA";
        $transactions  = DB::table('transactions')
            ->join("users","users.id","transactions.created_by")
            ->join("users as agent","agent.id","transactions.agent_id")
            ->join('distributeurs','distributeurs.id','agent.distributeur_id')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->select('users.distributeur_id','distributeurs.name_distributeur','transactions.agent_id','agent.telephone','agent.login as ref_agent','agent.name as name_agent','agent.surname as surname_agent','transactions.id','transactions.reference','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.balance_before_partenaire','transactions.balance_after_partenaire' ,'services.name_service','services.logo_service','users.type_user_id','users.name as name_operateur', 'users.surname as surname_operateur','transactions.id')
            ->where("transactions.fichier","distributeur")
            ->where('transactions.status',StatusTransEnum::VALIDATED->value)
            ->where("users.type_user_id", UserRolesEnum::DISTRIBUTEUR->value);

        $seuilDepasse = User::where("type_user_id", UserRolesEnum::AGENT->value)->where('application',1);
        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value)->where('application',1);
        $listDistributeurs = Distributeur::all();

        if(Auth::user()->type_user_id ==UserRolesEnum::AGENT->value || Auth::user()->type_user_id ==UserRolesEnum::SDISTRIBUTEUR->value || Auth::user()->type_user_id ==UserRolesEnum::DISTRIBUTEUR->value){
            $transactions  = $transactions->where("users.distributeur_id",Auth::user()->distributeur_id);
            $seuilDepasse = $seuilDepasse->where("distributeur_id",Auth::user()->distributeur_id);
            $listagents = $seuilDepasse->where("type_user_id",UserRolesEnum::AGENT->value);
            $listDistributeurs = Distributeur::where("id", Auth::user()->distributeur_id)->orderBy("name_distributeur")->get();
        }
        $transactions  = $transactions->orderByDesc('transactions.id')->get();
        $seuilDepasse = $seuilDepasse->get();
        $listagents = $listagents->orderBy("name")->orderBy("surname")->get();

        return view('pages.topupagent.topupagent', compact('transactions','money','listagents','seuilDepasse','listDistributeurs'));
    }

    public function getTopUpDetail($id){
        $money = "F CFA";
        $transactions  = DB::table('transactions')
            ->join("users","users.id","transactions.created_by")
            ->join("users as agent","agent.id","transactions.agent_id")
            ->join('distributeurs','distributeurs.id','agent.distributeur_id')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->select('users.distributeur_id','distributeurs.name_distributeur','transactions.agent_id','agent.login as ref_agent','agent.name as name_agent','agent.surname as surname_agent','transactions.id','transactions.reference','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.balance_before_partenaire','transactions.balance_after_partenaire' ,'services.name_service','services.logo_service','users.type_user_id','users.name as name_operateur', 'users.surname as surname_operateur','transactions.description')
            ->where("transactions.fichier","distributeur")
            ->where('transactions.status',StatusTransEnum::VALIDATED->value)
            ->where("users.type_user_id", UserRolesEnum::DISTRIBUTEUR->value)
            ->where("transactions.id",$id)->first();

       // dd();
        return view('pages.topupagent.detail_topup', compact('transactions','money'));
    }
    public function getTopUpAgentFiltre(Request $request){

        $request->validate([
            "startDate" =>"required|date",
            "endDate" =>"required|date",
        ]);
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $money = "F CFA";

        $transactions  = DB::table('transactions')
            ->join("users","users.id","transactions.created_by")
            ->join("users as agent","agent.id","transactions.agent_id")
            ->join('distributeurs','distributeurs.id','agent.distributeur_id')
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->select('transactions.agent_id','users.distributeur_id','distributeurs.name_distributeur','transactions.agent_id','agent.telephone','agent.login as ref_agent','agent.name as name_agent','agent.surname as surname_agent','transactions.id','transactions.reference','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.balance_before_partenaire','transactions.balance_after_partenaire' ,'services.name_service','services.logo_service','users.type_user_id','users.name as name_operateur', 'users.surname as surname_operateur','transactions.description','transactions.id')
            ->where("transactions.fichier","distributeur")
            ->where('transactions.status',StatusTransEnum::VALIDATED->value)
            ->where("users.type_user_id", UserRolesEnum::DISTRIBUTEUR->value)
            ->whereDate('transactions.created_at', '>=', $startDate)
            ->whereDate('transactions.created_at', '<=', $endDate);


        $seuilDepasse = User::where("type_user_id", UserRolesEnum::AGENT->value)->where('application',1);
        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value)->where('application',1);
        $listDistributeurs = Distributeur::all();

        if(Auth::user()->type_user_id ==UserRolesEnum::AGENT->value || Auth::user()->type_user_id ==UserRolesEnum::SDISTRIBUTEUR->value || Auth::user()->type_user_id ==UserRolesEnum::DISTRIBUTEUR->value){
            $seuilDepasse = $seuilDepasse->where("distributeur_id",Auth::user()->distributeur_id);
            $listagents = $listagents->where("distributeur_id",Auth::user()->distributeur_id);
            $listDistributeurs = Distributeur::where("id", Auth::user()->distributeur_id)->orderBy("name_distributeur")->get();
        }
        if($request->distributeur !=null){
            $transactions = $transactions->where("users.distributeur_id",$request->distributeur);
        }
        if($request->agent !=null){
            $transactions = $transactions->where("transactions.agent_id",$request->agent);
        }
        $transactions  = $transactions->orderByDesc('transactions.id')->get();
        $seuilDepasse = $seuilDepasse->get();
        $listagents = $listagents->orderBy("name")->orderBy("surname")->get();

        return view('pages.topupagent.topupagent', compact('transactions','money','listagents','listDistributeurs','seuilDepasse'))->with(
                [
                    "startDate" =>$startDate,
                    "endDate" =>$endDate,
                    "agent" =>$request->agent,
                    "distributeur"=>$request->distributeur,
                ]
            );


    }

    public function getDetailTransaction($id){
        $transactions  = DB::table('transactions')
            ->join("users","users.id","transactions.source")
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('users.login as agent','transactions.id','transactions.reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission','transactions.commission_agent','transactions.commission_distributeur','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.description','transactions.frais')
            ->where("transactions.fichier","agent")
            ->where("transactions.id",$id)
            ->get()->first();
        $money = "F CFA";
        return view('pages.transactions.edit_detail', compact('transactions','money'));
    }

    public function CancelAgentTopUp($id)
    {
        try{
            DB::beginTransaction();
            //Mise à jour de la transaction à annuler
            $transaction = Transaction::find($id);
            $transaction->status_cancel = 1;
            $transaction->date_cancel = date('Y-m-d H:i:s');
            $transaction->cancel_by = Auth::user()->id;
            $transaction->transaction_cancel_id = $transaction->id;
            $transaction->description_cancel = $transaction->reference;
            $transaction->save();

            $Distributeur_id = $transaction->distributeur_id;
            $Distributeur = Distributeur::where('id', $Distributeur_id)->get();
            $balanceDistributeur = $Distributeur->first()->balance_after;
            $newBalanceDistributeur = doubleval($balanceDistributeur)+doubleval($transaction->credit);

            $agent = User::where('id', $transaction->agent_id)->get();
            $balanceAgent = $agent->first()->balance_after;
            $newBalanceAgent = doubleval($balanceAgent)-doubleval($transaction->credit);

            $reference = "AN".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".N".$this->GenereRang();
            $amount= $transaction->credit==0?$transaction->debit:$transaction->credit;
            $Distributeur->first()->update([
                'balance_after'=>$newBalanceDistributeur,
                'balance_before'=>$balanceDistributeur,
                'last_amount'=>$amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>ServiceEnum::ANNULATION->value,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$reference,
            ]);

            $agent->first()->update([
                'balance_after'=>$newBalanceAgent,
                'balance_before'=>$balanceAgent,
                'last_amount'=>$amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>ServiceEnum::ANNULATION->value,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$reference
            ]);


            //Credit table transaction pour le distributeur,
            $debitDistributeur = Transaction::create([
                'reference'=>$reference,
                'reference_partenaire'=>$reference,
                'date_transaction'=>Carbon::now(),
                'service_id'=>ServiceEnum::ANNULATION->value,
                'balance_before'=>$balanceDistributeur,
                'balance_after'=>$newBalanceDistributeur,
                'debit'=>0,
                'credit'=>$transaction->debit,
                'status'=>1,
                'description'=>'SUCCESSFULL',
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$Distributeur_id,
                'disrtibuteur_id'=>$Distributeur_id,
                'agent_id'=>$transaction->agent_id,
                'balance_before_partenaire'=>$balanceAgent,
                'balance_after_partenaire'=>$newBalanceAgent,
                'fichier'=>"distributeur",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$reference,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>$agent->first()->telephone,
                'date_end_trans'=>Carbon::now(),
                'moyen_payment'=>"Cash",
            ]);

            //Debit table transaction pour l'agent
            $creditSousDistributeur = Transaction::create([
                'reference'=>$reference,
                'reference_partenaire'=>$reference,
                'date_transaction'=>Carbon::now(),
                'service_id'=>ServiceEnum::ANNULATION->value,
                'status'=>1,
                'description'=>'SUCCESSFULL',
                'balance_before'=>$balanceAgent,
                'balance_after'=>$newBalanceAgent,
                'debit'=>$transaction->debit,
                'credit'=>0,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$transaction->agent_id,
                'agent_id'=>$transaction->agent_id,
                'disrtibuteur_id'=>$Distributeur_id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$reference,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>Auth::user()->telephone,
                'balance_before_partenaire'=>$balanceDistributeur,
                'balance_after_partenaire'=>$newBalanceDistributeur,
                'date_end_trans'=>Carbon::now(),
                'moyen_payment'=>"Cash",
            ]);
            DB::commit();
            return redirect()->back()->with('success', 'Transaction annulée avec succès');
        }catch (\Exception $e){
            DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }


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




}
