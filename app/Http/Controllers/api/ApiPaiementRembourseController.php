<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Enums\TypeServiceEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                "message" => "Aucune paiement trouvé",
                "paiements"=>[]
            ],404);
        }

    }

    public function paiementAgentRembourse(){

        //Grouper le remboursement des PM agent par ref_remb_paiement_agent
        $paiements = DB::table('transactions')
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->select(DB::raw('ref_remb_paiement_agent as reference, DATE_FORMAT(paiement_agent_rembourse_date,"%Y-%m-%d")  as date_remboursement, sum(credit) as montant, sum(commission_agent) as commission'))
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
                "message" => "Aucune remboursement n'a encore été éffectué",
                "paiements"=>[]
            ],404);
        }

    }

}
