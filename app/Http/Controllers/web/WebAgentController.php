<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\api\ApiSmsController;
use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Mail\infoRechargeAgent;
use App\Mail\mailCreateAgent;
use App\Models\Distributeur;
use App\Models\User;
use App\Models\Ville;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WebAgentController extends Controller
{

    public function listAgent(){

        $agents = User::where('type_user_id', UserRolesEnum::AGENT->value)->where("status_delete",0);
        $mesdistributeurs = Distributeur::where("status",1);
        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $agents = $agents->where('distributeur_id', auth()->user()->distributeur_id);
            $mesdistributeurs = $mesdistributeurs->where("id",Auth::user()->distributeur_id);
        }
        $mesdistributeurs = $mesdistributeurs->orderBy("name_distributeur","asc")->get();
        $agents = $agents->with('ville','distributeur')->orderBy("name")->orderBy("surname")->get();
        $ville = Ville::where("status",1)->orderBy("name_ville","asc")->get();
        return view('pages.agents.listagent', compact('agents','ville','mesdistributeurs'));
    }

    public function setNewAgent(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'surname' => 'required|string',
            'telephone'=>'required|string|unique:users',
            'email'=>'required|string|unique:users',
            'ville'=>'required|integer',
            'quartier'=>'required|string',
            'adresse'=>'required|string',
            'seuil'=>'required|integer',
            'numcni'=>'required|string',
            'datecni'=>'required',

        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors()->first());
        }

        if(Auth::user()->status == 0){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }
        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }

        $datecni = $request->datecni;
        $now = Carbon::now();
        $checkDateCni = $now->gt($datecni);
        if($checkDateCni==true){
            return redirect()->back()->withErrors('Please check the date of your CNI. It cannot great than today');
        }
        $mesFonctions = new ApiCheckController();
        $newPassword = $mesFonctions->genererChaineAleatoire(8);
        $newAgent = new User();
        $newAgent->name = $request->name;
        $newAgent->surname = $request->surname;
        $newAgent->password = bcrypt($newPassword);
        $newAgent->login ="+237".$request->telephone;
        $newAgent->email_verified_at = Carbon::now();
        $newAgent->created_by = Auth::user()->id;
        $newAgent->telephone = $request->telephone;
        $newAgent->email = $request->email;
        $newAgent->ville_id = $request->ville;
        $newAgent->quartier = $request->quartier;
        $newAgent->adresse = $request->adresse;
        $newAgent->type_user_id = UserRolesEnum::AGENT->value;
        $newAgent->distributeur_id = $request->mondistributeur;
        $newAgent->countrie_id = 1;
        $newAgent->status = 1;
        $newAgent->numcni = $request->numcni;
        $newAgent->datecni = $request->datecni;
        $newAgent->seuilapprovisionnement=$request->seuil;
        $newAgent->moncodeparrainage = "KI".$mesFonctions->genererChaineAleatoire(12);
        $newAgent->application = Auth::user()->application;
        $newAgent->save();

        $sms = new ApiSmsController();
        $tel ="237".$request->telephone;
        $msg = $request->surname.", Votre compte KIABOO a été crée avec succès. Votre mot de passe temporaire est ".$newPassword.". Veuillez le changer dès votre première connexion";
       // $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msg));

        $data = [
            'name'=>strtoupper($request->name) ." ".$request->surname,
            'login'=>$request->telephone,
            'password'=>$newPassword,
        ];

        if(mail::to($request->email)->send(new mailCreateAgent($data))){
            $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msg));
        }
        return redirect()->back()->with('success', 'Agent created successfully');
    }

    public function setUpdateAgent(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'surname' => 'required|string',
            'email'=>'required|string',
            'ville'=>'required|integer',
            'quartier'=>'required|string',
            'adresse'=>'required|string',
           // 'seuil'=>'required|integer',
            'numcni'=>'required|string',
            'datecni'=>'required',
        ]);
        if(Auth::user()->status == 0){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }

        $updateAgent = User::find($id);

        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value ){
            return redirect()->back()->withErrors('You cannot authorize to modify users of this type : AGENT');
        }

        if(Auth::user()->type_user_id == UserRolesEnum::DISTRIBUTEUR->value){
            if($updateAgent->type_user_id != UserRolesEnum::AGENT->value ){
                return redirect()->back()->withErrors('You cannot authorize to modify users of this type');
            }
        }



        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors()->first());
        }

        $datecni = $request->datecni;
        $now = Carbon::now();
        $checkDateCni = $now->gt($datecni);
        if($checkDateCni==true){
            return redirect()->back()->withErrors('Please check the date of your CNI. It cannot great than today');
        }

        if($updateAgent->status== 0){
            return redirect()->back()->withErrors('Please unblock this agent to update it');
        }
        if($updateAgent !=null){
            $updateAgent->name = $request->name;
            $updateAgent->surname = $request->surname;
            $updateAgent->email = $request->email;
            $updateAgent->ville_id = $request->ville;
            $updateAgent->quartier = $request->quartier;
            $updateAgent->adresse = $request->adresse;
            if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value) {
                $updateAgent->distributeur_id = $request->mondistributeur;
            }


            $updateAgent->numcni = $request->numcni;
            $updateAgent->datecni = $request->datecni;
            $updateAgent->updated_at = now();
            $updateAgent->updated_by = auth()->user()->id;
            $updateAgent->save();
            return redirect()->back()->with('success', 'Agent updated successfully');
        }else{
            return redirect()->back()->withErrors('Agent to be updated not found ');
        }

    }

    public function getUpdateUser($id){
        $detailagent  = User::where('id', $id)->with('ville')->first();
        $ville = ville::where("status",1)->orderBy("name_ville","asc")->get();
        $mesdistributeurs = Distributeur::where("status",1);

        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $mesdistributeurs = $mesdistributeurs->where("id",Auth::user()->distributeur_id);
        }
        $mesdistributeurs = $mesdistributeurs->orderBy("name_distributeur","asc")->get();
        dd($mesdistributeurs);
        return view('pages.agents.editagent', compact('detailagent','ville','mesdistributeurs'));
    }

    public function getMesAgents($idDistributeur){
        $listagents = User::where("distributeur_id",$idDistributeur)->where("status_delete",0)->where("type_user_id",UserRolesEnum::AGENT->value)->orderBy("name","asc")->orderBy("surname","asc")->get();
        return view('pages.topupagent.show_agent_distributeur', compact('listagents'));
    }

    public function debloqueAgent($id){
        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value ){
            return redirect()->back()->withErrors('You cannot authorize to modify users of this type : AGENT');
        }
        $user = User::where('id', $id)->where("distributeur_id",Auth::user()->distributeur_id)->first();
        if($user == null || $user->type_user_id != UserRolesEnum::AGENT->value || $user->count()==0){
            return redirect()->back()->withErrors('You cannot authorize to modify users of this type');
        }
        $user->status = 1;
        $user->updated_at = now();
        $user->updated_by = Auth::user()->id;
        $user->save();
