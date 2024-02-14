<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partenaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_partenaire',
        'short_name_partenaire',
        'logo_partenaire',
        'name_contact',
        'surname_contact',
        'phone',
        'email',
        'status',
        'countrie_id',
        'created_by',
        'updated_by',
    ];
}
