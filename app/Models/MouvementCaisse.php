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
        do {

            $code =
                'TRF-' .
                now()->format('YmdHis') .
                '-' .
                rand(1000, 9999);

        } while (
            self::withTrashed()
                ->where('code', $code)
                ->exists()
        );

        return $code;
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
