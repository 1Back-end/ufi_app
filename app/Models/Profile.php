<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom_profile', 'status_profile', 'date_creation_profile', 'description_profile',

    ];

    protected function casts()
    {
        return [
            'description_profile' => 'timestamp',
        ];
    }

    public function droits()
    {
        return $this->belongsToMany(Droit::class,'profile_droit')->withPivot(['date_creation_profile_droit']);
    }
}
