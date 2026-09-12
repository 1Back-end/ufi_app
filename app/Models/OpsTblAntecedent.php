<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpsTblAntecedent extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'ops_tbl_antecedents';
    protected $fillable = [
        'dossier_consultation_id',
        'client_id',
        'categorie_antecedent_id',
        'souscategorie_antecedent_id',
        'category_label',
        'sous_categorie_label',
        'description',
        'pas_d_antecedent',
        'is_deleted',
        'created_by',
        'updated_by',
        'code'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieAntecedent::class, 'categorie_antecedent_id');
    }

    public function sousCategorie(): BelongsTo
    {
        return $this->belongsTo(ConfigTblSousCategorieAntecedent::class, 'souscategorie_antecedent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $year = now()->format('Y');
            $today = now()->format('Ymd');

            $lastRecord = self::whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastRecord ? intval(substr($lastRecord->code, 4, 3)) + 1 : 1;
            $formattedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

            $model->code = '#' . $formattedSequence  . $today;
        });
    }
}
