<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        //Générer heure aléatoire au format H:i:s
        $h=rand(0,23);
        $i=rand(0,59);
        $s=rand(0,59);
        $heure=$h.':'.$i.':'.$s;
        $telephone = '69'.rand(1000000,9999999);
        $date = Carbon::now()->subMonths(rand(1, 10));
      //  $heure=Carbon::now()->subDays(rand(1, 20))->format('H:i:s');
        $service=rand(4,7);
        $commission=rand(100,1100);
        $iduser=rand(23,8286);
        $montant=rand(1000,9999)*10;
        return [
            //Transactions
            'reference'=>Str::random(10),
            'reference_partenaire'=>Str::random(10),
            'date_transaction'=>$date,
            // 'type_transaction_id',
            'service_id'=>$service,
            'balance_before'=>rand(100000,999999),
            'debit'=>$service==4 || $service==6?$montant:0,
            'credit'=>$service==5 || $service==7?$montant:0,
            'balance_after'=>rand(100000,999999),
            'fees'=>0,
            'commission'=>$commission,
            'commission_filiale'=>$commission*0.25,
            'commission_rembourse'=>0,
            'commission_distributeur'=>$commission*0.25,
            'commission_distributeur_rembourse'=>0,
            'commission_agent'=>$commission*0.5,
            'commission_agent_rembourse'=>0,
            'commission_agent_rembourse_date'=>null,
            'commission_distributeur_rembourse_date'=>null,
            'ref_remb_com_agent'=>null,
            'ref_remb_com_distributeur'=>null,
            'status'=>1,
            'description'=>Str::random(10),
            'paytoken'=>Str::random(10),
            'device_notification'=>null,
            'created_by'=>$iduser,
            'updated_by'=>$iduser,
            'countrie_id'=>1,
            'distributeur_id'=>null,
            'source'=>$iduser,
            'fichier'=>'agent',
            'customer_phone'=>$telephone,
            'date_end_trans'=>$date,
            'agent_id'=>$iduser,
            'message'=>'',
            'date_operation'=>$date,
            'heure_operation'=>$heure,
            'moyen_payment'=>null,
            'reference_trans_carte'=>null,
            'balance_before_partenaire'=>0,
            'balance_after_partenaire'=>0,
            'status_cancel'=>0,
            'date_cancel'=>null,
            'cancel_by'=>null,
            'transaction_cancel_id'=>null,
            'description_cancel'=>null,
            'created_at'=>$date,
            'updated_at'=>$date,
        ];
    }
}
