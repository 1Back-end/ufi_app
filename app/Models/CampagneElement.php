<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampagneElement extends Model
{
    use HasFactory;

    protected $fillable = [
        'campagne_id',
        'type',
        'element_id',
        'price',
        'created_by',
        'updated_by'
    ];

    protected $appends = [
        'element_name', // Permet de l'inclure automatiquement dans les JSON
    ];

    /**
     * Relation avec la campagne
     */
    public function campagne()
    {
        return $this->belongsTo(Campagne::class);
    }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation dynamique vers l'élément selon le type
     */
    public function element()
    {
        return match($this->type) {
            'examens' => $this->belongsTo(Examen::class, 'element_id'),
            'consultations' => $this->belongsTo(Consultation::class, 'element_id'),
            'actes' => $this->belongsTo(Acte::class, 'element_id'),
            'soins' => $this->belongsTo(Soins::class, 'element_id'),
            default => null
        };
    }
    protected function elementName(): Attribute
    {
        return Attribute::get(fn () => $this->element?->name);
    }

}
