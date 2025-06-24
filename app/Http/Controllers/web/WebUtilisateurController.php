<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\api\ApiCheckController;
use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Mail\UserNotificationMail;
use App\Models\Distributeur;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\Ville;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WebUtilisateurController extends Controller
{
    public function listUtilisateurs(){

        $utilisateurs = User::where('type_user_id', "!=",UserRolesEnum::AGENT->value)
           // ->where("id", "!=", Auth::user()->id)
            ->whereNotIn("id", array(Auth::user()->id,7,8))
            ->where("status_delete",0)->where("view",1);
        $mesdistributeurs=Distributeur::all()->sortBy("name_distributeur");
        $typeUtilisateurs = TypeUser::where("status",1)->orderBy("name_type_user")->where('id','<>',UserRolesEnum::AGENT->value)->where('id','<>',UserRolesEnum::SUPADMIN->value)->get();

        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $utilisateurs = User::where('type_user_id',UserRolesEnum::AGENT->value)->where('distributeur_id', Auth::user()->distributeur_id)->where('id','<>',Auth::user()->id)->where("status_delete",0)->where("view",1);
            $mesdistributeurs=Distributeur::where('id',Auth::user()->distributeur_id)->orderBy("name_distributeur");
            $typeUtilisateurs = TypeUser::where("status",1)->where("id",UserRolesEnum::DISTRIBUTEUR->value)->orderBy("name_type_user")->get();
        }
       // dd($utilisateurs->get());
        $utilisateurs = $utilisateurs->with('ville','distributeur','typeUser')->orderBy("name")->orderBy("surname")->get();
        $ville = Ville::where("status",1)->orderBy("name_ville","asc")->get();
        return view('pages.utilisateurs.listutilisateur', compact('utilisateurs','ville','mesdistributeurs','typeUtilisateurs'));
    }

    public function setNewUtilisateur(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'surname' => 'required|string',
            'telephone'=>'required|unique:users|string',
            'email'=>'required|unique:users|string',
            'ville'=>'required|integer',
            'quartier'=>'required|string',
            'adresse'=>'required|string',
            'typeuser'=>'required|integer',
            'numcni'=>'required|unique:users|string',
            'datecni'=>'required',

        ]);
        if(Auth::user()->status == 0){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }

        if(Auth::user()->type_user_id == UserRolesEnum::DISTRIBUTEUR->value){ //Les distributeur ne peuvent créer que des distributeurs
            if($request->typeuser !=UserRolesEnum::DISTRIBUTEUR->value){
                return redirect()->back()->withErrors('You cannot authorize to perform this operation');
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
        $MesFonctions = new ApiCheckController();
        $newPassword = $MesFonctions->genererChaineAleatoire(12);
        $newUtilisateur = new User();
        $newUtilisateur->name = $request->name;
        $newUtilisateur->surname = $request->surname;
        $newUtilisateur->password = bcrypt($newPassword);
        $newUtilisateur->login = $request->email;
        $newUtilisateur->email_verified_at = Carbon::now();
        $newUtilisateur->created_by = Auth::user()->id;
        $newUtilisateur->telephone = $request->telephone;
        $newUtilisateur->email = $request->email;
        $newUtilisateur->ville_id = $request->ville;
        $newUtilisateur->quartier = $request->quartier;
        $newUtilisateur->adresse = $request->adresse;
        $newUtilisateur->type_user_id = $request->typeuser;
        $newUtilisateur->distributeur_id = $request->mondistributeur;
        $newUtilisateur->countrie_id = 1;
        $newUtilisateur->status = 1;
        $newUtilisateur->numcni = $request->numcni;
        $newUtilisateur->datecni = $request->datecni;
        $newUtilisateur->moncodeparrainage = "KIAB".$MesFonctions->genererChaineAleatoire(8);
        $newUtilisateur->status_delete =0;
        if($request->typeuser==UserRolesEnum::DISTRIBUTEUR->value){
            $newUtilisateur->application =2;
        }else{
            $newUtilisateur->application =1;
        }

        $newUtilisateur->save();

        $data=[
            'name'=>$request->surname." ".mb_strtoupper($request->name),
            'login'=>$request->email,
            'password'=>$newPassword,
        ];
        //Envoi du mail
        Mail::to($request->email)
            ->send(new UserNotificationMail($data));
        //
        return redirect()->back()->with('success', 'Agent created successfully');
    }

    public function setUpdateUtilisateur(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'surname' => 'required|string',
            'email'=>'required|string',
            'ville'=>'required|integer',
            'quartier'=>'required|string',
            'adresse'=>'required|string',
         //   'seuil'=>'required|integer',
            'numcni'=>'required|string',
            'datecni'=>'required',
        ]);

        if (!Auth::user()->hasRole(['super-admin', 'Administrateur'])){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation....');
        }

        if(Auth::user()->status == 0){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation.');
        }
//        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value){
//            return redirect()->back()->withErrors('You cannot authorize to modify users of this type');
//        }

