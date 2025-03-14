<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    use HasFactory;
    protected $fillable = [
        'profile_id',
        'nom_utilisateur',
        'password',
        'date_expiration_mot_passe',
        'email',
        'status_utilisateur',
    ];

    protected $casts = [
        'date_expiration_mot_passe' => 'date',
        'date_creation_utilisateur' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function centres(): BelongsToMany
    {
        return $this->belongsToMany(Centre::class, 'user_centre');
    }
}
