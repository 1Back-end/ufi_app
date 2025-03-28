<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeDocument extends Model
{
    use UpdatingUser, HasFactory;

    protected $fillable = [
        'description_typedoc',
        'created_by',
        'updated_by',
    ];

    public function createByTypedoc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateByTypedoc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'type_document_id');
    }
}
