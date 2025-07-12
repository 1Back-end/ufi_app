<?php

namespace App\Models;

use App\Enums\StateFacture;
use App\Models\Trait\CreateDefaultUser;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Client extends Model
{
    use HasFactory, SoftDeletes, UpdatingUser, CreateDefaultUser;

    protected $fillable = [
        'societe_id',
        'prefix_id',
        'status_familiale_id',
        'type_document_id',
        'sexe_id',
        'nomcomplet_client',
        'prenom_cli',
        'nom_cli',
        'secondprenom_cli',
        'date_naiss_cli',
        'enfant_cli',
        'ref_cli',
        'tel_cli',
        'tel2_cli',
        'type_cli',
        'renseign_clini_cli',
        'assure_pa_cli',
        'afficher_ap',
        'nom_assure_principale_cli',
        'document_number_cli',
        'nom_conjoint_cli',
        'email',
        'date_naiss_cli_estime',
        'status_cli',
        'client_anonyme_cli',
        'addresse_cli',
        'created_by',
        'updated_by',
        'tel_whatsapp',
        'user_id',
        'urgent_contact',
        'urgent_contact_number',
        'religion',
    ];

    protected $appends = ['age', 'validity_card'];

    // Le nom doit être caché pour le client annonyme lorsqu’on l’affiche
    protected function nomCli(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if (
                    $this->client_anonyme_cli == 1
                    && (
                        auth()->user()->roles()->where('confidential', true)->exists()
                        || auth()->user()->client?->user_id === $attributes['user_id']
                        || ($this->created_at->diffInMinutes(now()) <= 10 && $attributes['created_by'] === auth()->user()->id)
                    )
                ) {
                    return $attributes['nom_cli'];
                }

                return $attributes['client_anonyme_cli'] ? $attributes['ref_cli'] : $attributes['nom_cli'];
            },
            set: fn($value) => $value,
        );
    }

    protected function nomcompletClient(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if (
                    $attributes['client_anonyme_cli']
                    && (
                        auth()->user()->roles()->where('confidential', true)->exists()
                        || auth()->user()->client?->user_id === $attributes['user_id']
                        || ($this->created_at?->diffInMinutes(now()) <= 10 && $attributes['created_by'] === auth()->user()->id)
                    )
                ) {
                    return $attributes['nomcomplet_client'];
                }

                return $attributes['client_anonyme_cli'] ? $attributes['ref_cli'] : $attributes['nomcomplet_client'];
            },
            set: fn($value) => $value,
        );
    }

    protected function prenomCli(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if (
                    $attributes['client_anonyme_cli']
                    && (
                        auth()->user()->roles()->where('confidential', true)->exists()
                        || auth()->user()->client?->user_id === $attributes['user_id']
                        || ($this->created_at->diffInMinutes(now()) <= 10 && $attributes['created_by'] === auth()->user()->id)
                    )
                ) {
                    return $attributes['prenom_cli'];
                }

                return $attributes['client_anonyme_cli'] ? $attributes['ref_cli'] : $attributes['prenom_cli'];
            },
            set: fn($value) => $value,
        );
    }

    protected function validityCard(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fidelityCard = $this->fidelityCard()->where('name', 'fidelityCard')->latest()->first();
                return $fidelityCard ? $fidelityCard->created_at->greaterThan(now()->subDays($fidelityCard->validity)) : null;
            },
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn() => Carbon::parse($this->date_naiss_cli)->age,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }

    public function prefix()
    {
        return $this->belongsTo(Prefix::class);
    }

    public function statusFamiliale()
    {
        return $this->belongsTo(StatusFamiliale::class);
    }

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
    }

    public function sexe()
    {
        return $this->belongsTo(Sexe::class);
    }

    public function createByCli()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateByCli()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fidelityCard(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function toPay(): HasMany
    {
        return $this->hasMany(Prestation::class, 'payable_by');
    }

    public function facturesInProgressDeType2()
    {
        return $this->hasManyThrough(
            Facture::class,
            Prestation::class,
            'payable_by', // Foreign key on Prestation
            'prestation_id',      // Foreign key on Facture
            'id',                 // Local key on PriseEnCharge
            'id'                  // Local key on Prestation
        )->where('factures.type', 2)
            ->where('factures.state', StateFacture::IN_PROGRESS->value);
    }

    public function specialRegulations(): MorphMany
    {
        return $this->morphMany(SpecialRegulation::class, 'regulation');
    }

    protected function casts()
    {
        return [
            'date_naiss_cli' => 'date:d/m/Y',
            'enfant_cli' => 'boolean',
            'assure_pa_cli' => 'boolean',
            'afficher_ap' => 'boolean',
            'date_naiss_cli_estime' => 'boolean',
            'status_cli' => 'integer',
            'client_anonyme_cli' => 'boolean',
            'tel_whatsapp' => 'boolean',
        ];
    }

    public function conventionAssocies(): HasMany
    {
        return $this->hasMany(ConventionAssocie::class, 'client_id');
    }

    public function factures(): MorphMany
    {
        return $this->morphMany(FactureAssociate::class, 'facturable');
    }
}
