<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MouvementCaisse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mouvements_caisses';

    protected $fillable = [
        'code',
        'type',
        'caisse_depart_id',
        'caisse_arrivee_id',
        'montant',
        'description',
        'status',
        'created_by',
        'updated_by',
        'centre_id'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->code = self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        $datePart = now()->format('Ydm'); // Année, jour, mois → ex: 20252611
        $prefix = '#' . $datePart;

        // Chercher le dernier code global (pas par jour)
        $last = self::withTrashed()->orderBy('created_at', 'desc')->first();

        if ($last && preg_match('/(\d{6})$/', $last->code, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }


    public function caisseDepart()
    {
        return $this->belongsTo(Caisse::class, 'caisse_depart_id');
    }

    public function caisseArrivee()
    {
        return $this->belongsTo(Caisse::class, 'caisse_arrivee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }
}
