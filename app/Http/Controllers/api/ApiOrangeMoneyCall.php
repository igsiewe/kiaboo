<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiOrangeMoneyCall extends Controller
{
    public function DepotOM($beneficiaire, $montant, $reference, $description)
    {
        $ApiOM = new ApiOrangeMoneyController();
        //On genere le token Access
        $response = $ApiOM->OM_GetTokenAccess();
        $dataAcessToken = json_decode($response->getContent());
        $codeAccessToken = $dataAcessToken->code;

        if ($codeAccessToken != 200) {
            return response()->json([
                'code' => $codeAccessToken,
                'error' => '1.error = '.$codeAccessToken,
            ]);
        }
        $accessToken = $dataAcessToken->accessToken;

        //On genere le PayToken du depot
        $responsePayToken = $ApiOM->OM_Cashin_init($accessToken);
        $dataPayToken = json_decode($responsePayToken->getContent());
        $codePayToken = $dataAcessToken->code;
        if ($codePayToken != 200) {
            return response()->json([
                'code' => $codePayToken,
                'error' => '2.error = '.$codePayToken,
            ]);
        }
        $payToken = $dataPayToken->payToken;

        //On execute le OM_Cashin_execute de dépôt

        $resposeCashin = $ApiOM->OM_Cashin_execute($accessToken, $payToken, $beneficiaire, $montant, $reference, $description);
        $dataCashIn = json_decode($resposeCashin->getContent());
        $codeDepotPay = $dataCashIn->code;

        if ($codeDepotPay != 200) {
            return response()->json([
                'code' => $codeDepotPay,
                'error' => $dataCashIn->message,
                'message' => $dataCashIn->message,
                'status' => $dataCashIn->status,
            ]);
        }

        return response()->json([
            'code' => $dataCashIn->code,
            'status' => $dataCashIn->status,
            'message' => $dataCashIn->message,
            'reference' => $dataCashIn->reference,
            'payToken' => $dataCashIn->payToken,
        ]);
    }

    public function RetraitOM($beneficiaire, $montant, $reference, $description)
    {
        $ApiOM = new ApiOrangeMoneyController();
        //On genere le token Access
        $response = $ApiOM->OM_GetTokenAccess();
        $dataAcessToken = json_decode($response->getContent());
        $codeAccessToken = $dataAcessToken->code;

        if ($codeAccessToken != 200) {
            return response()->json([
                'code' => $codeAccessToken,
                'error' => 'error',
            ]);
        }
        $accessToken = $dataAcessToken->accessToken;

        //On genere le PayToken du depot
        $responsePayToken = $ApiOM->OM_CashOut_init($accessToken);
        $dataPayToken = json_decode($responsePayToken->getContent());
        $codePayToken = $dataAcessToken->code;
        if ($codePayToken != 200) {
            return response()->json([
                'code' => $codePayToken,
                'error' => 'error',
            ]);
        }
        $payToken = $dataPayToken->payToken;

        //On execute le OM_Cashin_execute de dépôt

        $resposeCashin = $ApiOM->OM_CashOut_execute($accessToken, $payToken, $beneficiaire, $montant, $reference, $description);
        $dataCashIn = json_decode($resposeCashin->getContent());
        $codeDepotPay = $dataCashIn->code;

        if ($codeDepotPay != 200) {
            return response()->json([
                'code' => $codeDepotPay,
                'error' => 'error',
                'message' => $dataCashIn->message,
                'status' => $dataCashIn->status,
            ]);
        }

        return response()->json([
            'code' => $dataCashIn->code,
            'status' => $dataCashIn->status,
            'message' => $dataCashIn->message,
            'reference' => $dataCashIn->reference,
            'payToken' => $dataCashIn->payToken,
        ]);
    }

    public function CallBackOM_Retrait(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payToken' => 'required|string',
            'status' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => $validator->getMessageBag(),
            ]);
        }
         $checkPayToken = Transaction::where('paytoken', $request->payToken)->where('status', 2)->get();

        $idDevice = '';
        if ($checkPayToken->count() > 0 && $checkPayToken->first()->notificationdevice != null) {

            $idDevice = $checkPayToken->first()->notificationdevice;

            if ($request->status == 'FAILED') {
                $transaction = Transaction::where('payToken', $request->payToken)
                    ->update([
                        'status' => 4,
                        'callback_message' => $request->message,
                        'callback_status' => $request->status,
                        'date_end_trans' => Carbon::now(),
                        'updated_at' => now(),
                        'response_call_back_status' => 1,
                    ]);
                //On déclenche le push notification

//                $title = 'Kiaboo';
//                $subtitle = 'Kiaboo - Dépôt OM';
//                $messageCallback = $request->message;
//                $PushSMS = new ApiSendNotificationPushController();
//
//                $message = "Transaction terminée avec échec\n".$messageCallback;
//                $sendNotification = $PushSMS->SendPushNotification($idDevice, $title, $message);
            }

            if ($request->status == 'SUCCESSFULL') {
                $utilisateur = User::where('id', $checkPayToken->first()->user_id);
                $soldeUtilisateur = $utilisateur->get()->first()->balance_after;

                $sousDistributeur = User::where('id', $utilisateur->first()->sous_distributeur_id);
                $soldeSousDistributeur = $sousDistributeur->get()->first()->balance_after_trans;

                $Distributeur = User::where('id', $sousDistributeur->first()->distributeur_id);
                $soldeDistributeur = $Distributeur->get()->first()->balance_after_trans;

                $montant_calcul_solde = $checkPayToken->first()->amount;
                //on met à jour la table transaction

                $transaction = Transaction::where('payToken', $request->payToken)->update([
                    'status' => 3,
                    'callback_message' => $request->message,
                    'callback_status' => $request->status,
                    'balance_before_trans' => $soldeDistributeur,
                    'balance_after_trans' => floatval($soldeDistributeur) + floatval($montant_calcul_solde),

                    'balance_before_trans_sd' => $soldeSousDistributeur,
                    'balance_after_trans_sd' => floatval($soldeSousDistributeur) + floatval($montant_calcul_solde),

                    'balance_before_trans_agent' => $soldeUtilisateur,
                    'balance_after_trans_agent' => floatval($soldeUtilisateur) + floatval($montant_calcul_solde),
                    'fees' => 0,
                    'tva' => 0,
                    'taxe_fees' => 0,
                    'commission' => 0,
                    'taxe_commission' => 0,

                    'date_end_trans' => Carbon::now(),
                    'updated_at' => now(),
                    'response_call_back_status' => 1,

                ]);
                //on met à jour le solde de l'utilisateur

                $utilisateur->update([
                    'balance_before_trans' => $soldeUtilisateur,
                    'balance_after_trans' => floatval($soldeUtilisateur) + floatval($montant_calcul_solde),
                    'amount_last_trans' => $montant_calcul_solde,
                    'date_last_trans' => Carbon::now(),
                    'fees' => 0,
                    'taxe' => 0,
                    'last_beneficiary' => $checkPayToken->first()->customer_number,
                    'id_last_service' => $checkPayToken->first()->service_id,
                    'updated_at' => now(),
                ]);

                //On déclenche le push notification

//                $title = 'Kiaboo';
//                $subtitle = 'Kiaboo - Dépôt OM';
//                $messageCallBack = $request->message;
//                $message = "Transaction terminée avec succès \nID Transaction : ".$checkPayToken->first()->reference_partenaire."\nTéléphone : ".$checkPayToken->first()->customer_number."\nMontant : ".$checkPayToken->first()->amount." F CFA\n\n".$messageCallBack;
//
//                $PushSMS = new ApiSendNotificationPushController();
//                $sendNotification = $PushSMS->SendPushNotification($idDevice, $title,  $message);
            }
        } else {
            return response()->json([
                'code' => 400,
                'message' => 'PayToken not found',
            ]);
        }
    }

    public function CallBackOM($payToken, $status, $message, $idDevice, $user_id, $amount, $customer_number, $service_id, $reference_partenaire)
    {
        //dd($status);
        $messageCallBack = $message;
        if ($status == 'EXPIRED' || $status == 'FAILED') {
            $transaction = Transaction::where('payToken', $payToken)
                ->update([
                    'status' => 4,
                    'callback_message' => $message,
                    'callback_status' => $status,
                    'date_end_trans' => Carbon::now(),
                    'updated_at' => now(),
                    'response_call_back_status' => 1,
                ]);
            //On déclenche le push notification

            $title = 'Kiaboo';
            $subtitle = 'Kiaboo - Dépôt OM';
            $messageCallback = $message;
           // $PushSMS = new ApiSendNotificationPushController();

          //  $message = "Transaction terminée avec échec\n".$messageCallback;
          //  $sendNotification = $PushSMS->SendPushNotification($idDevice, $title,  $message);
        }

        if ($status == 'SUCCESSFULL') {
            $utilisateur = User::where('id', $user_id);
            $soldeUtilisateur = $utilisateur->get()->first()->balance_after_trans;

            $sousDistributeur = User::where('id', $utilisateur->first()->sous_distributeur_id);
            $soldeSousDistributeur = $sousDistributeur->get()->first()->balance_after_trans;

            $Distributeur = User::where('id', $sousDistributeur->first()->distributeur_id);
            $soldeDistributeur = $Distributeur->get()->first()->balance_after_trans;

            $montant_calcul_solde = $amount;
            //on met à jour la table transaction
            $message = "Transaction terminée avec succès \nID Transaction : ".$reference_partenaire."\nTéléphone : ".$customer_number."\nMontant : ".$amount." F CFA\n\n".$messageCallBack;
            $transaction = Transaction::where('payToken', $payToken)->update([
                'status' => 3,
                'callback_message' => $message,
                'callback_status' => $status,
                'balance_before_trans' => $soldeDistributeur,
                'balance_after_trans' => floatval($soldeDistributeur) + floatval($montant_calcul_solde),

                'balance_before_trans_sd' => $soldeSousDistributeur,
                'balance_after_trans_sd' => floatval($soldeSousDistributeur) + floatval($montant_calcul_solde),

                'balance_before_trans_agent' => $soldeUtilisateur,
                'balance_after_trans_agent' => floatval($soldeUtilisateur) + floatval($montant_calcul_solde),
                'fees' => 0,
                'tva' => 0,
                'taxe_fees' => 0,
                'commission' => 0,
                'taxe_commission' => 0,

                'date_end_trans' => Carbon::now(),
                'updated_at' => now(),
                'response_call_back_status' => 1,

            ]);
            //on met à jour le solde de l'utilisateur

            $utilisateur->update([
                'balance_before_trans' => $soldeUtilisateur,
                'balance_after_trans' => floatval($soldeUtilisateur) + floatval($montant_calcul_solde),
                'amount_last_trans' => $montant_calcul_solde,
                'date_last_trans' => Carbon::now(),
                'fees' => 0,
                'taxe' => 0,
                'last_beneficiary' => $customer_number,
                'id_last_service' => $service_id,
                'updated_at' => now(),
            ]);
            //on met à jour le solde du sous distributeur auquel appartient l'utilisateur

            $sousDistributeur->update([
                'balance_before_trans' => $soldeSousDistributeur,
                'balance_after_trans' => floatval($soldeSousDistributeur) + floatval($montant_calcul_solde),
                'amount_last_trans' => $montant_calcul_solde,
                'date_last_trans' => Carbon::now(),
                'fees' => 0,
                'taxe' => 0,
                'last_beneficiary' => $customer_number,
                'id_last_service' => $service_id,
                'updated_at' => now(),
            ]);
            //on met à jour le solde du distributeur auquel appartient le grossiste de l'utilisateur

            $Distributeur->update([
                'balance_before_trans' => $soldeDistributeur,
                'balance_after_trans' => floatval($soldeDistributeur) + floatval($montant_calcul_solde),
                'amount_last_trans' => $montant_calcul_solde,
                'date_last_trans' => Carbon::now(),
                'fees' => 0,
                'taxe' => 0,
                'last_beneficiary' => $customer_number,
                'id_last_service' => $service_id,
                'updated_at' => now(),
            ]);

            //On déclenche le push notification

            $title = 'Kiaboo';
            $subtitle = 'Kiaboo - Dépôt OM';

            //$PushSMS = new ApiSendNotificationPushController();
            //$sendNotification = $PushSMS->SendPushNotification($idDevice, $title, $message);
        }
    }

    public function callBackOM_TachePlanifiee()
    {
        $ApiOM = new ApiOrangeMoney();
        //On genere le token Access
        $response = $ApiOM->OM_GetTokenAccess();
        $dataAcessToken = json_decode($response->getContent());
        $codeAccessToken = $dataAcessToken->code;

        if ($codeAccessToken != 200) {
            return response()->json([
                'code' => $codeAccessToken,
                'error' => 'error',
            ]);
        }
        $accessToken = $dataAcessToken->accessToken;

        $transaction = Transaction::where('status', 2)->where('service_id', 6)->get();

        if (! is_null($transaction) || $transaction->count() > 0) {
            foreach ($transaction as $value) {
                $payToken = $value->paytoken;

                $responseCheckStatus = $ApiOM->OM_CashOut_Check($accessToken, $payToken);
                $dataCheckStatus = json_decode($responseCheckStatus->getContent());

                $status = $dataCheckStatus->status;
                $message = $dataCheckStatus->message;

                $idDevice = $value->notificationdevice;
                $user_id = $value->user_id;
                $amount = $value->amount;
                $customer_number = $value->customer_number;
                $service_id = $value->service_id;
                $reference_partenaire = $value->reference_partenaire;
                $resultat = $this->CallBackOM($payToken, $status, $message, $idDevice, $user_id, $amount, $customer_number, $service_id, $reference_partenaire);
            }
        }
    }
}
