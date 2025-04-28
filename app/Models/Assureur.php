<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Assureur extends Model

{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'nom_abrege',
        'adresse',
        'tel',
        'tel1',
        'code_quotation',
        'code_centre',
        'Reg_com',
        'num_com',
        'bp',
        'fax',
        'code_type',
        'code_main',
        'ref_assur',
        'email',
        'BM',
        'ref',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
        'ref_assur_principal'
    ];


    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'code_quotation');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'code_centre');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function assureurPrincipal()
    {
        return $this->belongsTo(Assureur::class, 'code_main');
    }
    //

    /**
     * @return MorphToMany
     */
    public function actes(): MorphToMany
    {
        return $this->morphedByMany(Acte::class, 'assurable')
            ->withTimestamps()
            ->withPivot(['k_modulateur', 'b']);
    }

    /**
     * @return MorphToMany
     */
    public function soins(): MorphToMany
    {
        return $this->morphedByMany(Soins::class, 'assurable')
            ->withTimestamps()
            ->withPivot(['pu']);
    }

    /**
     * @return MorphToMany
     */
    public function consultations(): MorphToMany
    {
        return $this->morphedByMany(Consultation::class, 'assurable')
            ->withTimestamps()
            ->withPivot(['pu']);
    }
}
