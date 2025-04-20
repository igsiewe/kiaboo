<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    use HasFactory;
    protected $fillable = [
        'name_ville',
        'region_id',
        'status',
        'created_by',
        'updated_by',
    ];
}
