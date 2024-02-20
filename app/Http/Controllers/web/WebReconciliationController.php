<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Enums\StatusTransEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Partenaire;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebReconciliationController extends Controller
{
    public function transactionEnattente(){
        // phpinfo() ;die;
        $money = "F CFA";
        $listpartenaires = Partenaire::where("id","<>",1)->orderBy("name_partenaire")->get();
        $listservices = Service::where("id","<>",1)->orderBy("name_service")->get();


        $auth = Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value ? User::where("type_user_id",UserRolesEnum::AGENT->value)->where("distributeur_id",Auth::user()->distributeur_id)->pluck('id') :  User::where("type_user_id",UserRolesEnum::AGENT->value)->pluck('id');

        $query = Transaction::with(['service.typeService','auteur.distributeur'])
            ->where("fichier","agent")
            ->where('status',StatusTransEnum::PENDING) //status = 2 Transactions en attente
            ->whereHas('service',function ($query){
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value]);
            })->whereHas('auteur',function ($query) use ($auth){
                $query->whereIn("id",$auth);
            });

        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value);

        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $listagents =   $listagents->where("distributeur_id", Auth::user()->distributeur_id) ;
        }

        $transactions  =$query->orderByDesc('transactions.date_transaction')->limit(100)->get();
        $listagents =    $listagents->orderBy("name")->orderBy("surname")->get();

        return view('pages.reconciliation.trans_trans_attente.transactions', compact('transactions','money','listagents','listpartenaires','listservices'));
    }

    public function transactionEnattenteSearch(Request $request){

        $request->validate([
            "startDate" =>"required|date",
            "endDate" =>"required|date",
        ]);
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $money = "F CFA";
        $listpartenaires = Partenaire::where("id","<>",1)->orderBy("name_partenaire")->get();
        $listservices = Service::where("id","<>",1)->orderBy("name_service")->get();

        $result = Carbon::parse($endDate)->gte(Carbon::parse($startDate));
        if ($result==false){
            return redirect()->back()->withInput()->withErrors(['error' => 'La date de début doit être inférieure à la date de fin']);
        }

        $listagents = User::where("type_user_id",UserRolesEnum::AGENT->value);
        $auth = Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value ? User::where("type_user_id",UserRolesEnum::AGENT->value)->where("distributeur_id",Auth::user()->distributeur_id)->pluck('id') :  User::where("type_user_id",UserRolesEnum::AGENT->value)->pluck('id');

        $query = Transaction::with(['service.typeService','auteur.distributeur'])
            ->where("fichier","agent")
            ->where('status',StatusTransEnum::VALIDATED->value)
            ->whereHas('service',function ($query){
                $query->whereIn("type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value]);
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

        return view('pages.reconciliation.trans_attente.transactions', compact('transactions','money','listagents','listpartenaires','listservices'))->with(
            [
                "partenaire" =>$request->partenaire,
                "service" =>$request->service,
                "startDate" =>$startDate,
                "endDate" =>$endDate,
                "agent" =>$request->agent,
            ]
        );

    }
}
