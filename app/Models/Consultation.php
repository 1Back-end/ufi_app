<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'typeconsultation_id',
        'pu',
        'pu_default',
        'name',
        'validation_date',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    /**
     * Relation avec le type de consultation.
     */
    public function assurables()
    {
        return $this->morphMany(Assurable::class, 'assurable');
    }
    public function typeconsultation()
    {
        return $this->belongsTo(Typeconsultation::class);
    }
    public function Code_hopi()
    {
        return $this->belongsTo(Hopital::class);
    }

    /**
     * Utilisateur qui a créé l’enregistrement.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utilisateur qui a modifié l’enregistrement.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
