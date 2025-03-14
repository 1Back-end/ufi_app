<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_profile', 'status_profile', 'description_profile',

    ];

    protected function casts(): array
    {
        return [
        ];
    }

    public function droits(): BelongsToMany
    {
        return $this->belongsToMany(Droit::class,'profile_droit')->withPivot(['date_creation_profile_droit']);
    }
}
