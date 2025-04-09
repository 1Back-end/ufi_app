<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeActe extends Model
{
    use HasFactory, UpdatingUser;

    protected $fillable = [
        'created_by',
        'updated_by',
        'name',
        'k_modulateur',
        'state',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function actes(): HasMany
    {
        return $this->hasMany(Acte::class, 'type_acte_id');
    }
}
