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
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value]);
            })->whereHas('auteur',function ($query) use ($auth){
                $query->whereIn("id",$auth);
            });

    // dd($query->get()->take(5));

        $volumeofTransaction=0;
        $currentBalance=0;
        $revenue=0;
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


            $volumeofTransaction = $query->get()->sum(function(Transaction $item){
              return $item->debit + $item->credit;
            });


            $revenue = $query->get()->sum("commission_distributeur");
            $lastTransactions = $query->orderBy('transactions.date_transaction', 'desc')->limit(5)->get();


            $bestAgents = $query->get()->map(function (Transaction $transaction){
                return [
                    "volume" => $transaction->debit + $transaction->credit,
                    "commission" => $transaction->commission_distributeur,
                    "name" => $transaction->auteur->name,
                    "surname" => $transaction->auteur->surname,
                    "login" => $transaction->auteur->login,
                    "id" => $transaction->auteur->id,
                    "name_distributeur" => $transaction->auteur()->first()->distributeur->name_distributeur,
                ];
            })->groupBy("name","surname","login","id","name_distributeur")->map(function ($item){
                return [
                    "volume" => $item->sum("volume"),
                    "commission" => $item->sum("commission"),
                    "name" => $item->first()["name"],
                    "surname" => $item->first()["surname"],
                    "login" => $item->first()["login"],
                    "id" => $item->first()["id"],
                    "name_distributeur" => $item->first()["name_distributeur"],
                ];
            })->sortByDesc("volume")->take(5)->values();


/*            $resultGraphe= $query->selectRaw('year(kb_transactions.created_at) year, month(kb_transactions.created_at) month, sum(kb_transactions.debit) debit, sum(kb_transactions.credit) credit')
                ->whereYear('transactions.created_at','=',Carbon::now()->year)
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc');*/

            $resultGraphe = $query->get()->map(function (Transaction $transaction){
                return [
                    "year" => Carbon::parse($transaction->created_at)->year,
                    "month" => Carbon::parse($transaction->created_at)->month,
                    "debit" => $transaction->debit,
                    "credit" => $transaction->credit,
                ];
            });

           $mesdata=($resultGraphe->map(function (array $item)
            {
                return [
                    "month" =>$item["month"],
                    "envoi" =>$item["debit"],
                    "retrait" =>$item["credit"],
                ];
            }));

            $envoi = collect();
            for($i = 1;$i <= 12; $i++)
            {
                $data  = $mesdata->where("month",$i);

                if ($data == null || $data->isEmpty())
                {
                    $envoi->add(0);
                }
                else
                {
                    $envoi->add($data->first()["envoi"]);
                }
            }

            $mesdata=($resultGraphe->map(function (array $item)
            {
                return [
                    "month" =>$item["month"],
                    "envoi" =>$item["debit"],
                    "retrait" =>$item["credit"],
                ];
            }));
            $retrait = collect();
            for($i = 1;$i <= 12; $i++)
            {
                $data  = $mesdata->where("month",$i);

                if ($data == null || $data->isEmpty())
                {
                    $retrait->add(0);
                }
                else
                {
                    $retrait->add($data->first()["retrait"]);
                }
            }

        }
        return view('pages.dashboard.dashboard', compact('volumeofTransaction','currentBalance','revenue','agent','money','lastTransactions','bestAgents','envoi','retrait'));

    }
}
