<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Ordonnance extends Model
{
    use HasFactory;

    protected $table = 'ops_tbl_ordonnance';

    protected $fillable = [
        'code',
        'rapport_consultations_id',
        'description',
        'created_by',
        'updated_by',
    ];

    // Relation vers le rapport de consultation
    public function rapportConsultation()
    {
        return $this->belongsTo(OpsTblRapportConsultation::class, 'rapport_consultations_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ordonnance) {
            $prefix = 'ORDONNANCE-';
            $timestamp = now()->format('YmdHis');
            $ordonnance->code = $prefix . $timestamp;
        });
    }
}
