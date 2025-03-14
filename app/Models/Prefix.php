<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prefix extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefixe',
        'create_by_prefix',
        'update_by_prefix',
    ];

    public function createByPrefix(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by_prefix');
    }

    public function updateByPrefix(): BelongsTo
    {
        return $this->belongsTo(User::class, 'update_by_prefix');
    }
}
