<?php

namespace App\Models;

use App\Enums\TypePrestation;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RendezVous extends Model
{
    use HasFactory, UpdatingUser;

    protected $table = 'rendez_vouses';

    protected $fillable = [
        'created_by',
        'updated_by',
        'client_id',
        'consultant_id',
        'dateheure_rdv',
        'details',
        'nombre_jour_validite',
        'duration',
        'type',
        'etat',
        'code',
        'etat_paiement',
        'is_deleted',
        'rendez_vous_id',  // Pense à ajouter ici aussi
        'prestation_id',
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

    public function getTypePrestationLabelAttribute()
    {
        return $this->prestation ? TypePrestation::label($this->prestation->type) : null;
    }
    public function parent()
    {
        return $this->belongsTo(RendezVous::class, 'rendez_vous_id');
    }


    public function children()
    {
        return $this->hasMany(RendezVous::class, 'rendez_vous_id');
    }

    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }


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

    // Mutateur pour formater la date/heure
    public function getDateheureRdvAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y H:i');
    }
}
