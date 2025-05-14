<?php

namespace App\Models;

use App\Enums\StatusRegulation;
use App\Enums\TypeRegulation;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Regulation extends Model
{
    use UpdatingUser;

    protected $fillable = [
        'regulation_method_id',
        'facture_id',
        'created_by',
        'updated_by',
        'amount',
        'date',
        'type',
        'comment',
        'reason',
        'state',
        'particular',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'state' => StatusRegulation::class,
            'type' => TypeRegulation::class,
            'particular' => 'boolean',
        ];
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    public function regulationMethod(): BelongsTo
    {
        return $this->belongsTo(RegulationMethod::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
