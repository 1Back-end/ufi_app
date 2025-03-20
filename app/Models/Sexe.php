<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sexe extends Model
{
    use HasFactory;

    protected $fillable = [
        'description_sex', 'create_by_sex', 'update_by_sex',
    ];

    public function createBySex(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by_sex');
    }

    public function updateBySex(): BelongsTo
    {
        return $this->belongsTo(User::class, 'update_by_sex');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'sexe_id');
    }
}
