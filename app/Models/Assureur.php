<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assureur extends Model

{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'nom_abrege',
        'adresse',
        'tel',
        'tel1',
        'code_quotation',
        'code_centre',
        'Reg_com',
        'num_com',
        'bp',
        'fax',
        'code_type',
        'code_main',
        'ref_assur',
        'email',
        'BM',
        'ref',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
        'ref_assur_principal'
    ];


    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'code_quotation');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'code_centre');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function assureurPrincipal()
    {
        return $this->belongsTo(Assureur::class, 'code_main');
    }
    //
}
