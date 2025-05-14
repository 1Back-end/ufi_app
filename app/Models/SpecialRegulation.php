<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SpecialRegulation extends Model
{
    protected $fillable = [
        'regulation_id',
        'regulation_type',
        'start_date',
        'end_date',
        'amount',
        'regulation_method_id',
        'number_piece',
        'date_piece',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'date_piece' => 'date',
        ];
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    /**
     * @return BelongsTo
     */
    public function regulationMethod(): BelongsTo
    {
        return $this->belongsTo(RegulationMethod::class, 'regulation_method_id');
    }

    /**
     * @return MorphTo
     */
    public function assureur(): MorphTo
    {
        return $this->morphTo(Assureur::class, 'regulation');
    }

    /**
     * @return MorphTo
     */
    public function client(): MorphTo
    {
        return $this->morphTo(Client::class, 'regulation');
    }
}
