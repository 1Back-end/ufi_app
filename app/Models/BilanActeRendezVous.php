<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class BilanActeRendezVous extends Model
{
    use HasFactory;

    protected $table = 'bilans_actes_rendez_vous';

    protected $fillable = [
        'rendez_vous_id',
        'prestation_id',
        'medecin_signataire',
        'technique_analyse',
        'resume',
        'conclusion',
        'created_by',
        'updated_by',
        'titre', 'attachment'
    ];

    protected $appends = ['rapport_attachment'];

    public function getRapportAttachmentAttribute()
    {
        $media = $this->medias()->first();
        if ($media) {
            return Storage::disk($media->disk)->url($media->path);
        }
        return null;
    }

    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function rendezVous()
    {
        return $this->belongsTo(RendezVous::class, 'rendez_vous_id');
    }

    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
