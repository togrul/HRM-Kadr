<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelVacation extends Model
{
    use HasFactory,PersonnelTrait,SoftDeletes,DateCastTrait;

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
        'added_by',
        'deleted_by',
        'deleted_at'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'return_work_date',
        'order_date'
    ];

    protected $casts = [
        'start_date' => 'date:d.m.Y',
        'end_date' => 'date:d.m.Y',
        'return_work_date' => 'date:d.m.Y',
        'order_date' => 'date:d.m.Y'
    ];

    protected $likeFilterFields = [
        'vacation_places',
        'duration',
        'order_no'
    ];

    public function personDidDelete() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

    public function creator() : BelongsTo
    {
        return $this->belongsTo(User::class,'added_by','id');
    }

    public function deletedBy() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

    public function order() : BelongsTo
    {
        return $this->belongsTo(OrderLog::class,'order_no','order_no');
    }

    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if($field == 'structure_id')
            {
                $structureModel = Structure::with('subs')->find($value['id']);
                if ($structureModel) {
                    $structure = $structureModel->getAllNestedIds();
                }

                $query->whereHas('personnel.structure',function($qq) use($structure){
                    $qq->whereIn('structure_id',$structure);
                });
                continue;
            }
            if($field == 'date')
            {
                $minDate = isset($value['min']) ? Carbon::parse($value['min'])->format('Y-m-d') : null;
                $maxDate = isset($value['max']) ? Carbon::parse($value['max'])->format('Y-m-d') : null;

                if ($minDate) {
                    $query->where('start_date', '>=', $minDate);
                }
                if ($maxDate) {
                    $query->where('end_date', '<=', $maxDate);
                }

                continue;
            }
            if($field == 'vacation_status')
            {
                if($value == 'at_work')
                {
                    $query->where('return_work_date', '<', Carbon::now()->format('Y-m-d'));
                }
                elseif ($value == 'in_vacation')
                {
                    $query->where('return_work_date', '>', Carbon::now()->format('Y-m-d'));
                }
                continue;
            }
            if($field == 'fullname')
            {
                $query->whereHas('personnel',function($qq) use($value){
                    $qq->where(function ($q) use ($value) {
                        $q->where('surname', 'LIKE', "%$value%")
                            ->orWhere('name', 'LIKE', "%$value%")
                            ->orWhere('patronymic', 'LIKE', "%$value%");
                    });
                });
                continue;
            }
            if(in_array($field, $this->likeFilterFields) && $value != null)
            {
                $query->where($field, 'LIKE', "%$value%");
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
            if (!$model->isForceDeleting()) {
                $model->deleted_by = auth()->user()->id;
                $model->save();
            }
        });
    }

}
