<?php

namespace App\Models;

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
