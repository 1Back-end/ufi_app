<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DossierConsultation extends Model
{
    protected $fillable = [
        'facture_id',
        'rendez_vous_id',
        'poids',
        'tension',
        'taille',
        'saturation',
        'autres_parametres',
        'temperature',
        'frequence_cardiaque',
        'is_deleted',
        'created_by',
        'updated_by',
        'is_open',
        'code'

    ];

   public  function facture()
   {
       return $this->belongsTo(Facture::class, 'facture_id');
   }
   public  function rendezVous()
   {
       return $this->belongsTo(RendezVous::class, 'rendez_vous_id');
   }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dossier) {
            $prefix = 'DOSSIER-';
            $timestamp = now()->format('YmdHis');
            $dossier->code = $prefix . $timestamp;
        });
    }
    //
}
