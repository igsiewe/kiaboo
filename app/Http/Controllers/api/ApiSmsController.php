<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SoapClient;

class ApiSmsController extends Controller
{


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    function SendSMSCameroun ($recipient,$content){
        $params['userLogoin']="KIABOO";
        $params['userpassword']="K!@b012345";
        $params['destinataires']=$recipient;
        $params['messages']=$content;
        $params['numeroCourt']="Kiaboo";

        $client = new SoapClient("http://lmtgoldsms.dyndns.org:8282/Managesms-war/ServiceSMS?WSDL");
        $result = $client->envoyerSMS($params);
        $ResultQuote = $result->return;
        return $ResultQuote;
    }
    public function SendCMR($recipient, $content)
    {
        $client = new \GuzzleHttp\Client();
        $url = "https://app.lmtgroup.com/bulksms/api/v1/push";

        $myBody['api_key'] = "7pceN52XATpxQfA";
        $myBody['password'] = "K!@b012345";
        $myBody['sender'] = "KIABOO";
        $myBody['phone'] = $recipient;
        $myBody['message'] = $content;
        $myBody['flag'] = "long_sms";

        $request = $client->post($url,
            [
                'form_params'=> $myBody
            ]
        );
        $response = $request->getBody()->getContents();

        return $response;
    }


    public function SendSMS($tel, $msg)//index($tel,$msg)
    {
//        $rules = [
//            'tel'=>'required|string',
//            'msg' =>'required|string',
//            'pays' =>'required|string|max:4',
//        ];
//
//        $validator = Validator::make($request->all(), $rules);
//        if ($validator->fails()){
//            return response()->json([
//                "Code"=>404,
//                "Message" => $validator->getMessageBag(),
//                "Success"=>false,
//            ], 404);
//        }
        $response = $this->SendCMR($tel, utf8_encode($msg));
        $retourApiSms = array();
        $code = Str::contains($response,"success");
        $messageAPI = Str::contains($response,"message sent");

        $retourApiSms["Code"] = $code === true ?200:400;
        $retourApiSms["Message"] = $messageAPI === true ? "message envoyé" : "une erreur a été détectée";
        $retourApiSms["Success"] = $code; // true si la reponse est  200 false au cas contraire....

        return  json_encode($retourApiSms);
    }
}
