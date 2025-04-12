<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\prospect;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiProspectController extends Controller
{
    public function setNewProspect(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'required|string|unique:prospects',
            'email' => 'required|string|email|unique:prospects',
            'password' => 'required|string|min:12',
        ]);
        if ($validator->fails()) {

            return response(
                [
                    'success' => false,
                    'statusCode' => 'ERR-ATTRIBUTES-INVALID',
                    'message' => $validator->errors()->all()

                ], 422);
        }


        try {
            DB::beginTransaction();
            $checkUser = prospect::where('phone', $request->phone)->first();
            if($checkUser){
                DB::rollBack();

                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Ce numéro de téléphone existe déjà'
                    ], 202);
            }
            if($request->isCodeParrainage == true){

                $parrainageCheck = User::where('codeparrainage', $request->codeParrainage)->first();
                if(!$parrainageCheck){
                    DB::rollBack();
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Ce code de parrainage n'est pas valide"
                        ], 403);
                }

            }
            $user = new prospect();
            $user->name =strtoupper($request->name);
            $user->surname = strtoupper($request->surname);
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->phone_court = $request->phone_court;
            $user->password = bcrypt($request->password);
            $user->quartier_id = $request->quartier;
            $user->type_piece =$request->type_piece;
            $user->optin = $request->optin;
            $user->ville_piece_id =$request->ville_piece;
            $user->adresse = $request->adresse;
            $user->code_parrainage = $request->code_parrainage;
            $user->photo_verso = $request->photo_verso;
            $user->photo_recto = $request->photo_recto;
           // $user->status = "0";
            $result = $user->save();
            if ($result) {
                DB::commit();
                return response()->json(
                    [
                        'success' => true,
                        'message' => "Votre compte a été créé avec succès. Vous devez confirmer votre adresse email en cliquant sur le lien que nous venons de vous envoyer par email."
                    ], 202);
                } else {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'User don\t added'
                    ], 403);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return response()->json(
                [
                    'success' => false,
                    'message' => $message
                ], $e->getCode());


        }

    }
}
