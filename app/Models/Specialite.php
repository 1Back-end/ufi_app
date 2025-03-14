<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialite extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_specialite',
        'create_by_specialite',
        'update_by_specialite',
    ];

    public function createBySpecialite()
    {
        return $this->belongsTo(User::class, 'create_by_specialite');
    }

    public function updateBySpecialite()
    {
        return $this->belongsTo(User::class, 'update_by_specialite');
    }
}
