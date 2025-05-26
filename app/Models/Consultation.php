<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;

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

    protected function puDefault(): Attribute
    {
        return Attribute::make(
            get: function($value, array $attributes) {
                if (request()->header('prise_en_charge') && $priseEnCharge = PriseEnCharge::find(request()->header('prise_en_charge'))) {
                    $assureur = $this->assureurs()->where('assureurs.id', $priseEnCharge->assureur_id)->first();

                    if ($assureur) {
                        $value = $assureur->pivot->pu;
                    }
                }
                return $value;
            },
            set: fn($value) => $value,
        );
    }

    /**
     * Relation avec le type de consultation.
     */
    public function assureurs(): MorphToMany
    {
        return $this->morphToMany(Assureur::class, 'assurable')
            ->withPivot(['pu']);
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
