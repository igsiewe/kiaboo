<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Traits\PushNotification;


class ApiNotification extends Controller
{
    public function sendNotificationPushFireBaseS($idDevice, $title, $subtitle, $message){
        $curl = curl_init();
      //  print($idDevice." ".$title." ".$subtitle." ".$message);
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{

             "to": '.$idDevice.',
            "notification":{
                "title" : '.$title.',
                "body": '.$message.',
                "sound":"default",
                "subtitle":'.$subtitle.',
                }
            }
            ',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer AAAAxRcLfCY:APA91bGh_xq15JIo4QCUsMdZsCzynZCotx2n0POgNTsevhI5VgCYo1M1OGcpo-rM4qfdFh5wE5zD3PpMEBX6wDIwEpyCyF-ZyYchKjtooJR6CYLYh00-fl0M_kIG9E_1ElDtfPhu1hwf',
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($httpcode != 200) {
                return response()->json([
                    "response"=>$response],400);
            }
            return response()->json([
                "response"=>$response],200);
    }

    public function sendNotificationPushFireBase($idDevice, $title, $subtitle, $message){
        $response = Http::withOptions(['verify' => false,])->withHeaders(
            [
                'Authorization'=>'Bearer AAAAxRcLfCY:APA91bGh_xq15JIo4QCUsMdZsCzynZCotx2n0POgNTsevhI5VgCYo1M1OGcpo-rM4qfdFh5wE5zD3PpMEBX6wDIwEpyCyF-ZyYchKjtooJR6CYLYh00-fl0M_kIG9E_1ElDtfPhu1hwf',
                'Content-Type'=>'application/json'
            ])
            ->Post('https://fcm.googleapis.com/fcm/send', [
                'to'=>$idDevice,
                'notification'=>[
                    'title' => $title,
                    'body' => $message,
                    'sound' => "default",
                    'subtitle' =>$subtitle,
                ]
            ],
            );

        if($response->status()==200){
            $data = json_decode($response->body());
            if($data->success==1){
                return response()->json([
                    "success"=>true,
                    "response"=>$data],200);
            }else{
                return response()->json([
                    "success"=>false,
                    "response"=>$data],400);
            }

        }else{
            return response()->json([
                "success"=>false,
                "response"=>$response->body()],400);
        }
    }

    use PushNotification;

    public function SendPushNotification(Request $request)
    {
        $deviceToken = $request->device_token;
        $title = $request->title;
        $body = $request->body;

        $data =  [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $response = $this->sendNotification($deviceToken, $title, $body, $data);
        if($response->status()==200){
            return response()->json([
                "success"=>true,
                "response"=>$response
            ],200);
        }else {
            return response()->json([
                "success"=>false,
                "response"=>$response
            ],500);
        }

    }

}