//        $agent = User::find($id);
//        $agent->status = 1;
//        $agent->updated_at = now();
//        $agent->updated_by = auth()->user()->id;
//        $agent->save();
        return redirect()->back()->with('success', 'Agent débloqué avec succès');
    }

    public function bloqueAgent($id){

        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value ){
            return redirect()->back()->withErrors('You cannot authorize to modify users of this type : AGENT');
        }
        $user = User::where('id', $id)->where("distributeur_id",Auth::user()->distributeur_id)->first();
        if($user == null || $user->type_user_id != UserRolesEnum::AGENT->value || $user->count()==0){
            return redirect()->back()->withErrors('You cannot authorize to modify users of this type');
        }
        $user->status = 0;
        $user->updated_at = now();
        $user->updated_by = Auth::user()->id;
        $user->save();

//        $agent = User::find($id);
//        $agent->status = 0;
//        $agent->updated_at = now();
//        $agent->updated_by = auth()->user()->id;
//        $agent->save();
        return redirect()->back()->with('success', 'Agent bloqué avec succès');
    }

    public function deleteAgent($id){

        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value ){
            return redirect()->back()->withErrors('You cannot authorize to modify users of this type : AGENT');
        }
        $user = User::where('id', $id)->where("distributeur_id",Auth::user()->distributeur_id)->first();
        if($user == null || $user->type_user_id != UserRolesEnum::AGENT->value || $user->count()==0){
            return redirect()->back()->withErrors('You cannot authorize to modify users of this type');
        }

        //On vérifie si son solde est à 0
        if($user->balance_after != 0){
            return redirect()->back()->withErrors('Impossible de supprimer cet agent car son solde n\'est pas à 0');
        }

        //On vérifie s'il est dans la table transactions

        if($user->transactions->count() > 0){ //On vérifie s'il a des transactions d'approvisionnement
            return redirect()->back()->withErrors('Impossible de supprimer cet agent car il a des transactions');
        }
        $user->delete();
        return redirect()->back()->with('success', 'Agent supprimé avec succès');
    }
}
