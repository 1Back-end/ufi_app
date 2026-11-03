<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hopital extends Model
{
    use HasFactory, SoftDeletes;

    // Nom de la table en base de données
    protected $table = 'consultants_hospitals';

    protected $fillable = [
        'nom_hopi',
        'Abbreviation_hopi',
        'addresse_hopi',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
