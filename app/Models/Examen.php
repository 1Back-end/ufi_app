<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Examen extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "code",
        'name',
        'price',
        'b',
        'b1',
        'renderer_duration',
        'name_abrege',
        'prelevement_unit',
        'name1',
        'name2',
        'name3',
        'name4',
        'tube_prelevement_id',
        'type_prelevement_id',
        'paillasse_id',
        'sub_family_exam_id',
        'kb_prelevement_id',
        'code_exam',
    ];

    protected $withCount = ['elementPaillasses'];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    public function tubePrelevement(): BelongsTo
    {
        return $this->belongsTo(TubePrelevement::class, 'tube_prelevement_id');
    }

    public function typePrelevement(): BelongsTo
    {
        return $this->belongsTo(TypePrelevement::class, 'type_prelevement_id');
    }

    public function paillasse(): BelongsTo
    {
        return $this->belongsTo(Paillasse::class, 'paillasse_id');
    }

    public function subFamilyExam(): BelongsTo
    {
        return $this->belongsTo(SubFamilyExam::class, 'sub_family_exam_id');
    }

    public function kbPrelevement(): BelongsTo
    {
        return $this->belongsTo(KbPrelevement::class, 'kb_prelevement_id');
    }

    public function techniqueAnalysis(): BelongsToMany
    {
        return $this->belongsToMany(AnalysisTechnique::class, 'technique_exams')
            ->withPivot(['type']);
    }

    public function elementPaillasses(): HasMany
    {
        return $this->hasMany(ElementPaillasse::class, 'examen_id');
    }
}
