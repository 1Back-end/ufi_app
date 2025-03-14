<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hopital extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_hopi',
        'Abbreviation_hopi',
        'addresse_hopi',
        'create_by_hopi',
        'update_by_hopi',
    ];

    public function createByHopi()
    {
        return $this->belongsTo(User::class, 'create_by_hopi');
    }

    public function updateByHopi()
    {
        return $this->belongsTo(User::class, 'update_by_hopi');
    }
}
