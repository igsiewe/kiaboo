<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Commission extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_id',
        'distributeur_id',
        'borne_min',
        'borne_max',
        'amount',
        'taux',
        'part_agent',
        'part_distributeur',
        'part_kiaboo',
        'part_partenaire_service',
        'type_commission',
        'status',
        'created_by',
        'updated_by',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
