<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Droit extends Model
{
    protected $fillable = [
        'nom_droit',
        'status_droit'

    ];


    public function profiles()
    {
        return $this->belongsToMany(Profile::class, 'profile_droit')->withPivot(['date_creation_profile_droit']);
    }
    //
}
