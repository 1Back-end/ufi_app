<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class RendezVous extends Model
{
    use HasFactory;
    // Spécification de la table si ce n'est pas le nom par défaut (rendez_vouses)
    protected $table = 'rendez_vouses';

    // Définir les attributs qui peuvent être remplis en masse
    protected $fillable = [
        'created_by',
        'updated_by',
        'client_id',
        'consultant_id',
        'date_emission',
        'dateheure_rdv',
        'details',
        'nombre_jour_validite',
        'type',
        'etat',
        'code',
        'is_deleted',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rdv) {
            $prefix = 'RDV-';
            $timestamp = now()->format('YmdHis');
            $rdv->code = $prefix . $timestamp;
        });
    }

    // Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scope pour récupérer les rendez-vous non supprimés
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    // Accesseurs pour formater les dates
    public function getDateEmissionAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y H:i');
    }

    public function getDateheureRdvAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y H:i');
    }
    //
}
