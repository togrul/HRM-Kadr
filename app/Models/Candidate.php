<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'surname',
        'patronymic',
        'structure_id',
        'height',
        'military_service',
        'phone',
        'status_id',
        'knowledge_test',
        'physical_fitness_exam',
        'research_date',
        'research_result',
        'discrediting_information',
        'examination_date',
        'appeal_date',
        'application_date',
        'requisition_date',
        'initial_documents',
        'documents_completeness',
        'attitude_to_military',
        'characteristics',
        'hhk_date',
        'hhk_result',
        'useless_info',
        'note',
        'presented_by',
        'creator_id',
        'deleted_by'
    ];

    public function getFullnameAttribute() : string
    {
        return "{$this->surname} {$this->name} {$this->patronymic}";
    }

    public function creator() : BelongsTo
    {
        return $this->belongsTo(User::class,'creator_id','id');
    }

    public function personDidDelete() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

    public function structure() : BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function status() : BelongsTo
    {
        return $this->belongsTo(AppealStatus::class,'status_id','id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->creator_id = auth()->user()->id;
        });
        static::deleting(function ($model) {
            $model->deleted_by = auth()->user()->id;
            $model->save();
        });
    }
}
