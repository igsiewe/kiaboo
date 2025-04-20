<?php

use Exception;
use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

trait PushNotification
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

       }catch (Exception $e){
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
        return $token['access_token'] ?? null;
    }
}