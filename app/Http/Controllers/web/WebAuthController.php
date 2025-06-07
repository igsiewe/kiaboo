<?php

namespace App\Http\Controllers\web;
use App\Http\Controllers\BaseController;
use App\Http\Enums\UserRolesEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
}
