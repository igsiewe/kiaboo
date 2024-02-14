<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parrainage extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'surname',
        'phone',
        'status',
        'date_subscribe',
        'bonus',
        'codeparrainage',
    ];
}
