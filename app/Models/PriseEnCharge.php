<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriseEnCharge extends Model
{
    protected $fillable = [
        'assureurs_id',
        'quotations_id',
        'date',
        'date_debut',
        'date_fin',
        'clients_id',
        'taux_pc',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    protected $casts = [
        'date' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_deleted' => 'boolean',
        'taux_pc' => 'float',
    ];

    // Relations
    public function assureur()
    {
        return $this->belongsTo(Assureur::class, 'assureurs_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotations_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'clients_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function prestations(): HasMany
    {
        return $this->hasMany(Prestation::class);
    }
}
