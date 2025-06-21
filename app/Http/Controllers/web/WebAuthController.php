<?php

namespace App\Http\Controllers\web;
use App\Http\Controllers\BaseController;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class WebAuthController extends BaseController
{


    public function login(Request $request)
    {

        $request->validate([
            'login' => 'required|email|min:8|max:255',
            'password' => 'required|string|min:6|max:25',
            'captcha' => 'required|captcha'
        ]);

        $credentials = $request->only('login', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
        //    if (Auth::user()->status == 1 && Auth::user()->application==1 && Auth::user()->view==1 && (Auth::user()->type_user_id != UserRolesEnum::AGENT->value)) {
            if (Auth::user()->status == 1 && Auth::user()->view==1 && (Auth::user()->type_user_id != UserRolesEnum::AGENT->value)) {
                $updateConnexion = DB::table('users')->where('id', Auth::user()->id)->update(['last_connexion' => Carbon::now()]);
                if($updateConnexion){
                   return redirect()->intended('dashboard');
                }else{
                    return redirect()->back()->withErrors('Erreur de connexion');
                }

            } else {
                return redirect()->back()->withErrors('Utilisateur non autorisé');
            }
        }

        return redirect()->back()->withErrors('Login ou mot de passe incorrect.');
    }

    public function reloadCaptcha()
    {
        return response()->json(['captcha'=> captcha_img()]);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Session::flush();
            Auth::logout();
            return Redirect('/');
        }
    }

    function passwordGenerate($length=20){
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for($i=0; $i<$length; $i++){
            $string .= $chars[rand(0, strlen($chars)-1)];
        }
        return $string;
    }

    public function setUpdatePassword(Request $request){
        # Validation

        $request->validate([
            'old_password' => 'required|string|max:24',
            'new_password' => 'required|min:12|max:24',
            'confirmation' => 'required|string|min:12|max:24|same:new_password',
        ]);


        #Match The Old Password
        $check = Hash::check($request->old_password, auth()->user()->password);
        if($check !=1){
            return redirect()->back()->withErrors('Votre mot de passe est incorrect');

        }


        #Update the new Password
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password),
            'updated_at' => Carbon::now(),
            'updated_by' =>Auth::user()->id
        ]);

        Session::flush();
        Auth::logout();
        return Redirect('/')->with("Mot de passe changé avec succes");


    }

    public function InitPasswordUserProfil($id){

        $user = User::where('id', $id)->where("status_delete",0)->where("view",1)->first();
        if($user){
            $distributeur = $user->distributeur_id;
            if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
                if($distributeur !=Auth::user()->type_user_id){
                    return redirect()->back()->withErrors('Vous ne disposez pas de droit pour cette opération');
                }
            }else{
                $newPassword = $this->passwordGenerate();
                $user->update([
                    'password' => Hash::make($newPassword),
                    'updated_at' => Carbon::now(),
                    'updated_by'=>Auth::user()->id
                ]);
                return redirect()->back()->with("Le mot de passe a été réinitialisé et transmis à l'utilisateur avec succès");
            }
        }
    }
}
