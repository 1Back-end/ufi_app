<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionElement extends Model
{
    use HasFactory, SoftDeletes;

    protected  $table = 'session_element';

    protected $fillable = [
        'session_id',
        'facture_id',
        'montant',
        'caisse_id',
        'created_by',
        'updated_by',
        'centre_id',
        'regulation_method_id',
        'regulation_id',
        'is_deleted'
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');

    }
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    // 🔹 Relation avec la caisse
    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    // 🔹 Relation avec l'utilisateur créateur
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔹 Relation avec l'utilisateur ayant modifié
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function regulation_method()
    {
        return $this->belongsTo(RegulationMethod::class, 'regulation_method_id');
    }
    //
}
