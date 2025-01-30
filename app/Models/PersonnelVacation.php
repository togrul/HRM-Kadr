<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelVacation extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait,SoftDeletes;

    protected $fillable = [
        'tabel_no',
        'vacation_places',
        'duration',
        'start_date',
        'end_date',
        'return_work_date',
        'order_given_by',
        'order_no',
        'order_date',
        'vacation_days_total',
        'remaining_days',
        'added_by',
        'deleted_by',
        'deleted_at',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'return_work_date',
        'order_date',
    ];

    protected $casts = [
        'start_date' => self::FORMAT_CAST,
        'end_date' => self::FORMAT_CAST,
        'return_work_date' => self::FORMAT_CAST,
        'order_date' => self::FORMAT_CAST,
    ];

    protected $likeFilterFields = [
        'vacation_places',
        'duration',
        'order_no',
    ];

    public function personDidDelete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderLog::class, 'order_no', 'order_no');
    }

    public function scopeWhereDateInYear($query, $year)
    {
        return $query->where(function ($q) use ($year) {
            $q->where('start_date', '>=', "{$year}-01-01")
                ->where('start_date', '<=', "{$year}-12-31");
        });
    }

    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            switch ($field) {
                case 'structure_id':
                    if (isset($value['id'])) {
                        $structureModel = Structure::with('subs')->find($value['id']);
                        if ($structureModel) {
                            $structure = $structureModel->getAllNestedIds();
                            $query->whereHas('personnel.structure', function ($qq) use ($structure) {
                                $qq->whereIn('structure_id', $structure);
                            });
                        }
                    }
                    break;
                case 'date':
                    $minDate = $value['min'] ?? '' ? Carbon::parse($value['min'])->format('Y-m-d') : null;
                    $maxDate = $value['max'] ?? '' ? Carbon::parse($value['max'])->format('Y-m-d') : null;
                    if ($minDate) {
                        $query->where('start_date', '>=', $minDate);
                    }
                    if ($maxDate) {
                        $query->where('end_date', '<=', $maxDate);
                    }
                    break;
                case 'vacation_status':
                    if ($value === 'at_work') {
                        $query->where('return_work_date', '<', Carbon::now());
                    } elseif ($value === 'in_vacation') {
                        $query->where('return_work_date', '>', Carbon::now());
                    }
                    break;
                case 'fullname':
                    $query->whereHas('personnel', function ($qq) use ($value) {
                        $qq->where(function ($q) use ($value) {
                            // Split the search term into words
                            $searchTerms = explode(' ', trim($value));

                            foreach ($searchTerms as $term) {
                                $q->where(function ($subQuery) use ($term) {
                                    $subQuery->orWhere('surname', 'LIKE', "%{$term}%")
                                        ->orWhere('name', 'LIKE', "%{$term}%")
                                        ->orWhere('patronymic', 'LIKE', "%{$term}%");
                                });
                            }
                        });
                    });
                    break;
                default:
                    if (in_array($field, $this->likeFilterFields) && $value != null) {
                        $query->where($field, 'LIKE', "%$value%");
                    }
                    break;
            }
        }
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->added_by = auth()->user()->id;
        });
        static::deleting(function ($model) {
            if (! $model->isForceDeleting()) {
                $model->deleted_by = auth()->user()->id;
                $model->save();
            }
        });
    }
}
