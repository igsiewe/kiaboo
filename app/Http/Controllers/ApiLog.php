<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class ApiLog extends Controller
{
    public function logError($status = null, $service = null, $requete = null, $response=null)
    {

        Log::error('API Error', [
            'status' => $status,
            'function'=> $service,
            'request' => $requete,
            'response' => $response,
            'user' => Auth::user()->id,
        ]);
    }

    public function logInfo($status = null, $service = null, $requete = null, $response=null)
    {
        Log::info('API Info', [
            'status' => $status,
            'function'=> $service,
            'request' => $requete,
            'response' => $response,
            'user' => Auth::user()->id,
        ]);
    }

    public function logWarning($status = null, $service = null, $requete = null, $response=null)
    {
        Log::warning('API Warning', [
            'status' => $status,
            'function'=> $service,
            'request' => $requete,
            'response' => $response,
            'user' => Auth::user()->id,
        ]);
    }
}
