<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kit_products';

    protected $fillable = [
        'code',
        'name',
        'price',
        'quantity',
        'is_active',
        'created_by',
        'updated_by',
    ];
    public function items(): HasMany
    {
        return $this->hasMany(KitProductItem::class, 'kit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
