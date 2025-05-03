<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'reference_partenaire',
        'date_transaction',
        'type_transaction_id',
        'service_id',
        'balance_before',
        'balance_after_payment',
        'balance_before_payment',
        'debit',
        'credit',
        'balance_after',
        'charge',
        'commission',
        'commission_filiale',
        'commission_rembourse',
        'commission_distributeur',
        'commission_distributeur_rembourse',
        'commission_agent',
        'commission_agent_rembourse',
        'commission_agent_rembourse_date',
        'commission_distributeur_rembourse_date',
        'ref_remb_com_agent',
        'ref_remb_com_distributeur',
        'paiement_agent_rembourse',
        'ref_remb_paiement_agent',
        'paiement_agent_rembourse_date',
        'charge',
        'charge_kiaboo',
        'charge_distributeur',
        'charge_agent',
        'charge_rembourse',
        'charge_distributeur_rembourse',
        'charge_agent_rembourse',
        'charge_agent_rembourse_date',
        'charge_distributeur_rembourse_date',
        'ref_remb_charge_agent',
        'ref_remb_charge_distributeur',

        'status',
        'description',
        'paytoken',
        'device_notification',
        'created_by',
        'updated_by',
        'countrie_id',
        'distributeur_id',
        'source',
        'fichier',
        'customer_phone',
        'date_end_trans',
        'distributeur_id',
        'agent_id',
        'message',
        'date_operation',
        'heure_operation',
        'moyen_payment',
        'reference_trans_carte',
        'balance_before_partenaire',
        'balance_after_partenaire',
        'status_cancel',
        'date_cancel',
        'cancel_by',
        'transaction_cancel_id',
        'description_cancel',
        'callback_response',
        'terminaison',
        'latitude',
        'longitude',
        'place',
        'marchand_transaction_id',
        'fees_collecte',
        'fees_kiaboo',
        'fees_partenaire_service',
        'marchand_amount',
        'application',
        'version',
        'api_response',

    ];

    public function auteur(){
        return $this->belongsTo(User::class, 'source', 'id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function distributeur()
    {
        return $this->belongsTo(Distributeur::class, 'distributeur_id', 'id');
    }
}
