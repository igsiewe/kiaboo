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
           $prospect = prospect::find($id);
           if($prospect){
               if($prospect->status == 0){
                   //desctive dans la table des prospects
                   $codeparrainage =$this->extraireChaine( ($this->nettoyerTexte($prospect->name."".$prospect->surname)),0,5)."".$this->extraireChaine($prospect->phone,0,3);

                   $prospect->status = 1;
                   $prospect->validated_by = Auth::user()->id;
                   $prospect->validated_at = Carbon::now();
                   $prospect->save();

                   //On ajoute le client dans la table des users
                   $newAgent = new User();
                   $newAgent->name = $prospect->name;
                   $newAgent->surname = $prospect->surname;
                   $newAgent->password = $prospect->password;
                   $newAgent->login =$prospect->phone;
                   $newAgent->email_verified_at = Carbon::now();
                   $newAgent->created_by = Auth::user()->id;
                   $newAgent->telephone = $prospect->phone_court;
                   $newAgent->email = $prospect->email;
                   $newAgent->quartier = quartier::where("id",$prospect->quartier_id)->first()->name_quarter;
                   $newAgent->quartier_id = $prospect->quartier_id;
                   $newAgent->adresse = $prospect->adresse;
                   $newAgent->type_user_id = UserRolesEnum::AGENT->value;
                   $newAgent->distributeur_id = 1;
                   $newAgent->countrie_id = 1;
                   $newAgent->status = 1;
                   $newAgent->numcni = $prospect->numero_piece;
                   $newAgent->datecni = $prospect->date_validite;
                   $newAgent->seuilapprovisionnement="150000";
                   $newAgent->optin=$prospect->optin;
                   $newAgent->moncodeparrainage = strtoupper("KI".$codeparrainage);
                   $newAgent->save();

                   //On informe le client par SMS que son profil a été validé
                   $sms = new ApiSmsController();
                   $tel =str_replace("+","",$prospect->phone);
                   $msgBienvenue = "Bonjour ".$prospect->surname."\n\nVotre compte KIABOO a été activé avec succès.\n\nVous pouvez vous connecter dès à présent sur l'application KIABOO.\n\nMerci de changer votre mot de passe.\n\nEquipe KIABOO";
                   $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msgBienvenue));
                   $msgInfo = "Ne tardez plus, vous pouvez commencer à effectuer les transactions mobiles money et profiter de nombreux avantages.\n\nEquipe KIABOO";
                   $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msgInfo));
                   DB::commit();
                   return redirect()->back()->with('success', 'La demande de ce prospect a été validée avec succès');
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

    function nettoyerTexte($texte) {
        // 1. Remplace les caractères accentués par leur équivalent sans accent
        $texte = strtr(
            $texte,
            [
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'à' => 'a', 'â' => 'a', 'ä' => 'a',
                'î' => 'i', 'ï' => 'i',
                'ô' => 'o', 'ö' => 'o',
                'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                'ç' => 'c',
                'É' => 'E', 'È' => 'E', 'Ê' => 'E',
                'À' => 'A', 'Â' => 'A', 'Ç' => 'C',
            ]
        );

        // 2. Supprime les apostrophes, traits d'union et espaces
        $texte = str_replace(["'", "-", " "], "", $texte);

        return strtoupper($texte);
    }

    function extraireChaine($texte, $debut, $longueur = null) {
        return $longueur ? substr($texte, $debut, $longueur) : substr($texte, $debut);
    }

   public function rejectedProspect($id){
       if(Auth::user()->type_user_id !=UserRolesEnum::SUPADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::ADMIN->value && Auth::user()->type_user_id !=UserRolesEnum::BACKOFFICE->value){
           return redirect()->back()->withInput()->withErrors(['error' => "Vous n'êtes pas autorisés à acceder à cette page"]);
       }
       $prospect = prospect::find($id);
       if($prospect){

           if($prospect->status == "0"){
               $prospect->status = 2;//rejete
               $prospect->validated_by = Auth::user()->id;
               $prospect->validated_at = Carbon::now();
               $prospect->save();

               $sms = new ApiSmsController();
               $tel =str_replace("+","",$prospect->phone);
               $msgInfo = "Bonjour ".$prospect->surname."\n\nAprès analyse des éléments soumis via l'application KIABOO, nous n'avons pas été en mésure de valider votre compte KIABOO.\n\nMerci de vous rapprocher de l'agence KIABOO la plus proche.\n\nEquipe KIABOO";
               $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msgInfo));

               return redirect()->back()->with('success', 'La demande de ce prospect a été rejetée.');
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
