<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pays extends Model
{
    use HasFactory;
    protected $fillable = [
        'country',
        'code',
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
