<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPackaging extends Pivot
{
    use SoftDeletes;

    protected $table = 'product_packaging';

    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'packaging_product_id',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class, 'packaging_product_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
