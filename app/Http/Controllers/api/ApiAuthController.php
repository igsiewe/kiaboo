<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;

use App\Http\Enums\UserRolesEnum;
use App\Models\configuration;
use App\Models\monnaie;
use App\Models\Notification;
use App\Models\Partenaire;
use App\Models\prospect;
use App\Models\question;
use App\Models\recrutement;
use App\Models\Service;
use App\Models\User;
use App\Models\Version;
use App\Models\Ville;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Mockery\Matcher\Not;
use function Laravel\Prompts\table;

class ApiAuthController extends BaseController
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

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|min:3|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // Here, we get the user credentials from the request
        $credentials = [
            'login' => $request->login,
            'password' => $request->password,
            'status' => 1,
            'status_delete'=>0,
            'application'=>1,
            'view'=>1,
            'type_user_id' => UserRolesEnum::AGENT->value
        ];

        if (Auth::attempt($credentials)) {
            $users = Auth::user();
            //$user = User::where('id', $users->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before as balanceBefore', 'balance_after as balanceAfter', 'last_amount as lastAmount','sous_distributeur_id as sousDistributeur','date_last_transaction as dateLastTransaction','last_service_id as lastService', 'type_user_id as typeuser','countrie_id as country','reference_last_transaction as referenceLastTransaction', 'status')->first();
            $user = User::where('id', $users->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage','qr_code')->first();


            $partenaires = Partenaire::where("countrie_id",Auth::user()->countrie_id)->select("id","name_partenaire as nomPartenaire","logo_partenaire as logoPartenaire")->orderBy('name_partenaire', 'asc')->get();
            $infoVersion = Version::where('status',1)->get();
            $version = $infoVersion->first()->version;
            $urlApplication = $infoVersion->first()->url;
            $notification = Notification::where("status",1)->get();
            $monnaies = monnaie::where("status",1)->get();
            $transactions = DB::table('transactions')
                ->join('services', 'transactions.service_id', '=', 'services.id')
                ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent')
                ->where("fichier","agent")
                ->where("source",Auth::user()->id)
                ->where('transactions.status',1)
                ->orderBy('transactions.date_transaction', 'desc')
                ->limit(5)
                ->get();

            $services = Service::all();

            DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
            $token = $user->createToken('kiaboo');
            $access_token = $token->accessToken;
            $chaine = new ApiCheckController();
            $qr_code ="K".$chaine->genererChaineAleatoire(10)."-".Auth::user()->id."-".$chaine->genererChaineAleatoire(2);
            $user->last_connexion = Carbon::now();
            $user->version = $version;
            $user->urlApplication = $urlApplication;
            $user->qr_code = $qr_code;
            $user->save();

            $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                ->join("villes", "quartiers.ville_id", "=", "villes.id")
                ->where('users.id', $users->id)
                ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

            $questions = question::where("status",1)->orderBy('ordre', 'asc')->select('id','question','reponse','detail')->get();
            $configurations = configuration::where("status",1)->select('id','lien_politique', 'lien_cgu', 'lien_mention', 'lien_appstore','lien_playstore', 'telephone_support', 'email_support', 'message_parrainage')->get();

            return $this->respondWithToken($access_token, $user, $partenaires, $transactions, $services,$version,$urlApplication, $notification, $monnaies, $questions, $configurations);
        }

        return response()->json([
            'message' => 'Invalid login details',
        ], 401);
    }

    public function loginRecrutement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|min:3|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // Here, we get the user credentials from the request
        $credentials = [
            'login' => $request->login,
            'password' => $request->password,
            'status' => 1,
            'status_delete'=>0,
            'type_user_id' => UserRolesEnum::Stagiaire->value
        ];

        if (Auth::attempt($credentials)) {
            $users = Auth::user();
            //$user = User::where('id', $users->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before as balanceBefore', 'balance_after as balanceAfter', 'last_amount as lastAmount','sous_distributeur_id as sousDistributeur','date_last_transaction as dateLastTransaction','last_service_id as lastService', 'type_user_id as typeuser','countrie_id as country','reference_last_transaction as referenceLastTransaction', 'status')->first();
          //  $user = User::where('id', $users->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();
            $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                ->join("villes", "quartiers.ville_id", "=", "villes.id")
                ->where('users.id', $users->id)
                ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

            DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
            $token = $user->createToken('kiaboo');
            $access_token = $token->accessToken;

            $user->last_connexion = Carbon::now();
            $user->save();

            $agents = recrutement::where("created_by", Auth::user()->id)->where("status",1)->orderBy("name")->orderBy("surname")->get();
            $villes = Ville::where("status",1)->get();
            return $this->respondWithTokenRecrutement($access_token, $user, $agents, $villes);
        }

        return response()->json([
            'message' => 'Invalid login details.',
        ], 401);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];

        return response($response, 200);
    }

    public function checkCodePin($codepin)
    {
        if ($codepin == '' || is_null($codepin)) {
            return response(['errors' => 'Code pin is miss'], 422);
        }
        $currentCodePin = Auth::User()->codepin;
        if (! Hash::check($codepin, $currentCodePin)) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'message' => 'Your code pin is not valid.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'sucess',
        ], 200);
    }

    public function genererChaineAleatoire($longueur = 10)
    {
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longueurMax = strlen($caracteres);
        $chaineAleatoire = '';
        for ($i = 0; $i < $longueur; $i++) {
            $chaineAleatoire .= $caracteres[rand(0, $longueurMax - 1)];
        }

        return $chaineAleatoire;
    }

    public function setNewUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|string|max:255',
            'surname' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users',
            'login' => 'required|string|unique:users',
            'email' => 'required|string|email|unique:users',
            'typeuser' => 'required|integer|min:1|max:12',
        ]);
        if(Auth::user()->status == 0){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }
        if ($validator->fails()) {
            return response(['status'=>422,'message' => $validator->errors()->first()], 422);
        }

        if ($request->typeuser == UserRolesEnum::SDISTRIBUTEUR->value && Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value) {
            return $this->errorResponse('Your profil don\'t allow to perfom this operation.', 404);
        }
        if ($request->typeuser == UserRolesEnum::AGENT->value && Auth::user()->type_user_id != UserRolesEnum::SDISTRIBUTEUR->value) {
            return $this->errorResponse('Your profil don\'t allow to perfom this operation', 404);
        }

        try {
            DB::beginTransaction();
            $user = new User();
            $newPassword = $this->genererChaineAleatoire(8);

            $user->name = $request->name;
            $user->surname = strtoupper($request->surname);
            $user->email = $request->email;
            $user->telephone = $request->telephone;
            $user->login = $request->login;
            $user->status = 1;
            $user->countrie_id = Auth::user()->countrie_id;
            $user->type_user_id = $request->typeuser;
            $user->password = bcrypt($newPassword);
            $user->email_verified_at = Carbon::now();
            $user->created_by = Auth::user()->id;
            $user->quartier_id = $request->quartier;
            if ($request->typeuser == UserRolesEnum::SDISTRIBUTEUR->value || $request->typeuser == UserRolesEnum::AGENT->value) {
                if($request->sous_distributeur=="" || $request->sous_distributeur==null){
                    DB::rollBack();
                    return $this->errorResponse('Sous distributeur is required',404);
                }
                $user->sous_distributeur_id = $request->sous_distributeur;
                $user->distributeur = Auth::user()->distributeur_id;
            }
            if ($request->typeuser == UserRolesEnum::DISTRIBUTEUR->value) {
                if($request->distributeur=="" || $request->distributeur==null){
                    DB::rollBack();
                    return $this->errorResponse('Distributeur is required',404);
                }
                $user->distributeur_id = $request->distributeur;
            }
            $result = $user->save();
            if ($result) {
                DB::commit();
                return $this->sendResponse($user, 'User added succeffuly');
            } else {
                DB::rollBack();
                return $this->errorResponse('User don\t added', 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }

    }

    public function deactivatedUser($idUser){

        if(Auth::user()->status == 0){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }

        $user = User::find($idUser);

        if($user){
            if($user->id== Auth::user()->id){
                return $this->errorResponse('You cannot deactivate your own profile.', 404);
            }
            if ($user->type_user_id == UserRolesEnum::SDISTRIBUTEUR->value && Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value) {
                return $this->errorResponse('Your profil don\'t allow to perfom this operation.', 404);
            }
            if ($user->type_user_id == UserRolesEnum::AGENT->value && Auth::user()->type_user_id != UserRolesEnum::SDISTRIBUTEUR->value) {
                return $this->errorResponse('Your profil don\'t allow to perfom this operation', 404);
            }
            $user->status = 0;
            $user->updated_at = Carbon::now();
            $user->updated_by = Auth::user()->id;
            $user->save();
            return $this->sendResponse($user, 'User deactivated successfully');
        }else{
            return $this->errorResponse('User not found', 404);
        }
    }

    public function activatedUser($idUser){

        if(Auth::user()->status == 0){
            return $this->errorResponse('You cannot authorize to perform this operation', 404);
        }

        $user = User::find($idUser);

        if($user){
            if($user->id== Auth::user()->id){
                return $this->errorResponse('You cannot activate your own profile.', 404);
            }
            if ($user->type_user_id == UserRolesEnum::SDISTRIBUTEUR->value && Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value) {
                return $this->errorResponse('Your profil don\'t allow to perfom this operation.', 404);
            }
            if ($user->type_user_id == UserRolesEnum::AGENT->value && Auth::user()->type_user_id != UserRolesEnum::SDISTRIBUTEUR->value) {
                return $this->errorResponse('Your profil don\'t allow to perfom this operation', 404);
            }
            $user->status = 1;
            $user->updated_by = Auth::user()->id;
            $user->updated_at = Carbon::now();
            $user->save();
            return $this->sendResponse($user, 'User activated successfully');
        }else{
            return $this->errorResponse('User not found', 404);
        }
    }

    public function getUserData(){
       // $user = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before','total_commission', 'balance_after', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();
        $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
            ->join("villes", "quartiers.ville_id", "=", "villes.id")
            ->where('users.id',Auth::user()->id)
            ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

        return $this->sendResponse($user, 'User data');
    }

    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|string|max:255',
            'surname' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users',
            'email' => 'required|string|email|unique:users',
            'pays' => 'required|string',
            'isCodeParrainage' => 'required|boolean',
         ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 404);
        }


        try {
            DB::beginTransaction();
            $statutcodeparraisange = false;
            $checkUser = User::where('telephone', $request->telephone)->first();
            if($checkUser){
                DB::rollBack();
                return $this->errorResponse('Ce numéro de téléphone existe déjà', 404);
            }
            if($request->isCodeParrainage == true){
                $validator = Validator::make($request->all(), [
                    'codeParrainage' => 'required|string',
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return $this->errorResponse($validator->errors(), 404);
                }
                $parrainageCheck = User::where('codeparrainage', $request->codeParrainage)->first();
                if(!$parrainageCheck){
                    DB::rollBack();
                    return $this->errorResponse("Ce code de parrainage n'est pas valide", 404);
                }
                $statutcodeparraisange = true;
            }
            $user = new User();
            $newPassword = $this->genererChaineAleatoire(8);
            $moncodeParrainage = "KIAB".$this->genererChaineAleatoire(9);
            $user->name =strtoupper($request->name);
            $user->surname = strtoupper($request->surname);
            $user->email = $request->email;
            $user->telephone = $request->telephone;
            if ($statutcodeparraisange == true) {
                $user->codeparrainage = $request->codeParrainage;
            }
            $user->moncodeparrainage = $moncodeParrainage;
            $user->login = $request->telephone;
            $user->status = 1;
            $user->countrie_id = 1;
            $user->countrie = $request->pays;
            $user->type_user_id = 5;
            $user->password = bcrypt($newPassword);
            $user->created_by = 1;
            $user->distributeur_id =1;
            $user->optin = $request->optin;
            $user->sous_distributeur_id =1;
            $user->quartier_id=$request->quartier;
            $result = $user->save();
            if ($result) {
                DB::commit();
                return $this->sendResponse($user, 'Votre compte a été créé avec succès. Vous devez confirmer votre adresse email en cliquant sur le lien que nous venons de vous envoyer par email.');
            } else {
                DB::rollBack();
                return $this->errorResponse('User don\t added', 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }

    }

    public function updateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|string|max:255',
            'surname' => 'required|string|max:255',
           // 'email' => 'required|string|unique:users',
            'optin' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 404);
        }


        try {
            DB::beginTransaction();
            $checkEmail = User::where('email', $request->email)->where('id', '!=', Auth::user()->id)->first();
            if($checkEmail){
                DB::rollBack();
                return $this->errorResponse('Cet email existe déjà', 404);
            }
            $updateUser = User::where('id', Auth::user()->id)->update([
                'name' => strtoupper($request->name),
                'surname' => strtoupper($request->surname),
                'email' => $request->email,
                'optin'=>$request->optin,
                'updated_by' => Auth::user()->id,
                'updated_at' => Carbon::now(),
            ]);
           // $user = User::where('id', Auth::user()->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before','total_commission', 'balance_after', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();
            $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                ->join("villes", "quartiers.ville_id", "=", "villes.id")
                ->where('users.id',Auth::user()->id)
                ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

            if ($updateUser) {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Votre compte a été modifié avec succès. Vous devez confirmer votre adresse email en cliquant sur le lien que nous venons de vous envoyer par email.',
                    'user' => $user
                ], 200);
            } else {
                DB::rollBack();
                return $this->errorResponse('User don\t updated', 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->error($message,$e);
        }

    }

    public function updatePassword(Request $request)
    {
        # Validation
        $request->validate([
            'old_password' => 'required|string|max:50',
            'new_password' => 'required|min:12|max:50|confirmed',
        ]);


        #Match The Old Password
        $check = Hash::check($request->old_password, auth()->user()->password);
        if($check !=1){
            return response()->json([
                'status' => 'error',
                'message' => 'Votre mot de passe est incorrect'
            ], 404);

        }


        #Update the new Password
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Votre mot de passe a été changé avec succès!'
        ], 200);

    }

    public function checkNumeroUser(Request $request){
        $validator = Validator::make($request->all(), [
            'numero' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $user = User::where('login', $request->numero)->where('type_user_id',UserRolesEnum::AGENT->value)->first();
        if ($user) {
            if($user->status == 0){
                return response()->json(['success' => false, 'message' => 'Compte inactif'], 404);
            }
            $otpcode = rand(100000, 999999);
            $numero = str_replace("+","",$request->numero);
            $send = new ApiSmsController();
            $message = "Le code de réinitialisation du mot de passe de votre compte KIABOO est ".$otpcode;
            $envoyersMS = $send->SendSMS($numero,utf8_decode($message));
            return response()->json(['success' => true, 'message' => 'Un OTP a été envoyé par SMS. ','otpcode'=>$otpcode], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Numero non trouvé'], 404);
        }
    }

    public function checkNumeroAgent(Request $request){
        $validator = Validator::make($request->all(), [
            'numero' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $user = recrutement::where('telephone', $request->numero)->first();
        if ($user) {
            return response()->json(['success' => false, 'message' => 'Un agent a déjà été enregistré avec numéro'], 404);
        } else {
            $otpcode = rand(100000, 999999);
            $numero = str_replace("+","",$request->numero);
            $send = new ApiSmsController();
            $message = "Votre code de vérification KIABOO est ".$otpcode.". Si vous êtes d'accord pour vous enregistrer, communiquer ce code à l'agent KIABOO";
         //   $envoyersMS = $send->SendSMS($numero,utf8_decode($message));
            return response()->json(['success' => true, 'message' => "Un OTP a été envoyé par SMS ".$otpcode,'otpcode'=>$otpcode], 200);
        }
    }

    public function checkNumeroInscription(Request $request){
        $validator = Validator::make($request->all(), [
            'numero' => 'required',
            'dial_code'=>'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $numero = $request->dial_code.$request->numero;
        //On vérifie dans la tables des utilisateurs

        $user = User::where('login', $numero)->where('type_user_id',UserRolesEnum::AGENT->value)->first();
        if ($user) {
            return response()->json(['success' => false, 'message' => 'Ce numéro de téléphone est déjà enregistré'], 202);
        }
        //On vérifie dans la tables des recrutements

        $recrutement = prospect::where('phone', $numero)->first();
        if ($recrutement) {
            return response()->json(['success' => false, 'message' => 'Ce numéro de téléphone est déjà enregistré'], 202);
        }

        $otpcode = rand(100000, 999999);
        $numero = str_replace("+","",$numero);
        $send = new ApiSmsController();
        $message = "Votre code OTP est le ".$otpcode;
        $envoyersMS = $send->SendSMS($numero,utf8_decode($message));
        return response()->json(['success' => true, 'message' => 'Un OTP a été envoyé par SMS. ','otpcode'=>$otpcode,'phoneCourt'=>$request->numero], 200);

    }
    public function updateUserPassword(Request $request)
    {
        # Validation
        $request->validate([
            'userphone'=>'required',
            'password' => 'required|string|min:12|max:50',
           // 'password_confirmation' => 'required|string|min:8|max:20|confirmed',
        ]);
        #Find user

        $user = User::where('login',$request->userphone)->where('type_user_id',UserRolesEnum::AGENT->value)->where('status',1);

        if($user->count()>0){
            $user->update([
                'password' => Hash::make($request->password)
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully!'
            ], 200);
        }else{
            return response()->json([
                'status' => 'echec',
                'message' => 'User not found'
            ], 404);
        }
    }

    public function recrutement(Request $request)
    {
        # Validation
        $request->validate([
            'name'=>'required|string|min:2|max:255',
            'surname' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:recrutements',
            'telephone' => 'required|string|min:8|max:20|unique:recrutements',
            'ville_id' => 'required|integer',
            'quartier'=>'required|string',
            'numcni' => 'required|string|min:8|max:20',
        ]);
        #Find user

        $id = Auth::user()->id;
        $insert = recrutement::create([
            'name'=> strtoupper($request->name),
            'surname' => $request->surname,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'ville_id' => $request->ville_id,
            'quartier'=> $request->quartier,
            'adresse'=>$request->adresse,
            'date_creation'=>Carbon::now(),
            'datecni' =>Carbon::createFromFormat('d/m/Y', $request->datecni)->format('Y-m-d'),
            'numcni' => $request->numcni,
            'created_by'=>$id,
            'updated_by'=>$id,
            'status'=>1,
        ]);
        if($insert){
            $agents = recrutement::where("created_by", Auth::user()->id)->where("status",1)->orderBy("name")->orderBy("surname")->get();
            $villes = Ville::where("status",1)->get();
          //  $user = User::where('id', $id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();
            $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                ->join("villes", "quartiers.ville_id", "=", "villes.id")
                ->where('users.id', $id)
                ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.ville_id','quartiers.ville_id','users.qr_code')->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Partenaire enregistré avec succès!',
                'agents' => $agents,
                'villes'=>$villes,
                'user'=>$user,
            ], 200);

        }else{
            return response()->json([
                'status' => 'echec',
                'message' => 'Une erreur innatendue est survenue'
            ], 404);
        }
    }

    public function changePasswordSwagger(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6|max:255',
            'new_password' => 'required|string|min:6|max:255',
            'confirm_password' => 'required|string|min:6|max:255|same:new_password',
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
            $user = Auth::user();
            if (!password_verify($request->old_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 'ERR-OLD_PASSWORD-INVALID', // 'ERR-CREDENTIALS-INVALID
                    'message' => 'old password is invalid',
                ], 400);
            }

            $user->password = bcrypt($request->new_password);
            $user->save();
            return response()->json(
                [
                    'success' => true,
                    'statusCode' => 'PASSWORD-CHANGED-SUCCESSFULLY',
                    'message' => 'password changed successfully',
                ],
                500
            );

        } catch (\Exception $err) {
            Log::error($err);
            return response()->json(
                [
                    'success' => false,
                    'statusCode' => 'ERR-UNAVAILABLE',
                    'message' => $err->getMessage(),
                ],
                500
            );
        }

    }

    public function updateUserInfo(Request $request){

        $validator = Validator::make($request->all(), [
            'donnee' => 'required|email',
        ]);

        $user = User::find(Auth::user()->id);
        if($request->champ="EMAIL") {

        $user->update([ 'email'=> $request->donnee,]);
        }else if($request->champ="ADRESSE") {
            $user->update([ "adresse"=> $request->donnee,]);
        }else if($request->champ="QUARTIER") {
            $user->update([ "quartier_id" => $request->donnee,]);
        }


        $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
            ->join("villes", "quartiers.ville_id", "=", "villes.id")
            ->where('users.id', Auth::user()->id)
            ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code')->first();

        return response()->json([
            'success' => true,
            'message' => 'Modifié avec succès',
            'user' => $user,
        ],200);
    }


}
