<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ApiCheckController extends Controller
{
    public function GenereRang(){

        $rang = "";
        $chaine = Transaction::all()->count();
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

    function genererChaineAleatoire($longueur = 10)
    {
        // $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longueurMax = strlen($caracteres);
        $chaineAleatoire = '';
        for ($i = 0; $i < $longueur; $i++)
        {
            $chaineAleatoire .= $caracteres[rand(0, $longueurMax - 1)];
        }
        return $chaineAleatoire;
    }

    function checkUserValidity(){

        if(Auth::user()->status==0){
            return false;
        }

        if(Auth::user()->type_user_id != UserRolesEnum::AGENT->value){
            return false;
        }

        return true;

    }

    function checkUserBalance($montant)
    {
        if (Auth::user()->balance_after < $montant) {
            return false;
        }
        return true;
    }

    function checkUserApiBalance($user, $montant)
    {
        if (User::where("id",$user)->first()->balance_after < $montant) {
            return false;
        }
        return true;
    }

    function checkFiveLastTransaction($beneficiaire, $montant, $service){
        $dateActuelle = Carbon::now();
        $dateAvant = Carbon::now()->addMinutes(-5);

        $dateActuelle = Carbon::parse($dateActuelle);
        $dateAvant = Carbon::parse($dateAvant);

        $checkTransaction = Transaction::where('created_by', Auth::user()->id)
            ->where('service_id', $service)
            ->where('debit', floatval($montant))
            ->where("status",1)
            ->where('customer_phone', $beneficiaire)
            ->whereBetween('created_at', [$dateAvant, $dateActuelle])
            ->get();

        if ($checkTransaction->count() > 0) {
            return true;
        }
        return false;
    }

    function init_Depot($montant, $beneficiaire, $service, $payToken="", $device="",$latitude="", $longitude="", $place="",$application=1, $user=0,$merchandTransactionId=""){

        $reference = "DP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();

        try{
            DB::beginTransaction();
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'paytoken'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>$service,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>$montant,
                'credit'=>0,
                'status'=>0, //Initiate
                'created_by'=>$user, //Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$user, //Auth::user()->id,
                'fichier'=>"agent",
                'updated_by'=>$user, //Auth::user()->id,
                'customer_phone'=>$beneficiaire,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
                'device_notification'=>$device,
                'latitude'=>$latitude,
                "longitude"=>$longitude,
                "place"=>$place,
                "application"=>$application,
                "marchand_transaction_id"=>$merchandTransactionId,
                "version"=>Auth::user()->version,
            ]);

            if($Transaction) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'transId'=>$Transaction->id,
                    'reference'=>$reference,
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur inattentue s\' est produite. Veuillez contacter votre support.',
                ], 404);
            }
        }catch (\Exception $e){
            DB::rollback();
            Log::error([
                'function' => 'init_Depot',
                'user' => Auth::user()->id,
                'Service'=>$service,
                'erreur Message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' =>"Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
            ], $e->getCode());
        }


    }
    function init_Retrait($montant, $beneficiaire, $service, $payToken="", $device,$latitude, $longitude, $place){

        $reference = "RT".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();

        try{
            DB::beginTransaction();
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'paytoken'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>$service,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$montant,
                'status'=>0, //Initiate
                'created_by'=>Auth::user()->id,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>Auth::user()->id,
                'fichier'=>"agent",
                'updated_by'=>Auth::user()->id,
                'customer_phone'=>$beneficiaire,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
                'device_notification'=>$device,
                'latitude'=>$latitude,
                'longitude'=>$longitude,
                'place'=>$place,
                "version"=>Auth::user()->version,
            ]);

            if($Transaction) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'transId'=>$Transaction->id,
                    'reference'=>$reference,
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur inattentue s\' est produite. Veuillez contacter votre support.',
                ], 404);
            }
        }catch (\Exception $e){
            DB::rollback();
            Log::error([
                'function' => 'init_Retrait',
                'user' => Auth::user()->id,
                'Service'=>$service,
                'erreur Message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' =>"Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
            ], $e->getCode());
        }


    }

    function init_Payment($montant, $beneficiaire, $service, $payToken="",$user, $application="2", $device,$latitude, $longitude, $place){

        $reference = "PM".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();

        try{
            DB::beginTransaction();
            $Transaction= Transaction::create([
                'reference'=>$reference,
                'paytoken'=>$payToken,
                'date_transaction'=>Carbon::now(),
                'service_id'=>$service,
                'balance_before'=>0,
                'balance_after'=>0,
                'debit'=>0,
                'credit'=>$montant,
                'status'=>0, //Initiate
                'created_by'=>$user,
                'created_at'=>Carbon::now(),
                'countrie_id'=>Auth::user()->countrie_id,
                'source'=>$user,
                'fichier'=>"agent",
                'updated_by'=>$user,
                'customer_phone'=>$beneficiaire,
                'description'=>'INITIATED',
                'date_operation'=>date('Y-m-d'),
                'heure_operation'=>date('H:i:s'),
                'device_notification'=>$device,
                'latitude'=>$latitude,
                'longitude'=>$longitude,
                'place'=>$place,
                'application'=>$application,
                "version"=>Auth::user()->version,
            ]);

            if($Transaction) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'transId'=>$Transaction->id,
                    'reference'=>$reference,
                ], 200);
            }else{
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur inattentue s\' est produite. Veuillez contacter votre support.',
                ], 404);
            }
        }catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' =>"Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste",
            ], $e->getCode());
        }


    }

    function rang(){
        $rang =str_replace(str_replace(str_replace(Carbon::now(),"-","")," ",""),"/","");
    }

    function checkStatusService($idService)
    {

        $service = Service::where("id", $idService)->first();
        if($service->count() == 0){
            return false;
        }
        if ($service->status ==0) {
            return false;
        }
        return true;
    }

}
