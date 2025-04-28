<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Acte extends Model
{
    use HasFactory, UpdatingUser;

    protected $fillable = [
        'created_by',
        'updated_by',
        'name',
        'pu',
        'type_acte_id',
        'delay',
        'state',
        'k_modulateur',
        'b',
        'b1',
    ];

    protected function casts(): array
    {
        return [
            'state' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function typeActe(): BelongsTo
    {
        return $this->belongsTo(TypeActe::class);
    }

    /**
     * @return MorphToMany
     */
    public function prestation(): MorphToMany
    {
        return $this->morphToMany(Prestation::class, 'prestationable')
            ->withPivot(['remise', 'quantity', 'date_rdv', 'date_rdv_end'])
            ->withTimestamps();
    }

    public function assureurs(): MorphToMany
    {
        return $this->morphToMany(Assureur::class, 'assurable');
    }
}
