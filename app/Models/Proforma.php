<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{

    use HasFactory;

    protected $fillable = [
        'client_id', 'quotation_id', 'b_global', 'proforma', 'total',
        'is_deleted', 'created_by', 'updated_by','centre_id','code','status','type','price_kb_prelevement'
    ];
    protected $appends = ['total_unit_price'];

    public function items()
    {
        return $this->hasMany(ProformaItem::class)->with('examen');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function centre()
    {
        return $this->belongsTo(Centre::class,'centre_id');
    }
    public function quotation()
    {
        return $this->belongsTo(Quotation::class,'quotation_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }
    public function getTotalUnitPriceAttribute(): float
    {
        return $this->items->sum(fn($item) => (float) $item->unit_price);
    }


}
