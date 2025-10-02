<?php

namespace App\Models;

use App\Enums\StateExamen;
use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Enums\TypePrestation;
use App\Models\Trait\UpdatingUser;
use App\Pivots\PrelevementsPivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use function Laravel\Prompts\search;

class Prestation extends Model
{
    use HasFactory, UpdatingUser;

    protected $fillable = [
        'prise_charge_id',
        'client_id',
        'consultant_id',
        'payable_by',
        'convention_id',
        'programmation_date',
        'created_by',
        'updated_by',
        'type',
        'regulated',
        'centre_id',
        'apply_prelevement'
    ];

    protected function casts(): array
    {
        return [
            'payable' => 'boolean',
            'programmation_date' => 'datetime',
            'type' => TypePrestation::class,
        ];
    }
    public function rendezVous()
    {
        return $this->hasMany(RendezVous::class);
    }

    protected $appends = [
        'type_label',
        'paid',
        'can_update',
        'associate_file',
        'associate_file_name',
        'state_examen',
        'prelevement',
        'last_prelevement',
    ];

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => TypePrestation::label($this->type),
        );
    }

    protected function associateFile(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $media = $this->medias()->where('name', 'prestations-client-associate')->first();
                if ($media) {
                    return Storage::disk($media->disk)->url($media->path . '/' . $media->filename);
                }
            },
        );
    }

    protected function associateFileName(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $media = $this->medias()->where('name', 'prestations-client-associate')->first();
                if ($media) {
                    return $media->filename;
                }
            },
        );
    }

    protected function stateExamen(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->examens()->count()) return -1;

                $elt = 0;
                $this->examens->each(function (Examen $examen) use (&$elt) {
                    $elt += $examen->elementPaillasses()->whereNull('element_paillasses_id')->count();
                });

                if ($this->examens()->wherePivotNull('prelevements')->count() == $this->examens()->count()) {
                    return 0; // return "Aucun prélèvement";
                } elseif (
                    $this->examens()->wherePivotNull('status_examen')->count() == $this->examens()->count()
                    && $this->examens()->wherePivotNull('prelevements')->count() > 0
                ) {
                    return 1; // return "En cours de prélèvement";
                } elseif (
                    $this->examens()->wherePivotNull('status_examen')->count() == $this->examens()->count()
                    && $this->examens()->wherePivotNotNull('prelevements')->count() == $this->examens()->count()
                    || ($this->examens()->count() && !$this->results()->count() && ($this->examens()->wherePivot('status_examen', StateExamen::PENDING->value)->count() || $this->examens()->wherePivot('status_examen', StateExamen::CREATED->value)->count()))
                ) {
                    return 2; // return "En attente de résultats";
                } elseif ($this->results()->count() >= $elt && $this->examens()->wherePivot('status_examen', StateExamen::PENDING->value)->count()) {
                    return 3; // return "Résultat en attente de validation";
                } elseif ($this->results()->count() >= $elt && $this->examens()->wherePivot('status_examen', StateExamen::VALIDATED->value)->count()) {
                    return 4; // return "Résultat validé";
                } elseif ($this->results()->count() >= $elt && $this->examens()->wherePivot('status_examen', StateExamen::PRINTED->value)->count()) {
                    return 5; // return "Résultat déjà imprimé";
                } elseif ($this->results()->count() >= $elt && $this->examens()->wherePivot('status_examen', StateExamen::DELIVERED->value)->count()) {
                    return 6; // return "Résultat distribué";
                } elseif ($this->results()->count() && $this->results()->count() < $elt && $this->examens()->wherePivot('status_examen', StateExamen::PENDING->value)->count() && !$this->examens()->wherePivot('status_examen', StateExamen::VALIDATED->value)->count()) {
                    return 7; // return "Résultat partiel en attente de validation"
                } elseif ($this->results()->count() < $elt && $this->examens()->wherePivot('status_examen', StateExamen::VALIDATED->value)->count()) {
                    return 8; // return "Résultat partiel validé";
                } elseif ($this->results()->count() < $elt && $this->examens()->wherePivot('status_examen', StateExamen::PRINTED->value)->count()) {
                    return 9; // return "Résultat partiel imprimé";
                } elseif ($this->results()->count() < $elt && $this->examens()->wherePivot('status_examen', StateExamen::DELIVERED->value)->count()) {
                    return 10; // return "Résultat partiel distribué";
                } elseif ($this->results()->count() >= $elt && $this->examens()->wherePivot('status_examen', StateExamen::CREATED->value)->count()) {
                    return 11; // return "Résultat invalidé";
                }

                return -1;
            },
        );
    }

    protected function prelevement(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->examens()->wherePivotNotNull('prelevements')->count() == $this->examens()->count()) {
                    return "Prélèvement effectué";
                }

                if ($this->examens()->wherePivotNotNull('prelevements')->count() > 0) {
                    return "En cours de prélèvement";
                }

                return "Aucun prélèvement";
            },
        );
    }

    protected function lastPrelevement(): Attribute
    {
        return Attribute::make(
            get: function () {
                $prelevements = $this->prestationables()->whereNotNull('prelevements')->latest()->first()?->prelevements;
                return $prelevements ? $prelevements[count($prelevements) - 1] : [];
            },
        );
    }

    public function scopeFilterInProgress(Builder $query, $startDate, $endDate, $assurance = null, $payableBy = null, bool $latestFacture = false, $search = ''): Builder
    {
        $centreId = request()->header('centre');

        $factureFilter = function ($query) use ($startDate, $endDate, $latestFacture, $search) {
            $query->where('factures.type', 2)
                ->where('factures.state', StateFacture::IN_PROGRESS->value)
                ->when($search, fn($q) => $q->where('factures.code', 'like', "%$search%"))
                ->when(
                    $latestFacture,
                    fn($q) => $q->whereDate('factures.date_fact', '<', $startDate),
                    fn($q) => $q->whereBetween('factures.date_fact', [$startDate, $endDate])
                );
        };

        return $query->where('prestations.centre_id', $centreId)
            ->whereHas('factures', $factureFilter)
            ->with([
                'factures' => fn($q) => $q->where('factures.type', 2),
                'actes',
                'soins',
                'consultations',
                'hospitalisations',
                'products',
                'examens',
                'client',
                'priseCharge',
                'priseCharge.assureur',
                'payableBy'
            ])
            ->when($assurance, function ($query) use ($assurance) {
                $query->whereHas('priseCharge.assureur', fn($q) => $q->where('id', $assurance));
            })
            ->when($payableBy, function ($query) use ($payableBy) {
                $query->where('payable_by', $payableBy);
            });
    }

    protected function paid(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->payable_by) return true;

                $facture = $this->factures()
                    ->where('type', 2)
                    ->first();

                if ($facture) {
                    return $facture->amount_client == $facture->regulations->sum('amount');
                }

                return false;
            }
        );
    }

    protected function canUpdate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $facture = $this->factures()->where('factures.type', 2)->first();
                if ($facture) {
                    if ($this->payable_by || $this->priseCharge) {
                        return $facture->created_at->addMinutes(30)->lt(now());
                    }

                    return $facture->amount_client && $facture->regulations()->where('regulations.state', StatusRegulation::ACTIVE->value)->count() == 0;
                }

                return true;
            }
        );
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class, 'prestation_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function payableBy(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'payable_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function priseCharge(): BelongsTo
    {
        return $this->belongsTo(PriseEnCharge::class, 'prise_charge_id');
    }

    public function actes(): MorphToMany
    {
        return $this->morphedByMany(Acte::class, 'prestationable')
            ->withPivot(['remise', 'quantity', 'date_rdv', 'date_rdv_end', 'amount_regulate', 'b', 'k_modulateur', 'pu'])
            ->withTimestamps();
    }

    public function soins(): MorphToMany
    {
        return $this->morphedByMany(Soins::class, 'prestationable')
            ->withPivot(['remise', 'nbr_days', 'type_salle', 'honoraire', 'amount_regulate', 'pu'])
            ->withTimestamps();
    }

    public function consultations(): MorphToMany
    {
        return $this->morphedByMany(Consultation::class, 'prestationable')
            ->withPivot(['amount_regulate', 'date_rdv', 'remise', 'quantity', 'pu'])
            ->withTimestamps();
    }

    public function hospitalisations(): MorphToMany
    {
        return $this->morphedByMany(OpsTblHospitalisation::class, 'prestationable')
            ->withPivot(['amount_regulate', 'date_rdv', 'remise', 'quantity', 'pu'])
            ->withTimestamps();
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'prestationable')
            ->withPivot(['amount_regulate', 'remise', 'quantity', 'pu'])
            ->withTimestamps();
    }

    public function examens(): MorphToMany
    {
        return $this->morphedByMany(Examen::class, 'prestationable')
            ->withPivot(['amount_regulate', 'remise', 'quantity', 'pu', 'b', 'prelevements', 'status_examen'])
            ->using(PrelevementsPivot::class)
            ->withTimestamps();
    }

    public function medias(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    public function convention(): BelongsTo
    {
        return $this->belongsTo(ConventionAssocie::class, 'convention_id');
    }

    public function appointments()
    {
        return $this->hasMany(RendezVous::class, 'prestation_id');
    }

    public function prestationables(): HasMany
    {
        return $this->hasMany(Prestationable::class, 'prestation_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'prestation_id');
    }
}
