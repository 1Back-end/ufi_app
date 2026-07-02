<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MouvementStock extends Model
{
    use SoftDeletes;

    protected $table = 'mouvement_stock';

    protected $primaryKey = 'id';

    protected $fillable = [
        'created_by',
        'updated_by',
        'lot_id',
        'type_mouvement',
        'quantite_mutee',
        'description',
        'date_heure_mouvement',
    ];

    protected $casts = [
        'date_heure_mouvement' => 'datetime',
        'quantite_mutee' => 'integer',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lot()
    {
        return $this->belongsTo(LotProduit::class, 'lot_id');
    }
}
