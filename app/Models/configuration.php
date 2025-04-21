<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class configuration extends Model
{
    use HasFactory;
    protected $fillable = [
        'lien_politique',
        'lien_cgu',
        'lien_mention',
        'lien_appstore',
        'lien_playstore',
        'telephone_support',
        'email_support',
        'message_parrainage',
        'status',
    ];
}
