<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nurse extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_complet',
        'nom',
        'prenom',
        'email',
        'telephone',
        'matricule',
        'specialite',
        'adresse',
        'is_active',
        'created_by',
        'updated_by',
        'is_deleted',
    ];


    protected static function booted()
    {
        static::creating(function ($nurse) {
            $nurse->matricule = self::generateMatricule();
            $nurse->nom_complet = $nurse->nom . ' ' . $nurse->prenom;
        });

        static::updating(function ($nurse) {
            $nurse->nom_complet = $nurse->nom . ' ' . $nurse->prenom;
        });
    }

    public static function generateMatricule(): string
    {
        $last = self::orderBy('id', 'desc')->first();
        $lastNumber = $last && $last->matricule
            ? intval(substr($last->matricule, 4))
            : 0;

        return 'INF-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
