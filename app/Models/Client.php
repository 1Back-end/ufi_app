<?php

namespace App\Models;

use App\Models\Trait\CreateDefaultUser;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Client extends Model
{
    use HasFactory, SoftDeletes, UpdatingUser, CreateDefaultUser;

    protected $fillable = [
        'societe_id', 'prefix_id', 'status_familiale_id',
        'type_document_id', 'sexe_id', 'nomcomplet_client', 'prenom_cli',
        'nom_cli', 'secondprenom_cli', 'date_naiss_cli', 'enfant_cli',
        'ref_cli', 'tel_cli', 'tel2_cli', 'type_cli', 'renseign_clini_cli',
        'assure_pa_cli', 'afficher_ap', 'nom_assure_principale_cli',
        'document_number_cli', 'nom_conjoint_cli', 'email_cli', 'date_naiss_cli_estime',
        'status_cli', 'client_anonyme_cli', 'addresse_cli', 'created_by', 'updated_by', 'tel_whatsapp', 'user_id'
    ];

    protected $appends = ['age'];

    // Le nom doit être caché pour le client annonyme lorsqu’on l’affiche
    protected function nomCli(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $this->client_anonyme_cli ? $this->ref_cli : $value,
            set: fn($value) => $value,
        );
    }

    protected function nomcompletClient(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $this->client_anonyme_cli ? $this->ref_cli : $value,
            set: fn($value) => $value,
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn() => Carbon::parse($this->date_naiss_cli)->age ,
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
            'created_at' => 'date:d/m/Y H:i:s',
        ];
    }
}
