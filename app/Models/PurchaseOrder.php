<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'purchase_order_number',
        'purchase_order_type',
        'order_date',
        'expected_delivery_date',
        'fournisseur_id',
        'destination_location_id',
        'destination_source_id',
        'status',
        'description',
        'created_by',
        'updated_by',
        'rejected_at',
        'reason_of_rejection',
        'rejected_by',
    ];
    protected $appends = ['consumption_type_label','status_label'];

    public function getConsumptionTypeLabelAttribute(): string
    {
        return PurchaseOrderType::safeLabel($this->purchase_order_type);
    }

    public function getStatusLabelAttribute(): string
    {
        return PurchaseOrderStatus::safeLabel($this->status);
    }

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseurs::class, 'fournisseur_id', 'id');
    }

    public function destinationLocation()
    {
        return $this->belongsTo(EmplacementsProduct::class, 'destination_location_id', 'id');
    }

    public function destinationSource()
    {
        return $this->belongsTo(EmplacementsProduct::class, 'destination_source_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'id');
    }

}
