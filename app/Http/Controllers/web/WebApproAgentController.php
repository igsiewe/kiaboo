<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\api\ApiSmsController;
use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Mail\infoRechargeAgent;
use App\Models\ApproDistributeur;
use App\Models\Distributeur;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WebApproAgentController extends Controller
{
    public function setTopUpAgent(Request $request){

        $validator = Validator::make($request->all(), [
            'agent' => 'required|integer',
            'amount' => 'required|integer|min:50000|max:1000000',
        ]);
        if(Auth::user()->status == 0){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }
        if(Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }

        if ($validator->fails()) {
           // return response(['status'=>422,'message' => $validator->errors()->first()], 422);
            return redirect()->back()->withErrors($validator->errors()->first());
        }

        $Distributeur_id = Auth::user()->distributeur_id;
        $Distributeur = Distributeur::where('id', $Distributeur_id)->where('application',1)->get();

        if($Distributeur->count()==0){
            return redirect()->back()->withErrors('Distributor have not been specified in your profil');
        }
        $balanceDistributeur = $Distributeur->first()->balance_after;
        $newBalanceDistributeur = doubleval($balanceDistributeur)-doubleval($request->amount);
        if(doubleval($balanceDistributeur)<doubleval($request->amount)){
            return redirect()->back()->withErrors('You don\'t have enough balance to perform this operation');
        }
        $agent = User::where('id', $request->agent)->where('application',1)->get();
        $telephoneAgent = $agent->first()->telephone;
        $nomAgent = $telephoneAgent." ".strtoupper($agent->first()->name);
        $emailAgent = $agent->first()->email;

        if($agent->count()==0){
            return redirect()->back()->withErrors('Agent not found');
        }
        if($agent->first()->status ==0){
            return redirect()->back()->withErrors('Agent is not active');
        }

        if($agent->first()->distributeur_id != $Distributeur_id){
            return redirect()->back()->withErrors('You can\'t authorize to relaod this agent');
        }
        $balanceAgent = $agent->first()->balance_after;
        $newBalanceAgent = doubleval($balanceAgent)+doubleval($request->amount);


        try{
            DB::beginTransaction();
            $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();
            //Mise à jour sous distributeur

            $Distributeur->first()->update([
                'balance_after'=>$newBalanceDistributeur,
                'balance_before'=>$balanceDistributeur,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>3,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);

            $agent->first()->update([
                'balance_after'=>$newBalanceAgent,
                'balance_before'=>$balanceAgent,
                'last_amount'=>$request->amount,
                'date_last_transaction'=>Carbon::now(),
                'user_last_transaction_id'=>Auth::user()->id,
                'last_service_id'=>3,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'reference_last_transaction'=>$payToken,
            ]);
            //Debit table transaction pour le distributeur,
            $debitDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>3,
                'balance_before'=>$balanceDistributeur,
                'balance_after'=>$newBalanceDistributeur,
                'debit'=>$request->amount,
                'credit'=>0,
                'status'=>1,
                'description'=>'SUCCESSFULL',
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$Distributeur_id,
                'distributeur_id'=>$Distributeur_id,
                'agent_id'=>$request->agent,
                'balance_before_partenaire'=>$balanceAgent,
                'balance_after_partenaire'=>$newBalanceAgent,
                'fichier'=>"distributeur",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>$agent->first()->telephone,
                'date_end_trans'=>Carbon::now(),
                'moyen_payment'=>"Cash",
            ]);

            //Credit table transaction pour l'agent
            $creditSousDistributeur = Transaction::create([
                'reference'=>$payToken,
                'reference_partenaire'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>3,
                'status'=>1,
                'description'=>'SUCCESSFULL',
                'balance_before'=>$balanceAgent,
                'balance_after'=>$newBalanceAgent,
                'debit'=>0,
                'credit'=>$request->amount,
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$request->agent,
                'agent_id'=>$request->agent,
                'distributeur_id'=>$Distributeur_id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'paytoken'=>$payToken,
                'date_operation'=>Carbon::now()->format('Y-m-d'),
                'heure_operation'=>Carbon::now()->format('H:i:s'),
                'customer_phone'=>Auth::user()->telephone,
                'balance_before_partenaire'=>$balanceDistributeur,
                'balance_after_partenaire'=>$newBalanceDistributeur,
                'date_end_trans'=>Carbon::now(),
                'moyen_payment'=>"Cash",
            ]);
            $nomDistributeur = Auth::user()->telephone." ".Distributeur::where("id", Auth::user()->distributeur_id)->first()->name_distributeur; //Auth::user()->telephone." ".Auth::user()->name;
            $msg = "Compte rechargé par ".strtoupper($nomDistributeur)." vers ".$nomAgent.". Informations détaillées: ID transaction: ".$payToken.", Montant transaction : ".$request->amount." F CFA. Nouveau solde : ".$newBalanceAgent." F CFA. Merci de votre confiance";
            $sms = new ApiSmsController();
            $tel ="237".$telephoneAgent;


            $data = [
                'nameAgent'=>$nomAgent,
                'idTransaction'=>$payToken,
                'amount'=>$request->amount,
                'newBalance'=>$newBalanceAgent,
                'nameDistributeur'=>$nomDistributeur,
                'logo'=>base64_encode(file_get_contents(asset('/assets/images/logoMail.png')))
            ];
            $idAppro = "DépôtN°".$payToken;
//            if(mail::to($emailAgent)->send(new infoRechargeAgent($data))){
//                $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msg));
//            }
            DB::commit();
            return redirect()->back()->with('success', 'Approvisionnement agent effectué avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error([
                    'User'=>Auth::user()->id,
                    'Service'=>3,
                    'Agent'=>$request->agent,
                    'Distributeur'=>$Distributeur_id,
                    'amount'=>$request->amount,
                    'error'=>$e->getMessage(),
            ]);
            return redirect()->back()->withErrors('Une erreur est survenue lors de l\'approvisionnement de l\'agent '.$e->getMessage());
        }
    }

    public function GenereRang(){

        $rang = "";
        $chaine = ApproDistributeur::all()->count();
        $longueur = strlen($chaine);

        if($longueur==0){
            $rang = "00001";
        }
        if ( $longueur == 1){
            $rang="0000".($chaine+1);
        }
        if ( $longueur == 2){
            $rang="000".($chaine+1);
        }
        if ( $longueur == 3){
            $rang="00".($chaine+1);
        }
        if ( $longueur == 4){
            $rang="0".($chaine+1);
        }
        if ( $longueur > 4){
            $rang=($chaine+1);
        }

        return $rang;
    }

    public function getDetailAgentTopUpd($id){
        $agent = User::where('id',$id)->with('ville')->get()->first();
        return view('pages.topupagent.detail_agent',compact('agent'));
    }

}
