<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Soins extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_soin_id',
        'pu',
        'pu_default',
        'name',
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

    // Relations

    public function type_soins()
    {
        return $this->belongsTo(TypeSoins::class, 'type_soin_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assureurs(): MorphToMany
    {
        return $this->morphToMany(Assureur::class, 'assurable')
            ->withPivot(['pu']);
    }
}
