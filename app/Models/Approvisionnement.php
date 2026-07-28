<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approvisionnement extends Model
{
    use HasFactory;

    protected $table = 'approvisionnements';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'emplacement_id',
        'order_number',
        'quantite_recue',
        'batch_number',
        'expiration_date',
        'received_date',
        'created_by',
        'updated_by',
        'received_packaging_id',
        'unit_quantity_received',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function emplacement(): BelongsTo
    {
        return $this->belongsTo(EmplacementsProduct::class, 'emplacement_id');
    }
    public function receivedPackaging()
    {
        return $this->belongsTo(Packaging::class, 'received_packaging_id');
    }
}
