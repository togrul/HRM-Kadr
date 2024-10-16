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
        'start_date' => 'date:d.m.Y',
        'end_date' => 'date:d.m.Y',
        'order_date' => 'date:d.m.Y',
        'attributes' => 'array'
    ];

    protected $likeFilterFields = [
        'location',
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


    public function scopeFilter($query, array $filters)
    {
        $currentDate = Carbon::now()->format('Y-m-d');

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
                case 'order_type_id':
                    if (isset($value['id'])) {
                        $query->whereHas('order.orderType', function ($qq) use($value) {
                            $qq->where('order_type_id', $value['id']);
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
                    if ($value === 'at_work') {
                        $query->where('end_date', '<', $currentDate);
                    } elseif ($value === 'in_business_trip') {
                        $query->where('end_date', '>', $currentDate);
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
