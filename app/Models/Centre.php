<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Centre extends Model
{
    use HasFactory, SoftDeletes, UpdatingUser;

    protected $fillable = [
        'reference', 'name', 'short_name', 'address',
        'tel', 'tel2', 'contribuable', 'registre_commerce',
        'autorisation', 'town', 'fax', 'email', 'website',
        'created_by', 'updated_by', 'deleted_at',
    ];

    protected $appends = ['logo'];

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_centre')
            ->withPivot(['default']);
    }

    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class, 'centre_id');
    }

    public function prestations(): HasMany
    {
        return $this->hasMany(Prestation::class, 'centre_id');
    }
}
