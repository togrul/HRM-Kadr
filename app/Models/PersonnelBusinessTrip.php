<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelBusinessTrip extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait,SoftDeletes;

    protected $fillable = [
        'tabel_no',
        'location',
        'start_date',
        'end_date',
        'description',
        'attributes',
        'approval_status',
        'approver_personnel_id',
        'fallback_approver_personnel_id',
        'approval_route_source',
        'submission_source',
        'submitted_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
        'order_given_by',
        'order_no',
        'order_date',
        'added_by',
        'deleted_by',
        'deleted_at',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'order_date',
    ];

    protected $casts = [
        'start_date' => self::FORMAT_CAST,
        'end_date' => self::FORMAT_CAST,
        'order_date' => self::FORMAT_CAST,
        'attributes' => 'array',
    ];

    protected $likeFilterFields = [
        'location',
        'order_no',
    ];

    const INTERNAL_BUSINESS_TRIP = 6;

    const FOREIGN_BUSINESS_TRIP = 7;

    public function personDidDelete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'approver_personnel_id');
    }

    public function fallbackApprover(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'fallback_approver_personnel_id');
    }

    public function changeRequests(): MorphMany
    {
        return $this->morphMany(EmployeeRequestChangeRequest::class, 'requestable');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderLog::class, 'order_no', 'order_no');
    }

    public function scopeForeignBusinessTrip($query)
    {
        return $query->whereHas('order', function ($where) {
            $where->where('order_type_id', self::FOREIGN_BUSINESS_TRIP);
        });
    }

    public function scopeInternalBusinessTrip($query)
    {
        return $query->whereHas('order', function ($where) {
            $where->where('order_type_id', self::INTERNAL_BUSINESS_TRIP);
        });
    }

    public function scopeFilter($query, array $filters)
    {
        $currentDate = Carbon::now()->format('Y-m-d');

        foreach ($filters as $field => $value) {
            switch ($field) {
                case 'structure_id':
                    if (! empty($value)) {
                        $structureModel = Structure::with('subs')->find($value);
                        if ($structureModel) {
                            $structure = $structureModel->getAllNestedIds();
                            $query->whereHas('personnel.structure', function ($qq) use ($structure) {
                                $qq->whereIn('structure_id', $structure);
                            });
                        }
                    }
                    break;
                case 'order_type_id':
                    if (! empty($value)) {
                        $query->whereHas('order.orderType', function ($qq) use ($value) {
                            $qq->where('order_type_id', $value);
                        });
                    }
                    break;
                case 'date':
                    $minDate = isset($value['min']) ? Carbon::parse($value['min'])->format('Y-m-d') : null;
                    $maxDate = isset($value['max']) ? Carbon::parse($value['max'])->format('Y-m-d') : null;

                    $query->when($minDate, fn ($q) => $q->where('start_date', '>=', $minDate))
                        ->when($maxDate, fn ($q) => $q->where('end_date', '<=', $maxDate));
                    break;
                case 'business_trip_status':
                    switch ($value) {
                        case 'at_work':
                            $query->where('end_date', '<', $currentDate);
                            break;
                        case 'in_business_trip':
                            $query->where('end_date', '>=', $currentDate);
                            break;
                        case 'deleted':
                            $query->onlyTrashed();
                            break;
                        default:
                            break;
                    }
                    break;
                case 'fullname':
                    $query->whereHas('personnel', function ($qq) use ($value) {
                        $qq->where(function ($q) use ($value) {
                            $q->where('surname', 'LIKE', "%$value%")
                                ->orWhere('name', 'LIKE', "%$value%")
                                ->orWhere('patronymic', 'LIKE', "%$value%");
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

        // Two things to be careful about here.
        //
        // 1. `auth()->user()->id` assumed a signed-in user. A trip recorded from
        //    the console — the finance import runs on a schedule — has none, and
        //    the write died on a null. `auth()->id()` with a fallback is honest:
        //    the record was made by the system, not by nobody.
        //
        // 2. These closures MUST return nothing. `creating` and `deleting` are
        //    halting events: Eloquent dispatches them through `until()`, which
        //    stops at the first listener returning a non-null value. An arrow
        //    body returning the assignment silently swallowed every listener
        //    registered afterwards.
        static::creating(function ($model): void {
            $model->added_by = auth()->id() ?? 1;
        });

        static::deleting(function ($model): void {
            if (! $model->isForceDeleting()) {
                $model->deleted_by = auth()->id() ?? 1;
                $model->save();
            }
        });
    }
}
