<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SousDistributeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_sous_distributeur',
        'name_contact',
        'surname_contact',
        'phone',
        'email',
        'zone_id',
        'distributeur_id',
        'status',
        'balance_before',
        'balance_after',
        'last_amount',
        'last_transaction_id',
        'date_last_transaction',
        'user_last_transaction_id',
        'reference_last_transaction',
        'created_by',
        'updated_by',
    ];
}
