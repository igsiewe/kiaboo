<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApproDistributeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'reference_validation',
        'date_operation',
        'date_validation',
        'amount',
        'balance_after',
        'balance_before',
        'distributeur_id',
        'status',
        'description',
        'created_by',
        'updated_by',
        'validated_by',
        'rejected_by',
        'date_reject',
        'countrie_id',
    ];

    public function distributeur()
    {
        return $this->belongsTo(Distributeur::class);
    }

    public function validatedBy(){
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function createdBy(){
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rejectedBy(){
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
