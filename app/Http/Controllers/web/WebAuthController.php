<?php

namespace App\Http\Controllers\web;
use App\Http\Controllers\BaseController;
use App\Http\Enums\UserRolesEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;

class WebAuthController extends BaseController
{

//    public function authenticated(Request $request, $user)
//    {
//
//        if ($user->uses_two_factor_auth) {
//            $google2fa = new Google2FA();
//
//            if ($request->session()->has('2fa_passed')) {
//                $request->session()->forget('2fa_passed');
//            }
//
//            $request->session()->put('2fa:user:id', $user->id);
//            $request->session()->put('2fa:auth:attempt', true);
//            $request->session()->put('2fa:auth:remember', $request->has('remember'));
//
//            $otp_secret = $user->google2fa_secret;
//            $one_time_password = $google2fa->getCurrentOtp($otp_secret);
//
//            return redirect()->route('2fa')->with('one_time_password', $one_time_password);
//        }
//        $google2fa = app('pragmarx.google2fa');
//        $registration_data = $request->all();
//
//        $registration_data["google2fa_secret"] = $google2fa->generateSecretKey();
//        $request->session()->put('secret', $registration_data["google2fa_secret"]);
//
//        $QR_Image = $google2fa->getQRCodeInline(
//            config('app.name'),
//            $registration_data['login'],
//            $registration_data['google2fa_secret']
//        );
//
//        return view('auth.2faregisters', ['QR_Image' => $QR_Image, 'secret' => $registration_data["google2fa_secret"]]);
//      //  return redirect()->intended($this->redirectPath());
//    }

    protected function authenticated(Request $request, $user)
    {
        if (Google2FA::isActivated()) {
            Auth::logout();

            $request->session()->put('user_id', $user->id);
            $request->flashOnly(['email']);
            return redirect()->route('2fa.login');
        }

        return redirect()->intended($this->redirectPath());
    }
    public function redirectPath()
    {

        if (property_exists($this, 'redirectPath'))
        {

            return $this->redirectPath;
        }
         return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }

    public function assign2FA(Request $request)
    {
        $verificationCode = $request->get('one_time_password');
        $secret = $request->session()->get('google2fa_secret');
        if (!$secret || !Google2FA::verifyGoogle2FA($secret, $verificationCode)) {
            // store secret in the session only for the next request
            $request->session()->reflash();

            throw ValidationException::withMessages([
                'one_time_password' => [trans('auth.2fa.failed')],
            ]);
        }

        $user = $request->user();
        $user->google2fa_secret = $secret;
        $user->save();
        return view('auth.google2fa.activate');
    }

    public function deactivate2FA(Request $request)
    {
        $user = $request->user();

        //make secret column blank
        $user->google2fa_secret = null;
        $user->save();

        return view('auth.google2fa.deactivate');
    }

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
            if (Auth::user()->status == 1 && (Auth::user()->type_user_id != UserRolesEnum::AGENT->value)) {
                $updateConnexion = DB::table('users')->where('id', Auth::user()->id)->update(['last_connexion' => Carbon::now()]);
                if($updateConnexion){
                    return $this->authenticated($request, Auth::user());
                    // $this->authenticated($request, Auth::user());
                   // return redirect()->intended('dashboard');
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
