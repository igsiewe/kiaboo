<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    use HasFactory;
    protected $fillable = [
        'version',
        'date_start',
        'date_end',
        'status',
        'url',
        'description',
        'created_by',
        'updated_by',
    ];
}
