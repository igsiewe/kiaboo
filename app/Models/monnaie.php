<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class monnaie extends Model
{
    use HasFactory;
    protected $fillable = [
        'libelle',
        'currency',
        'flag',
        'status',
        'encours',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];
}
