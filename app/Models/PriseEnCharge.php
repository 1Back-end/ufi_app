<?php

namespace App\Models;

use App\Enums\StateFacture;
use Illuminate\Database\Eloquent\Builder;
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


    public function scopeFilterFactureInProgress(Builder $query, $startDate, $endDate, $assurance, bool $latestFacture = false): Builder
    {
        return $query->with([
            'assureur:id,nom',
            'prestations' => function ($query) use ($startDate, $endDate, $latestFacture) {
                $query->where('centre_id', request()->header('centre'))
                    ->whereHas('factures', function ($query) use ($startDate, $endDate, $latestFacture) {
                    $query->where('factures.type', 2)
                        ->where('factures.state', StateFacture::IN_PROGRESS->value)
                        ->when($latestFacture, function ($query) use ($startDate, $endDate) {
                            $query->whereDate('factures.date_fact', '<', $startDate);
                        }, function (Builder $query) use ($startDate, $endDate) {
                            $query->whereBetween('factures.date_fact', [$startDate, $endDate]);
                        });
                });
            },
            'prestations.factures' => function ($query) {
                $query->where('factures.type', 2);
            },
            'prestations.actes',
            'prestations.soins',
            'prestations.consultations',
            'client:id,nom_cli,prenom_cli,nomcomplet_client,ref_cli,date_naiss_cli',
            'prestations.priseCharge:id,assureur_id,taux_pc',
        ])
        ->when($assurance, function ($query) use ($assurance) {
            $query->whereHas('assureur', function ($query) use ($assurance) {
                $query->where('assureurs.id', $assurance);
            });
        })
        ->whereHas('prestations', function ($query) use ($startDate, $endDate, $latestFacture) {
            $query->where('centre_id', request()->header('centre'))
                ->whereHas('factures', function ($query) use ($startDate, $endDate, $latestFacture) {
                $query->where('factures.type', 2)
                    ->where('factures.state', StateFacture::IN_PROGRESS->value)
                    ->when($latestFacture, function ($query) use ($startDate, $endDate) {
                        $query->whereDate('factures.date_fact', '<', $startDate);
                    }, function (Builder $query) use ($startDate, $endDate) {
                        $query->whereBetween('factures.date_fact', [$startDate, $endDate]);
                    });
            });
        })
        ->select('prise_en_charges.*')
        ->selectSub(function ($query) use ($startDate, $endDate, $latestFacture) {
            $query->from('factures')
                ->join('prestations', 'prestations.id', '=', 'factures.prestation_id')
                ->where('factures.type', 2)
                ->where('factures.state', StateFacture::IN_PROGRESS->value)
                ->when($latestFacture, function ($query) use ($startDate, $endDate) {
                    $query->whereDate('factures.date_fact', '<', $startDate);
                }, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('factures.date_fact', [$startDate, $endDate]);
                })
                ->selectRaw('SUM(factures.amount_pc) / 100');
        }, 'total_amount');
    }


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
