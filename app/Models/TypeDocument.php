<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'description_typedoc',
        'create_by_typedoc',
        'update_by_typedoc',
    ];

    public function createByTypedoc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by_typedoc');
    }

    public function updateByTypedoc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'update_by_typedoc');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'type_document_id');
    }
}
