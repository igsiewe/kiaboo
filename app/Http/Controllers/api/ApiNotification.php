<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Google\Auth\ApplicationDefaultCredentials;


class ApiNotification extends Controller
{
    public function sendNotification($token, $title, $body, $data = [])
    {
        $fcmurl = 'https://fcm.googleapis.com/v1/projects/kiaboo-8bc01/messages:send';

        $notification = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
            'token' => $token,
        ];

        try{
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])->post($fcmurl, [
                'message' => $notification,
            ]);
            return $response->json();

        }catch (\Exception $e) {
            Log::error('Error sending push notification to '.$token.' : '. $e->getMessage());
            return false;
        }
    }

    private function getAccessToken(){
        $keyPath = config('services.firebase.key_path');
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyPath);
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = ApplicationDefaultCredentials::getCredentials($scopes);
        $token = $credentials->fetchAuthToken();
        dd($token['access_token']);
        return $token['access_token'] ?? null;
    }

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
        return response()->json([
            "success"=>true,
            "response"=>$response
        ]);
    }

}
