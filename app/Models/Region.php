<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;
    protected $fillable = [
        'name_region',
        'countrie_id',
        'status',
        'created_by',
        'updated_by',
    ];
}
