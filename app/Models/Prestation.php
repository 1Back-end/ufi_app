<?php

namespace App\Models;

use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Enums\TypePrestation;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;

class Prestation extends Model
{
    use HasFactory, UpdatingUser;

    protected $fillable = [
        'prise_charge_id',
        'client_id',
        'consultant_id',
        'payable_by',
        'programmation_date',
        'created_by',
        'updated_by',
        'type',
        'regulated',
        'centre_id',
    ];

    protected function casts(): array
    {
        return [
            'payable' => 'boolean',
            'programmation_date' => 'datetime',
        ];
    }

    protected $appends = [
        'type_label',
        'paid',
        'can_update'
    ];

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn ()  => TypePrestation::label($this->type),
        );
    }

    protected function paid(): Attribute 
    {
        return Attribute::make(
            get: function() {
                if($this->payable_by) return true;

                $facture = $this->factures()
                    ->where('type', 2)
                    ->first();

                if ($facture) {
                    return $facture->amount_client == $facture->regulations->sum('amount');
                }

                return false;
            }
        );
    }

    protected function canUpdate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $facture = $this->factures()->where('factures.type', 2)->first();
                if($facture) {
                    return $facture->regulations()->where('regulations.state', StatusRegulation::ACTIVE->value)->count() == 0;
                }

                return true;
            }
        );
    }


    public function centre() {
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class, 'prestation_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function payableBy(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'payable_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function priseCharge(): BelongsTo
    {
        return $this->belongsTo(PriseEnCharge::class, 'prise_charge_id');
    }

    public function actes(): MorphToMany
    {
        return $this->morphedByMany(Acte::class, 'prestationable')
            ->withPivot(['remise', 'quantity', 'date_rdv', 'date_rdv_end'])
            ->withTimestamps();
    }
}
