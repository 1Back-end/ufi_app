<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'prestation_id',
        'element_paillasse_id',
        'groupe_population_id',
        'result_machine',
        'result_client',
        'show',
    ];

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    public function elementPaillasse(): BelongsTo
    {
        return $this->belongsTo(ElementPaillasse::class, 'element_paillasse_id');
    }

    public function groupePopulation(): BelongsTo
    {
        return $this->belongsTo(GroupePopulation::class, 'groupe_population_id');
    }
}
