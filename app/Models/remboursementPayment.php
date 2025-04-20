<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class remboursementPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference',
        'user_id',
        'date_demande',
        'amount',
        'description',
        'created_by',
        'updated_by',
        'date_validation',
        'motif_validation',
        'motif_rejet',
        'date_rejet',
        'status',
    ];
}
