<?php

namespace App\Models;

use App\Traits\CreateDeleteTrait;
use App\Traits\DateCastTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use CreateDeleteTrait,DateCastTrait,HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'surname',
        'patronymic',
        'structure_id',
        'height',
        'military_service',
        'phone',
        'birthdate',
        'gender',
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
        'deleted_by',
    ];

    protected $dates = [
        'research_date',
        'examination_date',
        'appeal_date',
        'application_date',
        'requisition_date',
        'hhk_date',
        'birthdate',
    ];

    protected $casts = [
        'research_date' => 'date:d.m.Y',
        'examination_date' => 'date:d.m.Y',
        'appeal_date' => 'date:d.m.Y',
        'application_date' => 'date:d.m.Y',
        'requisition_date' => 'date:d.m.Y',
        'hhk_date' => 'date:d.m.Y',
        'birthdate' => 'datetime:d.m.Y',
    ];

    public function getFullnameAttribute(): string
    {
        return "$this->surname $this->name $this->patronymic";
    }

    public function getFullnameMaxAttribute(): string
    {
        return $this->fullname.' '.($this->gender == 2 ? 'qızı' : 'oğlu');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AppealStatus::class, 'status_id', 'id')->where('locale',config('app.locale'));
    }
}
