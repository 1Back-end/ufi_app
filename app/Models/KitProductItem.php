<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitProductItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kit_product_items';

    protected $fillable = [
        'kit_id',
        'product_id',
        'created_by',
        'updated_by',
    ];

    public function kit(): BelongsTo
    {
        return $this->belongsTo(KitProduct::class, 'kit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
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
