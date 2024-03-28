<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ApiProdYooMeeController extends Controller
{
    public function YooMee_getUserInfo($customerPhone){


        if ($customerPhone==null || $customerPhone=="") {
            return response()->json([
                'success' => false,
                'message' => "Please provide a customer phone number",
            ], 400);
        }

        $url = "http://quality-env.yoomeemoney.cm:8080/api/users?keywords=$customerPhone&roles=member&statuses=active";
        $response = Http::withOptions(['verify' => false,])->withBasicAuth("kiaboo2024", "Ki@boo2024")
            ->Get($url);

        $customerPhone="";
        $customerName="";
        if($response->body()==null || $response->body()=="[]"){ //On teste si l'utilisateur existe
            return response()->json([
                'status' => 'echec',
                'customerName' => $customerName,
                'customerPhone' => $customerPhone,
                'message'=>'Ce numéro de client n\'existe pas. Veuillez vérifier le numéro de téléphone',
                'response'=>$response,
            ],404);
        }
        if($response->status()==200){

            $element = json_decode($response, associative: true);
            dd($element, Arr::has($element[0], "name"));
            if(!Arr::has($element, "name")){ //On teste si l'utilisateur existe
                return response()->json([
                    'status' => 'echec',
                    'customerName' => $customerName,
                    'customerPhone' => $customerPhone,
                    'message'=>'Ce numéro de client n\'existe pas',
                    'response'=>$response,
                ],404);
            }
            $json = json_decode($response, false);
            $data=collect($json)->first();
            $customerName = $data->name;
            $customerPhone = $data->phone;
            $accountNumber = $data->accountNumber; //accountNumber;
            if($customerName==null && $accountNumber==null){
                return response()->json([
                    'status' => 'echec',
                    'customerName' => $customerName,
                    'customerPhone' => $customerPhone,
                    'message'=>'Ce numéro de client n\'existe pas',
                ],404);
            }

            return response()->json([
                'status' => 'success',
                'customerName' => $customerName,
                'customerPhone' => $customerPhone,
                'accountAccount' => $accountNumber,
                'message'=>'Client trouvé',
            ],200);
        }else{
            Log::error([
                'code'=> $response->status(),
                'function' => "YooMee_getUserInfo",
                'response'=>$response->body(),
                'user' => Auth::user()->id,
            ]);
            return response()->json(
                [
                    'status'=>$response->status(),
                    'message'=>$response->body(),
                ],$response->status()
            );
        }
    }
}
