<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriseEnCharge extends Model
{
    use HasFactory;

    protected $table = 'prise_en_charges';

    protected $fillable = [
        'code_assureur',
        'code_quotation',
        'date_pc',
        'date_debut_pc',
        'date_fin_pc',
        'code_client',
        'taux_pc',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    protected $casts = [
        'date_pc' => 'date',
        'date_debut_pc' => 'date',
        'date_fin_pc' => 'date',
        'is_deleted' => 'boolean',
        'taux_pc' => 'float',
    ];

    // Relations
    public function assureur()
    {
        return $this->belongsTo(Assureur::class, 'code_assureur');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'code_quotation');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'code_client');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
