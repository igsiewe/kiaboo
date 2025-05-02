<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'surname',
        'telephone',
        'email',
        'login',
        'password',
        'codepin',
        'type_user_id',
        'countrie_id',
        'balance_before',
        'balance_after',
        'total_commission',
        'last_amount',
        'last_transaction_id',
        'date_last_transaction',
        'distributeur_id',
        'optin',
        'codeparrainage',
        'moncodeparrainage',
        'seuilapprovisionnement',
        'updated_by',
        'created_by',
        'expires_at',
        "reference_last_transaction",
        "last_service_id",
        "countrie",
        "ville_id", //Va etre enlever
        "quartier_id",
        'quartier',
        'adresse',
        "last_connexion",
        "numcni",
        "datecni",
        "deleted_at",
        "deleted_by",
        "status_delete",
        "application",
        'sum_payment',
        'sum_refund',
        'version',
        'view',
        'statut_code_parrainage',
        'qr_code',
        'balance_after_payment',
        'balance_before_payment',
    ];

    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }

    public function distributeur(){
        return $this->belongsTo(Distributeur::class, 'distributeur_id');
    }

    public function transactions(){
        return $this->hasMany(Transaction::class,'agent_id');
    }

    public function typeUser(){
        return $this->belongsTo(TypeUser::class,'type_user_id','id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function tokenExpired()
    {

        if ( Carbon::now() >  Carbon::parse($this->attributes['expires_at'])  ) {
            return true; //On genere un nouveau token (date expirée)
        }
        return false;
    }

}
