<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientArchive extends Model
{
    use HasFactory;

    protected $table = 'patient_archives';

    protected $fillable = [
        'patient_id',
        'dossier_id',
        'number_order',
        'first_visit_at',
        'last_visit_at',
        'notes',
        'is_active',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relations
     */

    // Patient
    public function patient()
    {
        return $this->belongsTo(Client::class, 'patient_id');
    }

    // Dossier / Rendez-vous
    public function dossier()
    {
        return $this->belongsTo(RendezVous::class, 'dossier_id');
    }

    // Créé par
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Mis à jour par
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
