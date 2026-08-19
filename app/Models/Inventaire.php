<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventaires';

    protected $fillable = [
        'centre_id',
        'reference',
        'date_inventaire',
        'status',
        'commentaires',
        'created_by',
        'updated_by',
        'validated_by',
        'validated_at'
    ];

    // Relations
    public function items()
    {
        return $this->hasMany(InventaireItem::class, 'inventaire_id');
    }

    public function centre()
    {
        return $this->belongsTo(DossierLocation::class, 'centre_id');
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
}
