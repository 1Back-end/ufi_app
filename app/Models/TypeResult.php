<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'accept_saisi_user',
        'afficher_result',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'accept_saisi_user' => 'boolean',
            'afficher_result' => 'boolean',
        ];
    }
}
