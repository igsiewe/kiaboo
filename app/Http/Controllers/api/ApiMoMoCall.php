<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiMoMoCall extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

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

    public function DepotMOMO($beneficiaire, $montant, $reference, $description)
    {

        return response()->json([
            'code' => 200,
            'status' => "Succès",
            'message' => "Requête traitée avec succès",
            'reference' => rand(100000000000, 199999999999),
            'payToken' => "P".rand(10000000000, 19999999999),
        ]);

        $ApiOM = new ApiOrangeMoneyController();
        //On genere le token Access
        $response = $ApiOM->OM_GetTokenAccess();
        $dataAcessToken = json_decode($response->getContent());
        $codeAccessToken = $dataAcessToken->code;

        if ($codeAccessToken != 200) {
            return response()->json([
                'code' => $codeAccessToken,
                'error' => '1.error = '.$codeAccessToken,
            ]);
        }
        $accessToken = $dataAcessToken->accessToken;

        //On genere le PayToken du depot
        $responsePayToken = $ApiOM->OM_Cashin_init($accessToken);
        $dataPayToken = json_decode($responsePayToken->getContent());
        $codePayToken = $dataAcessToken->code;
        if ($codePayToken != 200) {
            return response()->json([
                'code' => $codePayToken,
                'error' => '2.error = '.$codePayToken,
            ]);
        }
        $payToken = $dataPayToken->payToken;

        //On execute le OM_Cashin_execute de dépôt

        $resposeCashin = $ApiOM->OM_Cashin_execute($accessToken, $payToken, $beneficiaire, $montant, $reference, $description);
        $dataCashIn = json_decode($resposeCashin->getContent());
        $codeDepotPay = $dataCashIn->code;

        if ($codeDepotPay != 200) {
            return response()->json([
                'code' => $codeDepotPay,
                'error' => $dataCashIn->message,
                'message' => $dataCashIn->message,
                'status' => $dataCashIn->status,
            ]);
        }

        return response()->json([
            'code' => $dataCashIn->code,
            'status' => $dataCashIn->status,
            'message' => $dataCashIn->message,
            'reference' => $dataCashIn->reference,
            'payToken' => $dataCashIn->payToken,
        ]);
    }

}
