<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FactureAdjustmentHistory extends Model
{
    use HasFactory;

    protected $table = 'facture_adjustments_history';

    protected $fillable = [
        'facture_id',
        'prestation_id',
        'amount_insurance',
        'amount_insured',
        'payable_by_file_url',
        'description',
        'adjusted_by',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
