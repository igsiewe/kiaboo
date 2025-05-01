<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiKiabooController extends Controller
{
    public function getAgentInfo($phone){

        if(Auth::user()->status<>1){
            return response()->json([
                "code" => 403,
                "message" => "Autorisation insuffisante pour cette action",
            ],403);
        }
        if(strlen($phone)<>9){
            return response()->json([
                "code" => 404,
                "message" => "Agent not found",
            ],404);
        }
        $agent = User::where("type_user_id", UserRolesEnum::AGENT->value)->where("telephone", $phone)->where("status",1)
            ->select("name","surname")
            ->first();
        if(!$agent){
            return response()->json([
                "code" => 404,
                "message" => "Agent not found",
            ],404);
        }
        return response()->json([
            "code" => 200,
            "message" => "Agent found",
            "data" => $agent,
        ], 200);
    }


    public function setTransfert(Request $request){
        $validator = Validator::make($request->all(), [
            'compte' => 'required|numeric|digits:9',
            'amount' => 'required|numeric|min:15000|max:500000',
            'deviceId' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $emetteur = User::where("type_user_id", UserRolesEnum::AGENT->value);
        //On se rassure que l'agent qui emet le transfert a un compte actif au moment du transfert
        if($emetteur->first()->status<>1){
            return response()->json([
                "code" => 403,
                "message" => "Autorisation insuffisante pour cette action",
            ],403);
        }
        //On vérifie que le solde de  l'agent qui emet la transaction est suffisant
        if(doubleval($emetteur->balance_after)<doubleval($request->amount)){
            return response()->json([
                "code" => 404,
                "message" => "Votre solde est insuffisant pour cette action",
            ],404);
        }
        //On vérifie que l'agent bénéficiaire est actif
        $beneficiaire = User::where("type_user_id", UserRolesEnum::AGENT->value)->where("telephone", $request->compte)->where("status",1);
        if(!$beneficiaire){
            return response()->json([
                "code" => 404,
                "message" => "Agent not found",
            ],404);
        }
        //On procède maintenant la transaction
        try {
            DB::beginTransaction();
            $variable = new ApiCheckController();
            $reference = "TR".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$variable->genererChaineAleatoire(1)."".$variable->GenereRang();

            //1 - On débite le compte de l'agent qui emet
                   $soldeEmetteur = $emetteur->first()->balance_after;
                   $newSoldeEmetteur = doubleval($soldeEmetteur)-doubleval($request->amount);

                   //Je modifie le solde

                    $commission_agent_emetteur = Transaction::where("status",1)->where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",Auth::user()->id)->sum("commission_agent");

                    $debitAgentEmetteur = DB::table("users")->where("id", Auth::user()->id)->update([
                        'balance_after'=>$newSoldeEmetteur,
                        'balance_before'=>$soldeEmetteur ,
                        'last_amount'=>$request->amount,
                        'date_last_transaction'=>Carbon::now(),
                        'user_last_transaction_id'=>Auth::user()->id,
                        'last_service_id'=>ServiceEnum::TRANSFERT->value,
                        'reference_last_transaction'=>$reference,
                        'remember_token'=>$reference,
                        'total_commission'=>$commission_agent_emetteur,
                    ]);

            //2 - On crée la transaction de débit dans la table transaction

            //3 - On crédite le compte de l'agent bénéficiaire
                    $soldeBeneficiaire = $beneficiaire->first()->balance_after;
                    $newSoldeBeneficiaire = doubleval($soldeBeneficiaire)+doubleval($request->amount);
                    $idBeneficiaire = $beneficiaire->first()->id;
                    //je credite le beneficiaire
                    $commission_agent_Beneficiaire = Transaction::where("status",1)->where("fichier","agent")->where("commission_agent_rembourse",0)->where("source",$idBeneficiaire)->sum("commission_agent");

                    $debitAgentBeneficiaire = DB::table("users")->where("id", $idBeneficiaire)->update([
                        'balance_after'=>$newSoldeBeneficiaire,
                        'balance_before'=>$soldeBeneficiaire ,
                        'last_amount'=>$request->amount,
                        'date_last_transaction'=>Carbon::now(),
                        'user_last_transaction_id'=>$idBeneficiaire,
                        'last_service_id'=>ServiceEnum::TRANSFERT->value,
                        'reference_last_transaction'=>$reference,
                        'remember_token'=>$reference,
                        'total_commission'=>$commission_agent_Beneficiaire,
                    ]);
            //4 - On crée la transaction de crédit dans la table des transactions

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json([
                "code" => $e->getCode(),
                "message" => $e->getMessage(),
            ],$e->getCode());
        }
    }
}
