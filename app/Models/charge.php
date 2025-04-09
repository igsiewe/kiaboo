<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class charge extends Model
{
    use HasFactory;
    protected $fillable = [
        'amount',
        'type_service_id',
        'status',
        'created_by',
        'updated_by',
    ];
}
