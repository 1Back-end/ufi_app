<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Packaging extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'quantity',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity'  => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_packaging', 'packaging_product_id', 'product_id')
            ->using(ProductPackaging::class)
            ->withPivot('id', 'is_default', 'created_by', 'updated_by')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

}
