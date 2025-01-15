<?php

namespace App\Models;

use App\Traits\CreateDeleteTrait;
use App\Traits\DateCastTrait;
use App\Traits\GenderEnumTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use CreateDeleteTrait,
        DateCastTrait,
        HasFactory,
        SoftDeletes;

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
        'research_date' => self::FORMAT_CAST,
        'examination_date' => self::FORMAT_CAST,
        'appeal_date' => self::FORMAT_CAST,
        'application_date' => self::FORMAT_CAST,
        'requisition_date' => self::FORMAT_CAST,
        'hhk_date' => self::FORMAT_CAST,
        'birthdate' => 'datetime:d.m.Y',
    ];

    protected $likeFilterFields = [
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
        return $this->belongsTo(AppealStatus::class, 'status_id', 'id')->where('locale', config('app.locale'));
    }

    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            switch ($field) {
                case 'results':
                    $query->where(function ($q) use ($value) {
                        $q->where('knowledge_test', $value)->orWhere('physical_fitness_exam', $value);
                    });
                    break;
                case 'age':
                    $query->whereRaw('timestampdiff(year, birthdate, curdate()) = ?', [$value]);
                    break;
                case 'appeal_date':
                    $min = empty($value['min']) ? '1990-01-01' : Carbon::parse($value['min'])->format('Y-m-d');
                    $max = empty($value['max']) ? Carbon::now()->format('Y-m-d') : Carbon::parse($value['max'])->format('Y-m-d');
                    $query->whereBetween($field, [$min, $max]);
                    break;
                case 'fullname':
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'LIKE', "%$value%")
                            ->orWhere('surname', 'LIKE', "%$value%")
                            ->orWhere('patronymic', 'LIKE', "%$value%");
                    });
                    break;
                default:
                    if (in_array($field, $this->likeFilterFields) && $value !== null) {
                        $query->where($field, 'LIKE', "%$value%");
                    } elseif (in_array($field, $this->fillable) && $value !== null) {
                        $query->where($field, $value);
                    }
                    break;
            }
        }

        return $query;
    }
}
