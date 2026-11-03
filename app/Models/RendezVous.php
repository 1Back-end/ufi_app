<?php

namespace App\Models;

use App\Enums\RendezVousStatus;
use App\Enums\RendezVousType;
use App\Enums\TypePrestation;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RendezVous extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'appointments';

    protected $fillable = [
        'created_by',
        'updated_by',
        'client_id',
        'consultant_id',
        'dateheure_rdv',
        'details',
        'nombre_jour_validite',
        'duration',
        'type',
        'etat',
        'code',
        'etat_paiement',
        'is_deleted',
        'rendez_vous_id',
        'prestation_id',
        'imaging_results_delivered',
        'imaging_delivered_by_user_id',
        'nursing_results_delivered',
        'nursing_delivered_by_user_id',
        'appointment_date',
        'appointment_time',
        'day_of_week',
        'appointment_type',
        'doctor_approved',
        'is_closed',
        'is_acte',
        'is_consultation',
        'is_have_folder_consultation'

    ];
    protected $appends = [
        'nombre_jours',
        'etat_label',
        'type_label',
        'is_expired'
    ];

    public function getEtatLabelAttribute(): string
    {
        return RendezVousStatus::safeLabel($this->etat);
    }
    public function getTypeLabelAttribute(): string
    {
        return RendezVousType::safeLabel($this->type);
    }
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->dateheure_rdv) {
            return false;
        }

        $dateRdv = \Carbon\Carbon::parse($this->dateheure_rdv);
        $validite = $this->nombre_jour_validite ?? 14;

        return round($dateRdv->diffInDays(now())) > $validite;
    }



    protected $casts = [
        'dateheure_rdv' => 'datetime',
    ];


    public function bilans()
    {
        return $this->hasMany(BilanActeRendezVous::class, 'rendez_vous_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rendezVous) {
            $year = now()->format('Y');
            $today = now()->format('Ymd');
            $count = self::whereYear('created_at', $year)
                ->lockForUpdate()
                ->count();

            $sequence = $count + 1;
            $formattedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);
            $rendezVous->code = '#' . $formattedSequence . $today;
        });
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d\TH:i:s');
    }

    public function getTypePrestationLabelAttribute()
    {
        return $this->prestation ? TypePrestation::label($this->prestation->type) : null;
    }
    public function parent()
    {
        return $this->belongsTo(RendezVous::class, 'rendez_vous_id');
    }

    public function children()
    {
        return $this->hasMany(RendezVous::class, 'rendez_vous_id');
    }

    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

        public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function getNombreJoursAttribute(): bool
    {
        if (
            $this->is_closed ||
            $this->is_no_show ||
            $this->etat === RendezVousStatus::CLOSED->value ||
            $this->etat === RendezVousStatus::NO_SHOW->value
        ) {
            return false;
        }

        if (!$this->dateheure_rdv) {
            return false;
        }

        $rdvPlusQuatorzeJours = $this->dateheure_rdv->copy()->addDays(14);
        return Carbon::now()->lessThanOrEqualTo($rdvPlusQuatorzeJours);
    }


    public function facture()
    {
        return $this->hasMany(Facture::class, 'prestation_id', 'prestation_id');
    }
    public function patient()
    {
        return $this->belongsTo(Client::class, 'patient_id');
    }







}
