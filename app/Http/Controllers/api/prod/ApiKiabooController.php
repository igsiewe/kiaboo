<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Models\User;
use Illuminate\Http\Request;

class ApiKiabooController extends Controller
{
    public function getAgentInfo($phone){
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
}
