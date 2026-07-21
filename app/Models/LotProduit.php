<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class LotProduit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lot_produits';

    protected $primaryKey = 'id';

    protected $fillable = [
        'numero_lot_fabricant',
        'date_peremption',
        'date_reception',
        'quantite_actuelle',
        'statut',
        'id_produit',
        'id_emplacement',
        'created_by',
        'updated_by',
        'fournisseur_id',
        'justification'
    ];

    protected $casts = [
        'date_peremption' => 'date',
        'date_reception'  => 'date',
        'quantite_actuelle' => 'integer',
    ];

    protected $appends = [
        'statut_calcule',
    ];

    public function determinerStatut(int $joursSeuilPeremption = 30): string
    {
        if ($this->quantite_actuelle <= 0) {
            return 'Épuisé';
        }

        if (!$this->date_peremption) {
            return 'Disponible';
        }

        $datePeremption = $this->date_peremption->startOfDay();
        $aujourdhui = Carbon::today();

        if ($datePeremption->isPast()) {
            return 'Périmé';
        }

        if ($datePeremption->lte($aujourdhui->copy()->addDays($joursSeuilPeremption))) {
            return 'Bientôt expiré';
        }

        return 'Disponible';
    }

    public function getStatutCalculeAttribute(): string
    {
        return $this->determinerStatut();
    }


    public function produit()
    {
        return $this->belongsTo(Product::class, 'id_produit');
    }


    public function emplacement()
    {
        return $this->belongsTo(EmplacementsProduct::class, 'id_emplacement');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function fournisseur()
    {
        return $this->belongsTo(Fournisseurs::class, 'fournisseur_id');
    }
}
