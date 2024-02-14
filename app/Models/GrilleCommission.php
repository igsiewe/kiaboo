<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrilleCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'model',
        'borne_inferieure',
        'boone_superieure',
        'tauxht',
        'montant',
        'service_id',
        'status',
        'created_by',
        'updated_by',
    ];
}
