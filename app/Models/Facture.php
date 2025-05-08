<?php

namespace App\Models;

use App\Enums\StateFacture;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facture extends Model
{
    use UpdatingUser;

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
        'contentieux'
    ];

    protected function casts(): array
    {
        return [
            'date_fact' => 'datetime',
            'state' => StateFacture::class,
        ];
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
