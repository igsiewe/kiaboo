<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class charge extends Model
{
    use HasFactory;
    protected $fillable = [
        'borne_min',
        'borne_max',
        'amount',
        'type_service_id',
        'type_charge',
        'part_agent',
        'part_distributeur',
        'part_kiaboo',

        'status',
        'created_by',
        'updated_by',
    ];
}
