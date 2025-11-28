<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProformaItem extends Model
{

    use HasFactory;

    protected $fillable = [
        'proforma_id', 'name', 'unit_price', 'kb_prelevement', 'total',
        'type', 'is_deleted', 'created_by', 'updated_by','b_value'
    ];

    public function proforma()
    {
        return $this->belongsTo(Proforma::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function getTotalUnitPrice(): float
    {
        return $this->items->sum(function ($item) {
            $unitPrice = (float) $item->unit_price;
            $kb = (float) ($item->price_kb_prelevement ?? 0);
            return $unitPrice - $kb;
        });
    }
    public function examen()
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }


    //
}
