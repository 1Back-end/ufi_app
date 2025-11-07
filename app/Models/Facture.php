<?php

namespace App\Models;

use App\Enums\StateFacture;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facture extends Model
{
    use UpdatingUser, SoftDeletes;

    protected $fillable = [
        'code',
        'prestation_id',
        'created_by',
        'updated_by',
        'date_fact',
        'amount',
        'amount_pc',
        'amount_remise',
        'type',
        'sequence',
        'amount_client',
        'centre_id',
        'state',
        'contentieux',
        'amount_prelevement',
        'amount_prelevement_pc'
    ];

    protected function casts(): array
    {
        return [
            'date_fact' => 'datetime',
            'state' => StateFacture::class,
        ];
    }

    protected $appends = [
        'regulations_total',
        'regulations_total_except_particular',
        'amount_rest'
    ];

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    protected function amountPc(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    protected function amountRemise(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    protected function amountClient(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    protected function amountPrelevement(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    protected function amountPrelevementPc(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    protected function regulationsTotal(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $this->regulations()->sum('regulations.amount') / 100,
        );
    }

    protected function regulationsTotalExceptParticular(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $this->regulations()->where('regulations.particular', false)->where('regulations.state', 1)->sum('regulations.amount') / 100,
        );
    }

    protected function amountRest(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->state->value === StateFacture::IN_PROGRESS->value) {
                    return $this->amount - $this->amount_remise - $this->regulations_total_except_particular;
                }

                return 0;
            },
        );
    }

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function regulations()
    {
        return $this->hasMany(Regulation::class, 'facture_id');
    }
}
