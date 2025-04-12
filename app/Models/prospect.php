<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class prospect extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'surname',
        'phone',
        'email',
        'quartier_id',
        'type_piece',
        'numero_piece',
        'ville_piece_id',
        'adresse',
        'photo_verso',
        'photo_recto',
        'status',
        'validated_by',
        'validated_at',
    ];
}
