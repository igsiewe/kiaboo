<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Enums\StatusTransEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRoles;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\Memo;
use App\Models\Transaction;
use App\Models\TypeUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WebDashBoardController extends Controller
{
    public function dashboard(){

        $auth = Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value ? User::where("type_user_id",UserRolesEnum::AGENT->value)->where("distributeur_id",Auth::user()->distributeur_id)->pluck('id') :  User::where("type_user_id",UserRolesEnum::AGENT->value)->pluck('id');

        $query = Transaction::with(['service.typeService','auteur.distributeur'])
            ->where("fichier","agent")
            ->where('status',StatusTransEnum::VALIDATED->value)
            ->whereHas('service',function ($query){
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::PAYMENT->value]);
            })->whereHas('auteur',function ($query) use ($auth){
                $query->whereIn("id",$auth);
            });

        $volumeofTransaction=0;
        $currentBalance=0;
        $revenue=0;
        $frais=0;
        $agent=0;
        $money = "F CFA";
        $lastTransactions=[];
        $bestAgents = [];
        $envoi=[];
        $retrait=[];

        if($query){
            $currentBalance = Distributeur::all()->sum("balance_after");
            $agent = User::where("type_user_id",UserRolesEnum::AGENT->value)->count();

            if(Auth::user()->type_user_id == UserRolesEnum::DISTRIBUTEUR->value){
                $currentBalance = Distributeur::where("id", Auth::user()->distributeur_id)->first()->balance_after;
                $agent = User::where("distributeur_id",Auth::user()->distributeur_id)->where("type_user_id",UserRolesEnum::AGENT->value)->count();
            }

            $volumeofTransaction = $query->sum("debit")+$query->sum("credit");

            $revenue = $query->get()->sum("commission");
            $frais = $query->get()->sum("fees");

            $lastTransactions = $query->orderBy('transactions.date_transaction', 'desc')->limit(5)->get();

            $transAgent = DB::table("transactions")
                ->join("users", "users.id","transactions.source")
                ->join("distributeurs","distributeurs.id","users.distributeur_id")
                ->join("services","services.id","transactions.service_id")
                ->join("type_services","type_services.id","services.type_service_id")
                ->where("transactions.fichier","agent")
                ->where('transactions.status',StatusTransEnum::VALIDATED->value)
                ->whereIn("type_services.id", [TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::PAYMENT->value]);

            $bestAgents =$transAgent->selectRaw('kb_users.id, kb_users.login, kb_users.name, kb_users.surname, kb_distributeurs.name_distributeur, sum(kb_transactions.debit+kb_transactions.credit) as volume, sum(kb_transactions.commission) as commission, sum(kb_transactions.fees) as frais')
                ->groupBy('users.name', 'users.surname','users.login','users.id')
                ->orderBy('volume', 'desc')
                ->limit(5)
                ->get();

            if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
                $transAgent = $transAgent ->where("users.distributeur_id", Auth::user()->distributeur_id);

                $bestAgents =$transAgent->selectRaw('kb_users.id, kb_users.login, kb_users.name, kb_users.surname, kb_distributeurs.name_distributeur, sum(kb_transactions.debit+kb_transactions.credit) as volume, sum(kb_transactions.commission_agent) as commission, sum(kb_transactions.fees) as frais')
                    ->groupBy('users.name', 'users.surname','users.login','users.id')
                    ->orderBy('volume', 'desc')
                    ->limit(5)
                    ->get();

                $revenue = $transAgent->get()->sum("commission");
                $frais = $transAgent->get()->sum("frais");

            }



            //---------------

/*            $resultGraphe= $transAgent
                ->whereYear('transactions.date_transaction', Carbon::now()->year)
                ->selectRaw('month(kb_transactions.date_transaction) as mois, sum(kb_transactions.debit) as envoi, sum(kb_transactions.credit) as retrait')
                ->groupBy('mois')
                ->orderBy('mois', 'desc')->get()->toArray();*/

            $resultGraphe= $transAgent
                ->whereYear('transactions.date_transaction', Carbon::now()->year)
                ->selectRaw("month(kb_transactions.date_transaction) as mois, SUM(CASE WHEN kb_type_services.name_type_service = 'Payment' THEN credit ELSE 0 END) AS Paiement,
                    SUM(CASE WHEN kb_type_services.name_type_service = 'Retrait' THEN credit ELSE 0 END) AS Retrait,
                    SUM(CASE WHEN kb_type_services.name_type_service = 'Envoi' THEN debit ELSE 0 END) AS Envoi")
                ->groupBy('mois')
                ->orderBy('mois', 'desc')->get()->toArray();



            $envoi = collect();
            $retrait = collect();
            $paiement = collect();

            for ($i = 1; $i <= 12; $i++) {

               $data = collect($resultGraphe)->where("mois",$i);
               $envoi->add($data->sum(function ($op) use ($envoi){
                     return $op->Envoi;
                }));

                $retrait->add($data->sum(function ($op) use ($retrait){
                    return $op->Retrait;
                }));
                $paiement->add($data->sum(function ($op) use ($paiement){
                    return $op->Paiement;
                }));
            }

          //  dd ($envoi, $retrait, $paiement);

        }

        return view('pages.dashboard.dashboard', compact('volumeofTransaction','currentBalance','revenue','agent','money','lastTransactions','bestAgents','envoi','retrait','paiement','frais'));

    }
}
