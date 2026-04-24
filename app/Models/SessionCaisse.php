<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SessionCaisse extends Model
{
    use HasFactory, softDeletes;


    protected $table = 'session_caisse'; // 🔹 nom exact

    protected $fillable = [
        'user_id',
        'caisse_id',
        'created_by',
        'updated_by',
        'centre_id',
        'ouverture_ts',
        'fermeture_ts',
        'fonds_ouverture',
        'fonds_fermeture',
        'fonds_fermeture_exactly',
        'fonds_en_pause',
        'solde',
        'etat',
        'pause_ts',
        'current_sold'
    ];

    protected $casts = [
        'ouverture_ts' => 'datetime',
        'fermeture_ts' => 'datetime',
        'pause_ts' => 'datetime',
    ];


    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Caisse
    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    // Centre
    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    // Créé par
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Mis à jour par
    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    //
}
