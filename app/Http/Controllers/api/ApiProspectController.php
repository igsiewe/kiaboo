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
            'phone' => 'required|string|unique:users',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:12',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 404);
        }


        try {
            DB::beginTransaction();
            $statutcodeparraisange = false;
            $checkUser = prospect::where('phone', $request->phone)->first();
            if($checkUser){
                DB::rollBack();
                return $this->errorResponse('Ce numéro de téléphone existe déjà', 202);
            }
            if($request->isCodeParrainage == true){

                $parrainageCheck = User::where('codeparrainage', $request->codeParrainage)->first();
                if(!$parrainageCheck){
                    DB::rollBack();
                    return $this->errorResponse("Ce code de parrainage n'est pas valide", 404);
                }
                $statutcodeparraisange = true;
            }
            $user = new User();
            $newPassword = $this->genererChaineAleatoire(8);

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
            $user->status = 0;


            $result = $user->save();
            if ($result) {
                DB::commit();
                return $this->sendResponse($user, 'Votre compte a été créé avec succès. Vous devez confirmer votre adresse email en cliquant sur le lien que nous venons de vous envoyer par email.');
            } else {
                DB::rollBack();
                return $this->errorResponse('User don\t added', 403);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }

    }
}
