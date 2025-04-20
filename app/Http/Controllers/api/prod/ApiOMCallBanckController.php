<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiOMCallBanckController extends Controller
{

       public function OMCallBackResponse(Request $request)
       {
           header("Content-Type: application/json");
           $OMcallBackResponse = file_get_contents('php://input');
           $data = json_decode($OMcallBackResponse);
           $element = json_decode($OMcallBackResponse, associative: true);
           Log::info([
               "responseOMCallBack" => $data,
           ]);
       }


}
