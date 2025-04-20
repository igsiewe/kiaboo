<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_type_service',
        'status',
        'created_by',
        'updated_by',
    ];
}
