<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Models\remboursementPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiProdRemboursementPaymentController extends Controller
{
    public function getListRemboursement(){
        $agent=User::where('distributeur_id',Auth::user()->distributeur_id)->where('type_user_id',UserRolesEnum::AGENT->value);
        $listAgent=$agent->pluck('id')->toArray();
        if($agent->count()>0){
            $listRemboursement=DB::table('remboursement_payments')->join('users','users.id','=','remboursement_payments.user_id')
            ->select('users.id as idUsers', 'users.name','users.surname', 'users.telephone','remboursement_payment.*')
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
}
