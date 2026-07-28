<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransfertStock extends Model
{
    use HasFactory, SoftDeletes;

    // Nom explicite de la table
    protected $table = 'transferts_stocks';

    protected $fillable = [
        'reference',
        'emplacement_source_id',
        'emplacement_destination_id',
        'staff_name',
        'description',
        'updated_by',
        'created_by',
        'status',
        'validated_by',
        'validated_at',
        'cancelled_by',
        'cancelled_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    public function emplacementSource()
    {
        return $this->belongsTo(EmplacementsProduct::class, 'emplacement_source_id');
    }


    public function emplacementDestination()
    {
        return $this->belongsTo(EmplacementsProduct::class, 'emplacement_destination_id');
    }

    public function items()
    {
        return $this->hasMany(TransfertStockItem::class, 'transfert_stock_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
