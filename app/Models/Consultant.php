<?php

namespace App\Models;

use App\Models\Trait\CreateDefaultUser;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultant extends Model
{
    use HasFactory,UpdatingUser, CreateDefaultUser;

    protected $fillable = [
        'code_hopi',
        'code_service_hopi',
        'code_specialite',
        'code_titre',
        'ref',
        'nom',
        'prenom',
        'nomcomplet', // Ajouté ici
        'tel',
        'tel1',
        'email',
        'type',
        'status',
        'created_by',
        'updated_by',
        'TelWhatsApp',
        'centre_id',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function code_hopi()
    {
        return $this->belongsTo(Hopital::class, 'code_hopi');
    }
    public function code_specialite()
    {
        return $this->belongsTo(Specialite::class, 'code_specialite');
    }
    public function code_titre()
    {
        return $this->belongsTo(Titre::class, 'code_titre');
    }

    public function code_service_hopi()
    {
        return $this->belongsTo(Service_Hopital::class, 'code_service_hopi');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }
    protected static function booted()
    {
        static::creating(function ($consultant) {
            if (empty($consultant->ref)) {
                $consultant->ref = 'C' . now()->format('ymdHis') . mt_rand(10, 99);
            }
        });
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($consultant) {
            if (empty($consultant->nomcomplet)) {
                $titreNom = null;
                if (!empty($consultant->code_titre)) {
                    $titre = Titre::find($consultant->code_titre);
                    $titreNom = $titre ? $titre->nom_titre : null;
                }

                $consultant->nomcomplet = trim("{$titreNom} {$consultant->prenom} {$consultant->nom}");
            }
        });

        static::updating(function ($consultant) {
            if (empty($consultant->nomcomplet)) {
                $titreNom = null;
                if (!empty($consultant->code_titre)) {
                    $titre = Titre::find($consultant->code_titre);
                    $titreNom = $titre ? $titre->nom_titre : null;
                }

                $consultant->nomcomplet = trim("{$titreNom} {$consultant->prenom} {$consultant->nom}");
            }
        });
    }
    public function isComplete(): bool
    {
        // Valeurs considérées comme incomplètes
        $placeholders = ['RAS', null, ''];

        // Vérifie les champs texte
        $textFields = ['nom', 'prenom', 'nomcomplet', 'type', 'status'];
        foreach ($textFields as $field) {
            if (in_array($this->$field, $placeholders)) {
                return false;
            }
        }

        // Vérifie les relations/IDs
        $idFields = ['code_hopi', 'code_service_hopi', 'code_specialite', 'code_titre', 'centre_id'];
        foreach ($idFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        // Vérifie l'email
        if (empty($this->email) || str_contains($this->email, 'example.com') || str_contains($this->email, 'fake')) {
            return false;
        }

        // Vérifie le téléphone : faux téléphone généré par Faker
        if (empty($this->tel) || preg_match('/^\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}$/', $this->tel)) {
            return false;
        }

        return true;
    }

    public function disponibilites()
    {
        return $this->hasMany(ConsultantDisponibilite::class);
    }






}
