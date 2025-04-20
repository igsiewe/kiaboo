<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\Controller;
use App\Models\ApproDistributeur;
use App\Models\Distributeur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiStripe extends Controller
{

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
    public function validateTopUpDistributeurSkype($reference){

//        if(Auth::user()->type_user_id != UserRolesEnum::BACKOFFICE->value){
//            return redirect()->back()->with('error','You cannot authorize to perform this operation');
//        }
        $approDistributeur = ApproDistributeur::where('reference',$reference)->get();
        $payToken = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".V".$this->GenereRang();

        if($approDistributeur->count()==0){
            return redirect()->back()->with('error','Approvisionnement not found.');
        }

        if($approDistributeur->first()->status==1){
            return redirect()->back()->with('error','The approvsionnement '.$reference.' has already been validated');
        }
        $distributeur_id = $approDistributeur->first()->distributeur_id;

        try {
            DB::beginTransaction();
            //On met à jour le solde du distributeur
            $distributeur = Distributeur::where('id',$distributeur_id)->get();
            $balance_before = $distributeur->first()->balance_after;
            $balance_after = $distributeur->first()->balance_after + $approDistributeur->first()->amount;
            //On met à jour l'approvisionnement dans la table approvisionnement

            $activeApproDistributeur = DB::table("appro_distributeurs")->where('reference',$reference)->where("status",0)->update([
                'status' => 1,
                'updated_by' => Auth::user()->id,
                'validated_by' => Auth::user()->id,
                'date_validation'=> Carbon::now(),
                'reference_validation' => $payToken,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
            ]);

            if($activeApproDistributeur){

                $updateSoldeDistributeur = DB::table("distributeurs")->where('id',$distributeur_id)->update([
                    'balance_after' => $balance_after,
                    'balance_before' => $balance_before,
                    'last_amount' => $approDistributeur->first()->amount,
                    'date_last_transaction' => Carbon::now(),
                    'last_transaction_id' => $approDistributeur->first()->id,
                    'last_service_id' => 1, //1 = Approvisionnement
                    'user_last_transaction_id' => Auth::user()->id,
                    'reference_last_transaction'=>$payToken,
                    'updated_by' => Auth::user()->id,
                    'created_by' => Auth::user()->id,
                ]);

                if($updateSoldeDistributeur){
                    //ON crée l'approvisionnement dans la table transactions
                    $transaction =DB::table("transactions")->insert([
                        'reference' => $payToken,
                        'reference_partenaire' => $approDistributeur->first()->reference,
                        'date_transaction' => Carbon::now(),
                        'service_id' => 1, //1 = Approvisionnement
                        'distributeur_id' => $approDistributeur->first()->distributeur_id,
                        'credit' => $approDistributeur->first()->amount,
                        'debit' => 0,
                        'description'=>'SUCCESSFULL',
                        'balance_before' => $balance_before,
                        'balance_after' => $balance_after,
                        'status' => 1,
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                        'paytoken' => $payToken,
                        'countrie_id' => Auth::user()->countrie_id,
                        'created_at' => Carbon::now(),
                        'source'=>$distributeur_id,
                        'fichier' => 'distributeur',
                        'date_operation'=>date('Y-m-d'),
                        'heure_operation'=>date('H:i:s'),
                        'customer_phone'=>Auth::user()->telephone,
                        'date_end_trans'=>Carbon::now(),
                    ]);
                    DB::commit();
                    return redirect()->back()->with('success', 'Approvisionnement validated successfully');
                }else{
                    DB::rollBack();
                    return redirect()->back()->with('error','Error on update balance distributor.');
                }

            }else{
                DB::rollBack();
                return redirect()->back()->with('error','Approvisionnement not updated.');
            }
        }
        catch (\Throwable $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return redirect()->back()->with('error', $message);
        }

    }

    public function initApproDistributeurSkype(Request $request)  {

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:50|max:500000',
            'reference'=> 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $distributeur = Distributeur::where('id',1)->where('status',1)->where('application',1); //Le distributeur est 1 = KIABOO
        if ($distributeur->count()==0) {

            return response()->json([
                'success' => false,
                'message' => "Opérationn non autorisée pour le moment. Veuillez essayer plus tard.",
            ], 404);
        }
        if($distributeur->first()->status == 0){
            return response()->json([
                'success' => false,
                'message' => "Opérationn non autorisée pour le moment. Veuillez essayer plus tard.",
            ], 404);
        }

       // $reference = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".I".$this->GenereRang();
        try {
            DB::beginTransaction();
            $approDistributeur = new ApproDistributeur();
            $approDistributeur->reference = $request->reference;
            $approDistributeur->date_operation = Carbon::now();
            $approDistributeur->distributeur_id = 1;//KIABOO $request->distributeur;
            $approDistributeur->amount = $request->amount;
            $approDistributeur->description = "Approvisionnement par carte Visa";// $request->description;
            $approDistributeur->status = 0;
            $approDistributeur->created_by = 20;
            $approDistributeur->updated_by = 20;
            $approDistributeur->countrie_id = 1;//
            $approDistributeur->save();
            DB::commit();
            $appro = ApproDistributeur::where("reference",$request->reference)->select("id","reference","date_operation","description","amount")->first();
            return response()->json([
                'success' => true,
                'code' => 200,
                'reference' => $request->reference,
                'approvisionnement'=>$appro,
            ],  200);

        } catch (\Throwable $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return response()->json([
                'success' => false,
                'code' => $e->getCode(),
                'reference' => $message,
            ],  $e->getCode());
        }

    }
}
