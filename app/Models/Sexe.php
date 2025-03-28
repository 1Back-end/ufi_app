<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sexe extends Model
{
    use HasFactory, UpdatingUser;

    protected $fillable = [
        'description_sex',
        'prefix_id',
        'created_by',
        'updated_by',
    ];

    public function createBySex(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateBySex(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'sexe_id');
    }

    public function prefixes(): BelongsToMany
    {
        return $this->belongsToMany(Prefix::class, 'prefix_sexe');
    }

    public function status_families(): BelongsToMany
    {
        return $this->belongsToMany(StatusFamiliale::class, 'sexe_stat_fam', 'sex_id', 'stat_fam_id');
    }
}
