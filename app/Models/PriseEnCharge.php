<?php

namespace App\Models;

use App\Enums\StateFacture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriseEnCharge extends Model
{
    protected $fillable = [
        'assureur_id',
        'quotation_id',
        'date',
        'code',
        'date_debut',
        'date_fin',
        'client_id',
        'taux_pc',
        'usage_unique',
        'created_by',
        'updated_by',
        'is_deleted',
        'used',
    ];

    protected $casts = [
        'date' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_deleted' => 'boolean',
        'taux_pc' => 'float',
        'used' => 'boolean'
    ];

    // Relations
    public function assureur()
    {
        return $this->belongsTo(Assureur::class, 'assureur_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
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
        return $this->hasMany(Prestation::class, 'prise_charge_id');
    }

    public function facturesInProgressDeType2()
    {
        return $this->hasManyThrough(
            Facture::class,
            Prestation::class,
            'prise_charge_id', // Foreign key on Prestation
            'prestation_id',      // Foreign key on Facture
            'id',                 // Local key on PriseEnCharge
            'id'                  // Local key on Prestation
        )->where('factures.type', 2)
        ->where('factures.state', StateFacture::IN_PROGRESS->value);
    }
}
