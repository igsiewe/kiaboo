<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use App\Http\Enums\ServiceEnum;
use App\Http\Enums\TypeServiceEnum;
use App\Models\Commission;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr\Cast\Double;
use phpseclib3\Math\PrimeField\Integer;

class ApiCommissionController extends BaseController
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
    public function getCommissionByService($idService, $montant)
    {
       $commission=0;

        $takeValue = Commission::where('service_id', $idService)->where("status",1)->where('borne_min','<=', $montant)->where('borne_max','>=',$montant)->get();
        if($takeValue->count() > 0){
           if($takeValue->first()->type_commission == 'taux') {
               $commission= ($takeValue->first()->taux) * $montant;
               $com_agent=$commission * $takeValue->first()->part_agent;
               $com_distributeur=$commission * $takeValue->first()->part_distributeur;
               $com_kiaboo=$commission * $takeValue->first()->part_kiaboo;

           }else{
               $commission = $takeValue->first()->amount;
               $com_agent=$commission * $takeValue->first()->part_agent;
               $com_distributeur=$commission * $takeValue->first()->part_distributeur;
               $com_kiaboo=$commission * $takeValue->first()->part_kiaboo;
           }
           if(doubleval($commission) <=0){
               return response()->json([
                   "status"=>false,
                   "message"=>"Aucune commission n'est définie pour ce montant"
               ],404);
           } else {
               return response()->json([
                   "status" => true,
                   "commission_globale" => $commission,
                   "commission_agent" => $com_agent,
                   "commission_distributeur" => $com_distributeur,
                   "commission_kiaboo" => $com_kiaboo,
               ],200);
           }
        }else{
            return response()->json([
                "status"=>false,
                "message"=>"Aucune commission n'est définie pour ce montant"
            ],404);
        }

    }

    public function getChargeService($idTypeService, $montant)
    {
        $charge=0;

        $takeValue = Charge::where('type_service_id', $idTypeService)->where("status",1)->where('borne_min','<=', $montant)->where('borne_max','>=',$montant)->get();
        if($takeValue->count() > 0){
            if($takeValue->first()->type_charge== 'taux') {
                $commission= ($takeValue->first()->taux) * $montant;
                $com_agent=$commission * $takeValue->first()->part_agent;
                $com_distributeur=$commission * $takeValue->first()->part_distributeur;
                $com_kiaboo=$commission * $takeValue->first()->part_kiaboo;

            }else{
                $commission = $takeValue->first()->amount;
                $com_agent=$commission * $takeValue->first()->part_agent;
                $com_distributeur=$commission * $takeValue->first()->part_distributeur;
                $com_kiaboo=$commission * $takeValue->first()->part_kiaboo;
            }
            if(doubleval($commission) <=0){
                return response()->json([
                    "status"=>false,
                    "message"=>"Aucune commission n'est définie pour ce montant"
                ],404);
            } else {
                return response()->json([
                    "status" => true,
                    "commission_globale" => $commission,
                    "commission_agent" => $com_agent,
                    "commission_distributeur" => $com_distributeur,
                    "commission_kiaboo" => $com_kiaboo,
                ],200);
            }
        }else{
            return response()->json([
                "status"=>false,
                "message"=>"Aucune commission n'est définie pour ce montant"
            ],404);
        }

    }
    public function getFeesByService($idService, $montant)
    {
        $fees_global = 0;
        $takeValue = Commission::where('service_id', $idService)->where("status",1)->where('borne_min','<=', $montant)->where('borne_max','>=',$montant)->get();
        if($takeValue->count() > 0){
            if($takeValue->first()->type_commission == 'taux') {
                $fees_global = ($takeValue->first()->taux) * $montant;
            }else{
                return response()->json([
                    "status"=>false,
                    "message"=>"Les fees ont été mal définis. Il doivent être en taux"
                ],404);
            }
            if(doubleval($fees_global) <=0){
                return response()->json([
                    "status"=>false,
                    "message"=>"Aucune commission n'est définie pour ce montant"
                ],404);
            } else {
                return response()->json([
                    "status" => true,
                    "fees_globale" => $fees_global,
                ],200);
            }
        }else{
            return response()->json([
                "status"=>false,
                "message"=>"Aucun frais de service n'est définie pour ce montant"
            ],404);
        }

    }

    public function commissionAgentRembourse(){

        //Grouper le remboursement des commissions agent par ref_remb_com_agent
        $commission = DB::table('transactions')
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->select(DB::raw('ref_remb_com_agent as reference, DATE_FORMAT(commission_agent_rembourse_date,"%Y-%m-%d")  as date_remboursement, sum(commission_agent) as commission'))
            ->where('transactions.source', Auth::user()->id)
            ->where("transactions.commission_agent_rembourse",1)
            ->whereNotIn("type_services.id", [TypeServiceEnum::APPROVISIONNEMENT->value,TypeServiceEnum::REMBOURSEMENT->value])
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->where("transactions.ref_remb_com_agent","!=",null)
            ->groupBy('transactions.ref_remb_com_agent','date_remboursement')
            ->get();


        if($commission->count() > 0) {
            return response()->json([
                "status" => true,
                "total" => $commission->sum("commission"),
                "message"=>$commission->count()." trouvée(s)",
                "commissions" => $commission
            ],200);
        }else{
            return response()->json([
                "status" => false,
                "total"=>0,
                "message" => "Aucune remboursement de commission trouvé",
                "commissions"=>[]
            ],404);
        }

    }

    public function commissionAgentRembourseFiltre(Request $request){

        $validator = Validator::make($request->all(), [
            'startDate' => 'required|string',
            'endDate' => 'required|string',
        ]);

        $startDate = Carbon::createFromFormat('d/m/Y', $request->startDate)->format('Y-m-d');
        $endDate = Carbon::createFromFormat('d/m/Y', $request->endDate)->format('Y-m-d');

        //Grouper le remboursement des commissions agent par ref_remb_com_agent
        $commission = DB::table('transactions')
            ->join("services","services.id","transactions.service_id")
            ->join("type_services", "type_services.id","services.type_service_id")
            ->where('transactions.source', Auth::user()->id)
            ->where("transactions.commission_agent_rembourse",1)
            ->where("transactions.date_transaction",">=",$startDate.' 00:00:00')
            ->where("transactions.date_transaction","<=",$endDate.' 23:59:59')
            ->select(DB::raw('ref_remb_com_agent as reference, DATE_FORMAT(commission_agent_rembourse_date,"%Y-%m-%d")  as date_remboursement, sum(commission_agent) as commission'))

            ->whereNotIn("type_services.id", [TypeServiceEnum::APPROVISIONNEMENT->value,TypeServiceEnum::REMBOURSEMENT])
            ->where("transactions.fichier","agent")->where('transactions.status',1)
            ->where("transactions.ref_remb_com_agent","!=",null)
            ->groupBy('transactions.ref_remb_com_agent','date_remboursement')
            ->get();

        if($commission->count() > 0) {
            return response()->json([
                "status" => true,
                "total" => $commission->sum("commission"),
                "message"=>$commission->count()." trouvée(s)",
                "commissions" => $commission
            ],200);
        }else{
            return response()->json([
                "status" => false,
                "total"=>0,
                "message" => "Aucun remboursement de commission trouvée sur la période sélectionnée",
                "commissio,s"=>[]
            ],404);
        }

    }

    public function commissionAgentNonRembourse(){
        $commission =Transaction::where("commission_agent_rembourse",0)->where('source', Auth::user()->id)->where("fichier","agent")->where('transactions.status',1)->get();
        if($commission->count() > 0) {
            return response()->json([
                "status" => true,
                "total" => $commission->sum("commission"),
                "commission" => $commission
            ]);
        }else{
            return response()->json([
                "status" => false,
                "message" => "Aucune commission n'est définie pour ce montant"
            ]);
        }
    }

    public function setRemboursementCommission(Request $request){
        $commission =Transaction::where('source', Auth::user()->id)->where("commission_agent_rembourse",0)
            ->join("services","services.id","transactions.service_id")
            ->where('transactions.status',1)->where("transactions.fichier","agent")
            ->whereIn("services.type_service_id",[TypeServiceEnum::ENVOI->value,TypeServiceEnum::RETRAIT->value,TypeServiceEnum::FACTURE->value]);
        $montantCommission=0;
        if($commission->count()>0){
            $montantCommission = $commission->sum("commission_agent");
            $date = Carbon::now();
            $reference = "RB".Carbon::now()->format('ymd').".".Carbon::now()->format('His').".".$this->genererChaineAleatoire(1)."".$this->GenereRang();
            try{
                DB::beginTransaction();
                $rembourse = $commission->update([
                    "commission_agent_rembourse"=>1,
                    "commission_agent_rembourse_date"=>$date,
                    "ref_remb_com_agent"=>$reference,
                ]);

                    $balanceAfter=doubleval(Auth::user()->balance_after) + doubleval($montantCommission);
                    $balanceBefore=Auth::user()->balance_after;
                    //Creation d'une ligne de credit dans la table transacton (historique de l'operation)
                    $Transaction= Transaction::create([
                        'reference'=>$reference,
                        'date_transaction'=>$date,
                        'service_id'=>ServiceEnum::PAIEMENT_COMMISSION->value,
                        'balance_before'=>$balanceBefore,
                        'balance_after'=>$balanceAfter,
                        'debit'=>0,
                        'credit'=>$montantCommission,
                        'status'=>1, //Initiate
                        'created_by'=>Auth::user()->id,
                        'created_at'=>$date,
                        'countrie_id'=>Auth::user()->countrie_id,
                        'source'=>Auth::user()->id,
                        'fichier'=>"agent",
                        'updated_by'=>Auth::user()->id,
                        'customer_phone'=>"679962015",
                        'description'=>'SUCCESSFULL',
                        'date_operation'=>date('Y-m-d'),
                        'heure_operation'=>date('H:i:s'),
                        'reference_partenaire'=>$reference,
                        'paytoken'=>$reference,
                        'date_end_trans'=>Carbon::now(),
                        'message'=>$reference,
                        'commission'=>0,
                        'commission_filiale'=>0,
                        'commission_agent'=>0,
                        'commission_distributeur'=>0,
                        "commission_agent_rembourse"=>1,
                        "commission_agent_rembourse_date"=>$date,
                       // "ref_remb_com_agent"=>$reference,
                    ]);
                    //Mise à jour du solde de l'agent
                    $updateSoldeCommissionAgent = User::where("id",Auth::user()->id)->update([
                        "total_commission"=>0,
                        "balance_after" =>$balanceAfter,
                        "last_amount"=>$montantCommission,
                        "balance_before"=>$balanceBefore,
                        "date_last_transaction"=>$date,
                        "user_last_transaction_id"=>Auth::user()->id,
                        "reference_last_transaction"=>$reference,
                        "remember_token"=>$reference,
                        "last_service_id"=>ServiceEnum::PAIEMENT_COMMISSION->value

                    ]);

                $commissions = DB::table('transactions')
                    ->join("services","services.id","transactions.service_id")
                    ->join("type_services", "type_services.id","services.type_service_id")
                    ->select(DB::raw('ref_remb_com_agent as reference, DATE_FORMAT(commission_agent_rembourse_date,"%Y-%m-%d")  as date_remboursement, sum(commission_agent) as commission'))
                    ->where('transactions.source', Auth::user()->id)
                    ->where("transactions.commission_agent_rembourse",1)
                    ->whereNotIn("type_services.id", [TypeServiceEnum::APPROVISIONNEMENT->value,TypeServiceEnum::REMBOURSEMENT])
                    ->where("transactions.fichier","agent")->where('transactions.status',1)
                    ->groupBy('transactions.ref_remb_com_agent','date_remboursement')
                    ->get();

                $user = DB::table("users")->join("quartiers", "users.quartier_id", "=", "quartiers.id")
                    ->join("villes", "quartiers.ville_id", "=", "villes.id")
                    ->where('users.id', Auth::user()->id)
                    ->select('users.id', 'users.name', 'users.surname', 'users.telephone', 'users.login', 'users.email','users.balance_before', 'users.balance_after','users.total_commission', 'users.last_amount','users.sous_distributeur_id','users.date_last_transaction','users.moncodeparrainage','quartiers.name_quartier as quartier','villes.name_ville as ville','users.adresse','users.quartier_id','quartiers.ville_id','users.qr_code','users.total_fees','users.total_paiement')->first();

                $transactions = DB::table('transactions')
                    ->join('services', 'transactions.service_id', '=', 'services.id')
                    ->join('type_services', 'services.type_service_id', '=', 'type_services.id')
                    ->select('transactions.id','transactions.reference as reference','transactions.paytoken','transactions.reference_partenaire','transactions.date_transaction','transactions.debit','transactions.credit' ,'transactions.customer_phone','transactions.commission_agent as commission','transactions.balance_before','transactions.balance_after' ,'transactions.status','transactions.service_id','services.name_service','services.logo_service','type_services.name_type_service','type_services.id as type_service_id','transactions.date_operation', 'transactions.heure_operation','transactions.commission_agent_rembourse as commission_agent','transactions.fees')
                    ->where("fichier","agent")
                    ->where("source",Auth::user()->id)
                    ->where('transactions.status',1)
                    ->orderBy('transactions.date_transaction', 'desc')
                    ->limit(5)
                    ->get();

                   $services = Service::all();

                    DB::commit();
                    $title = "Transaction en succès";
                    $message = "Vos commissions (" . $montantCommission . " F CFA) ont été créditées avec succès à votre compte principal. Votre nouveau solde s'élève à ".$balanceAfter." F CFA";
                    $appNotification = new ApiNotification();
                    $envoiNotification = $appNotification->SendPushNotificationCallBack($request->deviceId, $title, $message);

                    return response()->json([
                        "status"=>true,
                        "message"=>"Remboursement effectué avec succès",
                        "total"=>$montantCommission,
                        "commissions"=>$commissions,
                        "user"=>$user,
                        "transactions"=>$transactions,
                        "services"=>$services
                    ],200);

            }catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => "Exception : Une exception a été détectée, veuillez contacter votre superviseur si le problème persiste", //'Une erreur innatendue s\est produite. Si le problème persiste, veuillez contacter votre support.',
                ], 500);
            }

        }else{
            return response()->json([
                "status"=>false,
                "message"=>"Aucun remboursement possible trouvé",
            ],404);
        }

    }
}
