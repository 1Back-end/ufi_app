<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocksRegularisationsItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stocks_regularisations_items';

    protected $fillable = [
        'stocks_regularisation_id',
        'status',
        'quantite',
        'product_id',
        'packaging_id',
        'updated_by',
        'created_by',
    ];

    public function regularisation(): BelongsTo
    {
        return $this->belongsTo(StocksRegularisation::class, 'stocks_regularisation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(LotProduit::class, 'packaging_id');
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
