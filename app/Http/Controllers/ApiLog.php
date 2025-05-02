<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class ApiLog extends Controller
{
    public function logError($status = null, $service = null, $requete = null, $response=null, $fonction=null)
    {

        Log::error($service, [
            'status' => $status,
            'function'=> $fonction,
            'request' => $requete,
            'response' => $response,
            'user' => Auth::user()->id,
        ]);
    }

    public function logErrorCallBack($status = null, $service = null, $requete = null, $response=null, $fonction=null, $user=null)
    {

        Log::error($service, [
            'status' => $status,
            'function'=> $fonction,
            'request' => $requete,
            'response' => $response,
            'user' => $user,
        ]);
    }

    public function logInfo($status = null, $service = null, $requete = null, $response=null, $fonction=null )
    {
        Log::info($service, [
            'status' => $status,
            'function'=> $fonction,
            'request' => $requete,
            'response' => $response,
            'user' => Auth::user()->id,
        ]);
    }

    public function logWarning($status = null, $service = null, $requete = null, $response=null, $fonction=null )
    {
        Log::warning($service, [
            'status' => $status,
            'function'=> $fonction,
            'request' => $requete,
            'response' => $response,
            'user' => Auth::user()->id,
        ]);
    }
}
