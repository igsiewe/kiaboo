<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\api\ApiSmsController;
use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\prospect;
use App\Models\quartier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WebProspectontroller extends Controller
{
   public function getListProspect(){
       if(Auth::user()->type_user_id !=UserRolesEnum::SUPADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::ADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::BACKOFFICE->value){
           return redirect()->back()->withInput()->withErrors(['error' => "Vous n'êtes pas autorisés à acceder à cette page"]);
       }
       $listProspect = prospect::with( 'quartier', 'ville_piece')->orderBy('id', 'DESC')->get();
       return view('pages.prospect.listprospect', compact('listProspect'));

   }

   public function valideProspect($id){
       if(Auth::user()->type_user_id !=UserRolesEnum::SUPADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::ADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::BACKOFFICE->value){
           return redirect()->back()->withInput()->withErrors(['error' => "Vous n'êtes pas autorisés à acceder à cette page"]);
       }
       try{
           DB::beginTransaction();
           $user = user::find($id);
           if($user){
               if($user->status == 0){
                   //desctive dans la table des prospects
                   $codeparrainage = new WebUtilisateurController();
                   $user->status = 1;
                   $user->validated_by = Auth::user()->id;
                   $user->validated_at = Carbon::now();
                   $user->save();

                   //On ajoute le client dans la table des users
                   $newAgent = new User();
                   $newAgent->name = $user->name;
                   $newAgent->surname = $user->surname;
                   $newAgent->password = $user->password;
                   $newAgent->login =$user->phone;
                   $newAgent->email_verified_at = Carbon::now();
                   $newAgent->created_by = Auth::user()->id;
                   $newAgent->telephone = $user->phone_court;
                   $newAgent->email = $user->email;
                   $newAgent->quartier = quartier::where("id",$user->quartier_id)->first()->name_quarter;
                   $newAgent->quartier_id = $user->quartier_id;
                   $newAgent->adresse = $user->adresse;
                   $newAgent->type_user_id = UserRolesEnum::AGENT->value;
                   $newAgent->distributeur_id = 1;
                   $newAgent->countrie_id = 1;
                   $newAgent->status = 1;
                   $newAgent->numcni = $user->numero_piece;
                   $newAgent->datecni = $user->date_validite;
                   $newAgent->seuilapprovisionnement="100000";
                   $newAgent->moncodeparrainage = "KI".$codeparrainage->genererChaineAleatoire(8);
                   $newAgent->save();

                   //On informe le client par SMS que son profil a été validé
                   $sms = new ApiSmsController();
                   $tel =$user->phone;
                   $msgBienvenue = $user->surname.", Votre compte KIABOO a été activé avec succès. Vous pouvez vous connecter dès à présent sur l'application KIABOO. Merci de changer votre mot de passe.";
                   $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msgBienvenue));
                   $msgInfo = "Ne tardez plus, vous pouvez commencer à effectuer les transactions mobiles money et profiter de nombreux avantages.";
                   $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msgInfo));
                   DB::commit();
                   return redirect()->back()->with('success', 'Prospect validated successfully');
               }else{
                   DB::rollBack();
                   return redirect()->back()->withInput()->withErrors(['error' => 'Ce prospect a déjà été traité']);
               }
           }else{DB::rollBack();
               return redirect()->back()->withInput()->withErrors(['error' => 'Prospect n\'existe pas']);
           }
       }catch (\Exception $exception){
           DB::rollBack();
           return redirect()->back()->with('error', $exception->getMessage());
       }

   }

   public function rejectedProspect($id){
       if(Auth::user()->type_user_id !=UserRolesEnum::SUPADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::ADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::BACKOFFICE->value){
           return redirect()->back()->withInput()->withErrors(['error' => "Vous n'êtes pas autorisés à acceder à cette page"]);
       }
       $user = User::find($id);
       if($user){
           if($user->status == "0"){
               $user->status = 2;//rejete
               $user->validated_by = User::user()->id;
               $user->validated_at = Carbon::now();
               $user->save();

               $sms = new ApiSmsController();
               $tel =$user->phone;
               $msgInfo = $user->surname.", Après analyse des éléments soumis via l'application KIABOO, nous n'avons pas été en mésure de valider votre compte KIABOO. Merci de vous rapprocher de l'agence KIABOO la plus proche";
               $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msgInfo));

               return redirect()->back()->with('success', 'Prospect rejeted successfully');
           }else{
               return redirect()->back()->withInput()->withErrors(['error' => 'Ce prospect a déjà été traité']);
           }
       }else{
           return redirect()->back()->withInput()->withErrors(['error' => 'Prospect n\'existe pas']);
       }
   }

    public function editProspect($id){
        if(Auth::user()->type_user_id !=UserRolesEnum::SUPADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::ADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::BACKOFFICE->value){
            return redirect()->back()->withInput()->withErrors(['error' => "Vous n'êtes pas autorisés à acceder à cette page"]);
        }
        $editProspect = prospect::with('quartier', 'ville_piece')->where('id', $id)->first();
        return view('pages.prospect.editprospect', compact('editProspect'));
    }

}
