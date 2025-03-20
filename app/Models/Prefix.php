<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prefix extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefixe',
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
}
