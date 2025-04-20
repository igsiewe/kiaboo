<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Countrie extends Model
{
    use HasFactory;
    protected $fillable = [
        'name_country',
        'short_name',
        'phone_code',
        'flag',
        'status',
        'created_by',
        'updated_by',
    ];
}
