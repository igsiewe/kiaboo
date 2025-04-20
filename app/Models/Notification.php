<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = [
        'message',
        'date_start',
        'date_end',
        'status',
        'url',
        'created_by',
        'updated_by',
    ];
}
