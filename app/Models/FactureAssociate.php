<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FactureAssociate extends Model
{
    public $timestamps = false;

    protected $table = 'facture_associates';

    protected $fillable = [
        'facturable',
        'start_date',
        'end_date',
        'code',
        'amount',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'date' => 'datetime',
        ];
    }

    public function assurance(): MorphTo
    {
        return $this->morphTo(Assureur::class, 'facturable');
    }

    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
