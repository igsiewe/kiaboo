<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
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
            'genre' => 'required',
            'name' => 'required|min:3|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'required|string|unique:prospects',
            'email' => 'required|string',
            'password' => 'required|string|min:12',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 401);
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

//            $checkEmail = prospect::where("email",$request->email)->first();
//            if($checkEmail){
//                DB::rollBack();
//                return response()->json(
//                    [
//                        'success' => false,
//                        'message' => 'Cette adresse email est déjà enregistrée de téléphone existe déjà'
//                    ], 202);
//            }
            if($request->isCodeParrainage == true){
                $parrainageCheck = User::where('codeparrainage', $request->codeParrainage)->where("statut_code_parrainage",1)->where("type_user_id", UserRolesEnum::AGENT->value);
                if($parrainageCheck->count() <= 0){
                    DB::rollBack();
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Ce code de parrainage n'est pas valide. ".$parrainageCheck->count()
                        ], 202);
                }

            }
            $user = new prospect();
            $user->genre =$request->genre;
            $user->name =strtoupper($request->name);
            $user->surname = $request->surname;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->phone_court = $request->phone_court;
            $user->password = bcrypt($request->password);
            $user->quartier_id = $request->quartier;
            $user->type_piece = $request->type_piece;
            $user->optin = $request->optin;
            $user->ville_piece_id =$request->ville_piece;
            $user->numero_piece = strtoupper($request->numero_piece);
            $user->date_validite= $request->date_validite;
            $user->adresse = $request->adresse;
            $user->code_parrainage = strtoupper($request->codeParrainage);
            $user->photo_verso = $request->photo_verso;
            $user->photo_recto = $request->photo_recto;
           // $user->status = "0";
            $result = $user->save();
            if ($result) {
                DB::commit();
                $numero = str_replace("+","",$request->phone);
                $send = new ApiSmsController();
                $message = "Bonjour ".$request->name.",\n\nMerci de vous être inscrit sur notre plateforme. Un commercial vous contactera dans un délai de 48h pour finaliser le processus et activer votre compte.\n\nNous vous remercions de votre confiance et restons à votre disposition pour toute question.\n\nCordialement,\nL'équipe Kiaboo";
                $envoyersMS = $send->SendSMS($numero,utf8_decode($message));
                return response()->json(
                    [
                        'success' => true,
                        'message' => "Votre compte a été créé avec succès. Vous devez confirmer votre adresse email en cliquant sur le lien que nous venons de vous envoyer par email."
                    ], 200);
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
                ], 500);


        }

    }
}
