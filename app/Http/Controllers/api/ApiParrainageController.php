<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Parrainage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiParrainageController extends Controller
{
   public function setNewParrainage(Request $request){
       $validator = Validator::make($request->all(), [
           'name' => 'required|min:3|string|max:255',
           'surname' => 'required|string|max:255',
           'phone' => 'required|string|max:255',

       ]);
       //On se rassure que l'utilisateur qui veut parrainer est actif
       if(Auth::user()->status == 0){
           return $this->errorResponse('You cannot authorize to perform this operation', 404);
       }
       if ($validator->fails()) {
           return response(['status'=>422,'message' => $validator->errors()->first()], 422);
       }
       //On se rassure qu'il n'a pas déjà parrainer quelqu'un ayant le même numéro de téléphone
         $parraine = Parrainage::where('phone',$request->phone)->where('user_id', Auth::user()->id)->get();
         if($parraine->count() > 0){
             return response(['status' => 422, 'message' => "Vous avez déjà parrainé quelqu'un ayant le même numéro de téléphone"], 422);
         }
         $addParraine = Parrainage::create([
           'name'=>strtoupper($request->name),
           'surname'=>$request->surname,
           'phone'=>$request->phone,
           'status'=>0,
           'user_id'=>Auth::user()->id,
           'codeparrainage'=>Auth::user()->moncodeparrainage,
       ]);

       if($addParraine){
           //Déclenchez envoi de SMS
           return response(['status'=>200,'message' => "Enregistré avec succès"], 200);
       }
       return response(['status'=>404,'message' => "Une error s'est produite"], 404);
   }
}
