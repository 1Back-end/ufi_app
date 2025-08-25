<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
    protected $appends = ['logo'];

   public  function facture()
   {
       return $this->belongsTo(Facture::class, 'facture_id');
   }
   public  function rendezVous()
   {
       return $this->belongsTo(RendezVous::class, 'rendez_vous_id');
   }
    public function motifsConsultation()
    {
        return $this->hasMany(OpsTbl_Motif_consultation::class, 'dossier_consultation_id');
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

        static::creating(function ($examenPhysique) {
            $prefix = 'DOSSIER-';
            $timestamp = now()->format('ymdHi');

            $random = strtoupper(Str::random(7));
            $examenPhysique->code = $prefix . $timestamp . $random;
        });
    }
    protected function logo(): Attribute
    {
        return Attribute::make(
            get: function($value, array $attributes) {
                $media = $this->medias()->where('name', 'logo')->first();
                if ($media) {
                    return Storage::disk($media->disk)->url($media->path .'/'. $media->filename);
                }
            },
        );
    }
    //
}
