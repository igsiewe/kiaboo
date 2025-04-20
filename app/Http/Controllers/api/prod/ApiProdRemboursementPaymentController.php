<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Models\remboursementPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiProdRemboursementPaymentController extends Controller
{
    public function getListRemboursement(){
        $agent=User::where('distributeur_id',Auth::user()->distributeur_id)->where('type_user_id',UserRolesEnum::AGENT->value);
        $listAgent=$agent->pluck('id')->toArray();
        if($agent->count()>0){
            $listRemboursement=DB::table('remboursement_payments')->join('users','users.id','=','remboursement_payments.user_id')
            ->select('users.id as idUsers', 'users.name','users.surname', 'users.telephone','remboursement_payments.*')
            ->whereIn('remboursement_payments.user_id',$listAgent)->get();
            if($listRemboursement->count()>0){
                return response()->json([
                    'status'=>200,
                    'data'=>$listRemboursement
                ],200);
            }else{
                return response()->json([
                    'status'=>404,
                    'message'=>'Aucun remboursement trouvé'
                ],404);
            }

        }else{
            return response()->json([
                'status'=>404,
                'message'=>'Aucun agent trouvé'
            ],404);
        }

    }

    public function getListRemboursementSearch(Request $request){

        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response(
                [
                    'success'=>false,
                    'statusCode' => 'ERR-ATTRIBUTES-INVALID',
                    'message' => $validator->errors()->first()
                ], 422);
        }
        $startDate =$request->startDate;
        $endDate =$request->endDate;
        $telephoneAgent= $request->agentId;
        $agent=User::where('distributeur_id',Auth::user()->distributeur_id)->where('type_user_id',UserRolesEnum::AGENT->value);
        $listAgent=$agent->pluck('id')->toArray();
        if($agent->count()>0){
            $listRemboursement=DB::table('remboursement_payments')->join('users','users.id','=','remboursement_payments.user_id')
                ->select('users.id as idUsers', 'users.name','users.surname', 'users.telephone','remboursement_payments.*')
                ->whereIn('remboursement_payments.user_id',$listAgent)
                ->where("remboursement_payments.date_demande",">=",$startDate.' 00:00:00')
                ->where("remboursement_payments.date_demande","<=",$endDate.' 23:59:59');

            if($telephoneAgent !=0 || $telephoneAgent !=null){

                $agentSelect=User::where('telephone',$telephoneAgent)->where('distributeur_id',Auth::user()->distributeur_id)->get();
                if($agentSelect->count() == 0){
                    return response()->json([
                        "success"=> false,
                        "statusCode"=>"ERR-AGENT-NOT-FOUND",
                        "message"=>"Agent ID not found"
                    ], 404);
                }
                $agentId = $agentSelect->first()->id;
                $listRemboursement = $listRemboursement->where("remboursement_payments.user_id",$agentId);
            }

            if($listRemboursement->count()>0){
                return response()->json([
                    'success'=>true,
                    'statusCode' => 'SUCCESS',
                    'message'=> $listRemboursement->count()." transactions found",
                    'listRemboursement'=>$listRemboursement->orderBy('remboursement_payments.date_demande','desc')->get(),
                    'listAgent'=>$agent->orderBy('name')->get(),
                ], 200);
            }else{
                return response()->json([
                    'status'=>404,
                    'message'=>'Aucun remboursement trouvé'
                ],404);
            }

        }else{
            return response()->json([
                'status'=>404,
                'message'=>'Aucun agent trouvé'
            ],404);
        }

    }
}
