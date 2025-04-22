<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class prospect extends Model
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'genre',
        'name',
        'surname',
        'phone',
        'phone_court',
        'email',
        'quartier_id',
        'type_piece',
        'numero_piece',
        'date_validite',
        'ville_piece_id',
        'adresse',
        'photo_verso',
        'photo_recto',
        'status',
        'password',
        'code_parrainage',
        'optin',
        'validated_by',
        'validated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function ville_piece(){
        return $this->belongsTo(Ville::class, 'ville_piece_id','id');
    }

    public function quartier(){
        return $this->belongsTo(quartier::class);
    }
}