//        if(Auth::user()->type_user_id == UserRolesEnum::DISTRIBUTEUR->value){ //Les distributeur ne peuvent créer que des distributeurs
//            if($request->typeuser !=UserRolesEnum::DISTRIBUTEUR->value){
//                return redirect()->back()->withErrors('You cannot authorize to perform this operation');
//            }
//        }

        if(Auth::user()->id==$id){
            return redirect()->back()->withErrors('You cannot update your own account');
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
        $updateAgent = User::find($id);

        if($updateAgent->status== 0){
            return redirect()->back()->withErrors('Please unblock this agent to update it');
        }

        if($updateAgent->status_delete== 1){
            return redirect()->back()->withErrors('You cannot update this agent...');
        }
        if($updateAgent->id== Auth::user()->id){
            return redirect()->back()->withErrors('You cannot update yourself');
        }
        if($updateAgent !=null){
            $updateAgent->name = $request->name;
            $updateAgent->surname = $request->surname;
            $updateAgent->email = $request->email;
            $updateAgent->ville_id = $request->ville;
            $updateAgent->quartier = $request->quartier;
            $updateAgent->adresse = $request->adresse;
         //   $updateAgent->seuilapprovisionnement=$request->seuil;
            $updateAgent->distributeur_id = $request->mondistributeur;
            $updateAgent->type_user_id = $request->typeuser;
            $updateAgent->numcni = $request->numcni;
            $updateAgent->datecni = $request->datecni;
            $updateAgent->updated_at = now();
            $updateAgent->updated_by = auth()->user()->id;
            $updateAgent->save();
            return redirect()->back()->with('success', 'Utilisateur updated successfully');
        }else{
            return redirect()->back()->withErrors('User to be updated not found ');
        }

    }

    public function getUpdateUtilisateur($id){
        $detailutilisateur  = User::where('id', $id)->where("status_delete",0)->with('ville')->where("view",1)->first();
        $ville = ville::where("status",1)->orderBy("name_ville","asc")->get();
        $mesdistributeurs = Distributeur::where("status",1);
        $typeUtilisateurs = TypeUser::where("status",1)->where("id","<>",1)->orderBy("name_type_user")->get();
        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $mesdistributeurs = $mesdistributeurs->where("id",Auth::user()->distributeur_id);
            $typeUtilisateurs = TypeUser::where("status",1)->where("id",UserRolesEnum::DISTRIBUTEUR->value)->orderBy("name_type_user")->get();
        }
        $mesdistributeurs = $mesdistributeurs->orderBy("name_distributeur","asc")->get();

        return view('pages.utilisateurs.editutilisateur', compact('detailutilisateur','ville','mesdistributeurs','typeUtilisateurs'));
    }

    public function getinfoUserSelect($id){
        $user  = User::where('id', $id)->where("status_delete",0)->where("view",1)->first();
        return view('pages.utilisateurs.initpassword', compact('user'));
    }

    public function debloqueUtilisateur($id){
        if(Auth::user()->id==$id){
            return redirect()->back()->withErrors('You cannot update your own account');
        }
        $utilisateur = User::find($id);
        if($utilisateur->status_delete ==1){
            return redirect()->back()->withErrors('User not found');
        }
        $utilisateur->status = 1;
        $utilisateur->updated_at = now();
        $utilisateur->updated_by = auth()->user()->id;
        $utilisateur->save();
        return redirect()->back()->with('success', 'Utilisateur débloqué avec succès');
    }

    public function bloqueUtilisateur($id){
        if(Auth::user()->id==$id){
            return redirect()->back()->withErrors('You cannot update your own account');
        }
        $utilisateur = User::find($id);
        if($utilisateur->status_delete ==1){
            return redirect()->back()->withErrors('User not found');
        }
        $utilisateur->status = 0;
        $utilisateur->updated_at = now();
        $utilisateur->updated_by = auth()->user()->id;
        $utilisateur->save();
        return redirect()->back()->with('success', 'Utilisateur bloqué avec succès');
    }

    public function deleteUtilisateur($id){
        if(Auth::user()->id==$id){
            return redirect()->back()->withErrors('You cannot update your own account');
        }
        $utilisateur = User::find($id);

        //On vérifie si son solde est à 0
        if($utilisateur->balance_after != 0){
            return redirect()->back()->withErrors('Impossible de supprimer cet utilisateur car son solde n\'est pas à 0');
        }

        //On vérifie s'il est dans la table transactions

        if($utilisateur->transactions->count() > 0){ //On vérifie s'il a des transactions d'approvisionnement
            return redirect()->back()->withErrors('Impossible de supprimer cet utilisateur');
        }
        $utilisateur->update([
            "status_delete"=>1,
            "deleted_at"=>Carbon::now(),
            "deleted_by"=>Auth::user()->id,
            "status"=>0,
            "updated_by"=>Auth::user()->id,
            "updated_at"=>Carbon::now(),
        ]);
        return redirect()->back()->with('success', 'Utilisateur supprimé avec succès');
    }
}
