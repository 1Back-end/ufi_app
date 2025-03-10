<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;
    protected $fillable = [
        'profile_id', 'nom_utilisateur', 'mot_de_passe',
        'date_expiration_mot_passe', 'email_utilisateur',
        'status_utilisateur', 'date_creation_utilisateur',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function centres ()
    {
        return $this->belongsToMany(Centre::class);
    }
}
