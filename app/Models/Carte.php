<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carte extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'number',
        'name',
        'cvv',
        'expire',
        'month',
        'year',
        'fourth_first_number',
        'fourth_last_number',
        'adresse',
        'ville',
        'boitepostale',
        'nom_facturation',
    ];
}
