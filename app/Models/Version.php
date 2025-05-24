<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    use HasFactory;
    protected $fillable = [
        'version', //A supprimer
        'date_start',
        'date_end',
        'status',
        'url', //A supprimer
        'description',
        'created_by',
        'updated_by',
        'version_ios',
        'lien_appstore',
        'version_android',
        'lien_playstore'
    ];
}
