<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_type_user',
        'status',
      //  'created_by',
      //  'updated_by',
    ];
}
