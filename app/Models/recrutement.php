<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class recrutement extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'surname',
        'email',
        'telephone',
        'ville_id',
        'quartier',
        'adresse',
        'datecni',
        'numcni',
        'photo',
        'recto',
        'verso',
        'date_creation',
        'created_by',
        'updated_by',
        'status',

    ];

    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }
}
