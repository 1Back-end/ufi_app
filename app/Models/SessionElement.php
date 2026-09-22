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
        'is_deleted',
        'prestation_id'
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');

    }
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function regulation_method()
    {
        return $this->belongsTo(RegulationMethod::class, 'regulation_method_id');
    }
    public function regulation()
    {
        return $this->belongsTo(Regulation::class, 'regulation_id');
    }
    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }
}
