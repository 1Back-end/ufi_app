<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'typeconsultation_id',
        'pu_nonassure',
        'pu_assure',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    /**
     * Relation avec le type de consultation.
     */
    public function typeconsultation()
    {
        return $this->belongsTo(Typeconsultation::class);
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
