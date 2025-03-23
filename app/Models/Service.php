<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_service',
        'short_name',
        'status',
        'partenaire_id',
        'type_service_id',
        'logo_service',
        'created_by',
        'updated_by',
        'display'
    ];

    public function typeService()
    {
        return $this->belongsTo(TypeService::class);
    }

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }
}
