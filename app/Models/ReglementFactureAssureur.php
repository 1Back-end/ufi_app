<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReglementFactureAssureur extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reglement_factures_assureurs';

    protected $fillable = [
        'reglement_assurance_id',
        'facture_id',
        'montant_initial',
        'montant_assure',
        'montant_ir',
        'montant_exclu',
        'type_label',
        'created_by',
        'updated_by',
    ];

    /**
     * Cast des données
     */
    protected $casts = [
        'montant_initial' => 'decimal:2',
        'montant_assure' => 'decimal:2',
        'montant_ir' => 'decimal:2',
        'montant_exclu' => 'decimal:2',
    ];

    /**
     * Relation avec le règlement
     */
    public function reglement()
    {
        return $this->belongsTo(ReglementAssurance::class, 'reglement_assurance_id');
    }

    /**
     * Relation avec la facture
     */
    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }

    /**
     * Créé par
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Modifié par
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
