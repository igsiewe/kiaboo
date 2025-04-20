<?php

namespace App\Models;

use App\Http\Enums\UserRolesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distributeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_distributeur',
        'name_contact',
        'surname_contact',
        'phone',
        'email',
        'adresse',
        'plafond_alerte',
        'region_id',
        'status',
        'balance_before',
        'balance_after',
        'last_amount',
        'last_transaction_id',
        'reference_last_transaction',
        'date_last_transaction',
        'user_last_transaction_id',
        'created_by',
        'updated_by',
        'application',
    ];

    public function region(){
        return $this->belongsTo(Region::class,'region_id');
    }

    public function agents(){
        return $this->hasMany(User::class,'distributeur_id')->where("type_user_id",UserRolesEnum::AGENT->value);
    }

    public function transactions(){
        return $this->hasMany(Transaction::class,'distributeur_id');
    }
}
