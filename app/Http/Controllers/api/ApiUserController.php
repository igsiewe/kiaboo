<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiUserController extends BaseController
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

    public function checkNumero(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $user = User::where('telephone', $request->numero)->where("view",1)->first();
        if ($user) {
            return response()->json(['success' => true, 'message' => 'Ce numéro existe déjà'], 200);
        } else {
            $otpcode = rand(100000, 999999);
            return response()->json(['success' => false, 'message' => 'Numero non trouvé','otpcode'=>$otpcode], 404);
        }
    }
}
