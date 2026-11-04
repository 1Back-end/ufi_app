<?php

namespace App\Models;

use App\Models\Trait\CreateDefaultUser;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Consultant extends Model
{
    use HasFactory, UpdatingUser, CreateDefaultUser;

    protected $table = 'consultants';

    protected $fillable = [
        'code_hopi',
        'code_service_hopi',
        'code_specialite',
        'code_titre',
        'ref',
        'nom',
        'prenom',
        'nomcomplet',
        'tel',
        'tel1',
        'email',
        'type',
        'status',
        'created_by',
        'updated_by',
        'TelWhatsApp',
        'centre_id',
        'user_id',
        'is_used_commission',
        'order_number',
        'is_active',
        'is_archived',
        'can_consult_out_of_schedule'
    ];

    protected $appends = ['fullname', 'nb_prestations', 'is_used'];

    protected function fullname(): Attribute
    {
        return Attribute::make(
            get: fn() => trim("{$this->nom} {$this->prenom}"),
        );
    }

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class, 'code_hopi', 'id');
    }

    public function serviceHopital(): BelongsTo
    {
        return $this->belongsTo(Service_Hopital::class, 'code_service_hopi', 'id');
    }

    public function specialite(): BelongsTo
    {
        return $this->belongsTo(Specialite::class, 'code_specialite', 'id');
    }

    public function titre(): BelongsTo
    {
        return $this->belongsTo(Titre::class, 'code_titre', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class, 'centre_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function appointments()
    {
        return $this->hasMany(RendezVous::class, 'consultant_id');
    }

    public function disponibilites()
    {
        return $this->hasMany(ConsultantDisponibilite::class, 'consultant_id');
    }

    public function prestations()
    {
        return $this->hasMany(ConsultantPrestationShare::class, 'consultant_id')
            ->with('prestationType');
    }

    public function account()
    {
        return $this->belongsTo(PaymentAccount::class, 'account_id', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($consultant) {
            if (empty($consultant->ref)) {
                $consultant->ref = 'C' . now()->format('ymdHis') . mt_rand(10, 99);
            }
        });

        static::creating(function ($consultant) {
            if (empty($consultant->nomcomplet)) {
                $consultant->nomcomplet = $consultant->genererNomComplet();
            }
        });

        static::updating(function ($consultant) {
            if (empty($consultant->nomcomplet)) {
                $consultant->nomcomplet = $consultant->genererNomComplet();
            }
        });
    }

    public function genererNomComplet(): string
    {
        $titreNom = null;
        if (!empty($this->code_titre)) {
            $titre = $this->relationLoaded('titre') ? $this->titre : $this->titre()->first();
            $titreNom = $titre ? $titre->nom_titre : null;
        }
        return trim("{$titreNom} {$this->prenom} {$this->nom}");
    }

    protected function nbPrestations(): Attribute
    {
        return Attribute::make(
            get: fn () => Prestation::where('consultant_id', $this->id)->count(),
        );
    }

    protected function isUsed(): Attribute
    {
        return Attribute::make(
            get: fn () => Prestation::where('consultant_id', $this->id)->exists(),
        );
    }
}
