<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prefix extends Model
{
    use HasFactory, UpdatingUser;

    protected $fillable = [
        'prefixe',
        'position',
        'age_max', 'age_min',
        'create_by',
        'update_by',
    ];

    public function createBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by');
    }

    public function updateBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'update_by');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'prefix_id');
    }

    public function sexes(): BelongsToMany
    {
        return $this->belongsToMany(Sexe::class, 'prefix_sexe');
    }
}
