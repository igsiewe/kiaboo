<?php

namespace App\Http\Controllers;

use Exception;
use http\Env\Response;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class BaseController extends Controller
{
       /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($data, $message)
    {
        $response = [
            'status' => 200,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response);
    }

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function errorResponse($message, $statusCode)
    {
        $response = [
           // 'success' => false,
            'status' => $statusCode,
            'message' => $message,
         //   "data" => $data,
        ];

        return response()->json($response,$statusCode);
    }

    public function notFound($message = null)
    {
        return response()->json([
            'message' => $message ?? 'not found',
        ], 404);
    }

    public function badRequest($message = null)
    {
        return response()->json([
            'message' => $message ?? 'invalid request',
        ], 400);
    }

    public function error($message = null, Exception $ex = null)
    {
        return response()->json([
            'message' => $message ?? 'an error occurred',
            'exception' => $ex,
        ], 500);
    }

    public function created($data = null, $msg = null)
    {
        $response = [
            'success' => true,
            'message' => $msg,
            'data' => $data,
        ];

        return response()->json($response, 201);
    }

    public function created_Message($message = null)
    {
        return response()->json([
            'message' => $message ?? 'successful created',
        ], 201);
    }

    public function updated($msg = null)
    {
        $response = [
            'success' => true,
            'message' => $msg,
        ];

        return response()->json($response, 200);
    }

    public function ok($message = null)
    {
        return response()->json([
            'message' => $message ?? 'successful',
        ], 200);
    }

    public function delete($message = null)
    {
        return response()->json([
            'message' => $message ?? 'successful',
        ], 200);
    }


    public function respondWithTokenRecrutement($token, $user = null, $agents = null, $villes = null)
    {
        return response()->json([
            'token_type' => 'bearer',
            'scope'=> "am_application_scope default",
            'access_token' => $token,
            'user' => $user,
            'agents'=> $agents,
            'villes'=> $villes,

        ], 200);
    }

    public function respondWithToken($token, $user = null, $partenaires = null, $transactions = null, $services = null, $version=null, $urlApplication=null, $notification=null, $monnaies = null, $questions = null, $configurations = null)
    {
        return response()->json([
            'token_type' => 'bearer',
            'scope'=> "am_application_scope default",
            'access_token' => $token,
            'version'=>$version,
            'urlApplication'=>$urlApplication,
            'user' => $user,
            'partenaires'=> $partenaires,
            'transactions'=> $transactions,
            'services'=> $services,
            'notification'=>$notification,
            'monnaies'=>$monnaies,
            'questions'=> $questions,
            'configurations'=> $configurations,

        ], 200);
    }
    public function respondWithTokenSwagger($token, $user = null,$delay=null)
    {
        return response()->json([
            'success' => true,
            'statusCode'=> "LOGIN-SUCCESS",
            'message'=> "successful login user",
            'access_token' => $token,
                'expired_at'=>$delay,
                'user'=>[
                    'name'=>$user->name,
                    'surname'=>$user->surname,
                ],
        ], 200);
    }
}
