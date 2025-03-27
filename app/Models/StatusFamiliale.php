<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusFamiliale extends Model
{
    use HasFactory, UpdatingUser;

    protected $fillable = [
        'description_statusfam',
        'created_by',
        'updated_by',
    ];

    public function createByStatusfam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateByStatusfam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'status_familiale_id');
    }

    public function sexes(): BelongsToMany
    {
        return $this->belongsToMany(Sexe::class, 'sexe_stat_fam', 'stat_fam_id', 'sex_id');
    }
}
