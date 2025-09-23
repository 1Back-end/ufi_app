<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantDisponibilite extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'jour',
        'heure_debut',
        'heure_fin',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'heure_debut' => 'datetime:H:i',
        'heure_fin'   => 'datetime:H:i',
    ];

    /**
     * Relations
     */
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function createBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor pour afficher la plage horaire formatée
     */
    public function getPlageHoraireAttribute(): string
    {
        return $this->heure_debut->format('H:i') . ' - ' . $this->heure_fin->format('H:i');
    }

    /**
     * Accessor pour récupérer le nom du jour à partir de l'entier
     */
    public function getJourNomAttribute(): string
    {
        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return $jours[$this->jour] ?? 'Inconnu';
    }
}
