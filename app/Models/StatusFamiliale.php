<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusFamiliale extends Model
{
    use HasFactory;

    protected $fillable = [
        'description_statusfam',
        'create_by_statusfam',
        'update_by_statusfam',
    ];

    public function createByStatusfam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by_statusfam');
    }

    public function updateByStatusfam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'update_by_statusfam');
    }
}
