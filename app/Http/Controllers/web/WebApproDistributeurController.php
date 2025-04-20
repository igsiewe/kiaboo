<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\ApproDistributeur;
use App\Models\Distributeur;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class WebApproDistributeurController extends Controller
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
    public function getApproDistributor(){
        $listApprovisionnement = ApproDistributeur::with('distributeur', 'createdBy','validatedBy','rejectedBy');
        $listOperation  = DB::table('transactions')
            ->join("users","users.id","transactions.created_by")
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('users.login as auteur','transactions.id','transactions.reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission','transactions.commission_agent','transactions.commission_distributeur','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation')
            ->where("transactions.fichier","distributeur")
            ->where('transactions.status',1);
      //  $listdistributeurs = Distributeur::all()->sortBy("name_distributeur");
        $listdistributeurs = Distributeur::where('application',1)->orderBy('name_distributeur')->get();
        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $listApprovisionnement = $listApprovisionnement->where("distributeur_id",Auth::user()->distributeur_id);
            $listOperation = $listOperation->where("transactions.source",Auth::user()->distributeur_id);
            $listdistributeurs=[];
        }
        $listApprovisionnement = $listApprovisionnement->orderBy("created_at","desc")->get();
        $listOperation = $listOperation->orderByDesc('transactions.date_transaction')->get();
        $money ="F CFA";
        return view('pages.myrelaod.myrelaod', compact('listApprovisionnement','listOperation','money','listdistributeurs'));
    }

    public function listApprovisionnementFiltre(Request $request){

        $request->validate([
            "startDate" =>"required|date",
            "endDate" =>"required|date",
        ]);
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $result = Carbon::parse($endDate)->gte(Carbon::parse($startDate));
        if ($result==false){
            return redirect()->back()->withInput()->withErrors(['error' => 'La date de début doit être inférieure à la date de fin']);
        }
        $listApprovisionnement = ApproDistributeur::with('distributeur', 'createdBy','validatedBy','rejectedBy')
            ->whereDate('created_at', '>=', $startDate. " 00:00:00")
            ->whereDate('created_at', '<=', $endDate. " 23:59:59");

        $listOperation  = DB::table('transactions')
            ->join("users","users.id","transactions.created_by")
            ->join('services', 'transactions.service_id', '=', 'services.id')
            ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
            ->select('users.login as auteur','transactions.id','transactions.reference','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission','transactions.commission_agent','transactions.commission_distributeur','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation')
            ->whereDate('transactions.created_at', '>=', $startDate. " 00:00:00")
            ->whereDate('transactions.created_at', '<=', $endDate. " 23:59:59")
            ->where("transactions.fichier","distributeur")
            ->where('transactions.status',1);
       // $listdistributeurs = Distributeur::all();
        $listdistributeurs = Distributeur::where('application',1)->orderBy('name_distributeur')->get();
        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $listApprovisionnement = $listApprovisionnement->where("distributeur_id",Auth::user()->distributeur_id);
            $listOperation = $listOperation->where("transactions.source",Auth::user()->distributeur_id);
            $listdistributeurs = [];
        }
        if($request->status != null || $request->status != ""){
            $listApprovisionnement =  $listApprovisionnement->where("status",$request->status);
            $listOperation =  $listOperation->where("transactions.status",$request->status);
        }

        $listApprovisionnement = $listApprovisionnement->orderBy("created_at","desc")->get();
        $listOperation  =$listOperation->orderByDesc('transactions.date_transaction')->get();

        $money ="F CFA";
        return view('pages.myrelaod.myrelaod', compact('listApprovisionnement','listOperation','money','listdistributeurs'))->with(
            [
                "startDate" =>$startDate,
                "endDate" =>$endDate,
                "status" =>$request->status,
            ]
        );
    }

    public function CancelTopUpDistributeur($id){
//        if(Auth::user()->type_user_id !=UserRolesEnum::BACKOFFICE->value){
//            return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à effectuer cette opération');
//        }
        $approvisionnement = ApproDistributeur::find($id);
        if($approvisionnement->count()==0){
            return redirect()->back()->with('error', 'Une erreur s\'est produite');
        }

        if($approvisionnement->status==0){
            $approvisionnement->status = 2;
            $approvisionnement->rejected_by = Auth::user()->id;
            $approvisionnement->date_reject = Carbon::now();
            $approvisionnement->save();
            return redirect()->back()->with('success', 'Annulation effectuée avec succès');
        }else{
            return redirect()->back()->with('error', 'Impossible d\'annuler cette opération');
        }

    }

    public function validateTopUpDistributeur($reference){

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

    public function initApproDistributeur(Request $request)  {

//        if(Auth::user()->type_user_id != UserRolesEnum::FRONTOFFICE->value){
//            return redirect()->back()->with('error','You cannot authorize to perform this operation');
//        }
        $validator = Validator::make($request->all(), [
            'distributeur' => 'required|integer',
            'amount' => 'required|integer|min:100000|max:10000000',
        ]);
        if(Auth::user()->status == 0){
            return redirect()->back()->with('error','You cannot authorize to perform this operation');
        }
        if ($validator->fails()) {
            return redirect()->back()->with('error',$validator->errors()->first());
        }

        $distributeur = Distributeur::where('id',$request->distributeur)->where('status',1)->where('application',1);
        if ($distributeur->count()==0) {
            return redirect()->back()->with('error','Distributeur not found or not authorized.');
        }
        if($distributeur->first()->status == 0){
            return redirect()->back()->with('error','Distributeur is not active.');
        }



        $reference = "AP".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".I".$this->GenereRang();
        try {
            DB::beginTransaction();
            $approDistributeur = new ApproDistributeur();
            $approDistributeur->reference = $reference;
            $approDistributeur->date_operation = Carbon::now();
            $approDistributeur->distributeur_id = $request->distributeur;
            $approDistributeur->amount = $request->amount;
            $approDistributeur->description = $request->description;
            $approDistributeur->status = 0;
            $approDistributeur->created_by = Auth::user()->id;
            $approDistributeur->updated_by = Auth::user()->id;
            $approDistributeur->countrie_id = Auth::user()->countrie_id;
            $approDistributeur->save();
            DB::commit();
            return redirect()->back()->with('success','Approvisionnement initiated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return redirect()->back()->with('error', $message);
        }

    }

    public function getTopUpDetailDistributeur($id, $action){
        $money = "F CFA";
        $approvisionnement = ApproDistributeur::find($id);
        if($approvisionnement->count()==0){
            return redirect()->back()->with('error', 'Une erreur s\'est produite');
        }
        return view('pages.myrelaod.detail_topup', compact('approvisionnement','money','action'))->with('distributeur','createdBy');

    }
}
