<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestationable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'prestation_id', 'prestationable_id', 'prestationable_type', 'remise', 'quantity',
        'date_rdv', 'date_rdv_end', 'nbr_days', 'type_salle', 'honoraire', 'created_at', 'updated_at',
        'amount_regulate', 'pu', 'b', 'k_modulateur', 'prelevements', 'status_examen',
    ];

    protected $casts = [
        'prelevements' => 'array',
    ];

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }
}
