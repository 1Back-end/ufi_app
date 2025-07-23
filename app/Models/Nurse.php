<?php

namespace App\Models;

use App\Models\Trait\CreateDefaultUser;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nurse extends Model
{
    use HasFactory,UpdatingUser, CreateDefaultUser;

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
        'user_id'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
    public function misesEnObservation()
    {
        return $this->belongsToMany(
            OpsTblMiseEnObservationHospitalisation::class,
            'infirmiere_mise_observation',
            'infirmiere_id',
            'mise_observation_id'
        );
    }
}
