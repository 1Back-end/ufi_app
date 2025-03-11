<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'societe_id', 'prefix_id', 'status_familiale_id',
        'type_document_id', 'sexe_id', 'nomcomplet_client', 'prenom_cli',
        'nom_cli', 'secondprenom_cli', 'date_naiss_cli', 'enfant_cli',
        'ref_cli', 'tel_cli', 'tel2_cli', 'type_cli', 'renseign_clini_cli',
        'assure_pa_cli', 'afficher_ap', 'nom_assure_principale_cli',
        'document_number_cli', 'nom_conjoint_cli', 'email_cli', 'date_naiss_cli_estime',
        'status_cli', 'client_anonyme_cli', 'addresse_cli', 'create_by_cli', 'update_by_cli', 'tel_whatsapp',
    ];

    protected $appends = ['fullname', 'age'];

    // Le nom doit être caché pour les client annonyme lorsqu'on l'affiche
    protected function nomCli(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $this->client_anonyme_cli ? $this->ref_cli : $value,
            set: fn($value) => $value,
        );
    }

    /**
     * @return Attribute
     */
    protected function fullname(): Attribute
    {
        return Attribute::make(
            get: function () {
                $prefix = $this->prefix->prefixe; // Champ contenant le préfixe
                $nom = ucfirst($this->nom ?? ''); // ucfirst pour mettre la première lettre en majuscule
                $prenom = ucfirst($this->prenom ?? '');

                return match ($prefix) {
                    'Epouse' => "$nom Epouse $prenom",
                    'Enfant', 'Bebe' => "Enfant $nom $prenom",
                    default => "$nom $prenom",
                };
            }
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn() => Carbon::parse($this->date_naissance)->age ,
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
        return $this->belongsTo(User::class, 'create_by_cli');
    }

    public function updateByCli()
    {
        return $this->belongsTo(User::class, 'update_by_cli');
    }

    protected function casts()
    {
        return [
            'date_naiss_cli' => 'date',
            'enfant_cli' => 'boolean',
            'assure_pa_cli' => 'boolean',
            'afficher_ap' => 'boolean',
            'date_naiss_cli_estime' => 'boolean',
            'status_cli' => 'boolean',
            'client_anonyme_cli' => 'boolean',
            'tel_whatsapp' => 'boolean',
        ];
    }
}
