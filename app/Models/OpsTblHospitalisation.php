<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class OpsTblHospitalisation extends Model
{
    use HasFactory;
    protected $table = 'ops_tbl_hospitalisation';



    protected $fillable = [
        'name',
        'pu',
        'pu_default',
        'description',
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

    // Relation avec l'utilisateur qui a créé
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation avec l'utilisateur qui a mis à jour
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function assureurs(): MorphToMany
    {
        return $this->morphToMany(Assureur::class, 'assurable')
            ->withPivot(['pu']);
    }
    //
}
