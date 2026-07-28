<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransfertStockItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transfert_stocks_items';

    protected $fillable = [
        'transfert_stock_id',
        'product_id',
        'lot_produit_id',
        'quantite',
        'updated_by',
        'created_by',
    ];

    public function transfert()
    {
        return $this->belongsTo(TransfertStock::class, 'transfert_stock_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function lot()
    {
        return $this->belongsTo(LotProduit::class, 'lot_produit_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
