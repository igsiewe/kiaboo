<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class quartier extends Model
{
    use HasFactory;
    protected $fillable = [
        'name_quartier',
        'ville_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function prospects(){
        return $this->hasMany(prospect::class);
    }

    public function ville(){
        return $this->belongsTo(ville::class);
    }
}
