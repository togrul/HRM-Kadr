<?php

namespace App\Models;

use App\Traits\CreateDeleteTrait;
use App\Traits\DateCastTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderLog extends Model
{
    use CreateDeleteTrait,DateCastTrait,HasFactory,LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");
    }

    protected $fillable = [
        'order_id',
        'order_no',
        'order_type_id',
        'given_date',
        'given_by',
        'given_by_rank',
        'description',
        'status_id',
        'creator_id',
        'deleted_by',
    ];

    protected $dates = [
        'given_date',
    ];

    protected $casts = [
        'deleted_at' => self::FORMAT_CAST,
        'given_date' => self::FORMAT_CAST,
        'description' => 'array',
    ];

    public function getForeignKeyName()
    {
        return 'order_no'; // Specify the name of the foreign key you want to use for syncing
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(
            Component::class,
            'order_log_components',
            'order_no',
            'component_id',
            'order_no',
            'id'
        )
            ->withPivot('row_number')
            ->orderBy('row_number');
    }

    public function personnels(): BelongsToMany
    {
        return $this->belongsToMany(
            Personnel::class,
            'order_log_personnels',
            'order_no',
            'tabel_no',
            'order_no',
            'tabel_no'
        );
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id', 'id')
            ->where('locale', config('app.locale'));
    }

    public function orderType(): BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(OrderLogComponentAttributes::class, 'order_no', 'order_no');
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(PersonnelVacation::class, 'order_no', 'order_no');
    }

    public function businessTrips(): HasMany
    {
        return $this->hasMany(PersonnelBusinessTrip::class, 'order_no', 'order_no');
    }

    public function handleDeletion(): void
    {
        if ($this->order_id == Order::IG_EMR) {
            // emre hazir statusuna qaytarmaq
            // vacancy yenile. bos yerleri coxalt dolunu azalt
            foreach ($this->personnels as $personnel) {
                $candidate_id = str_replace('NMZD', '', $personnel->tabel_no);
                Candidate::where('id', $candidate_id)->update(['status_id' => 30]);
                $staff_schedule = StaffSchedule::query()
                    ->where('structure_id', $personnel->structure_id)
                    ->where('position_id', $personnel->position_id)
                    ->first();

                $updatedData = [
                    'total' => max(0, $staff_schedule->total - 1),
                ];

                $vacancyField = $personnel->is_pending ? 'vacant' : 'filled';
                $updatedData[$vacancyField] = max(0, $staff_schedule->{$vacancyField} - 1);

                $staff_schedule->update($updatedData);
                $personnel->delete();
            }
        }

        switch ($this->order->blade) {
            case Order::BLADE_VACATION:
                // mezuniyyet emrini sil ve ona aid olan verilenleri evvelki veziyyetine geri qaytar.
                $vacation = $this->vacations->first();
                $this->vacations()->forceDelete();
                Vacation::where([
                    'tabel_no' => $vacation->tabel_no,
                    'year' => $vacation->start_date->year,
                ])->increment('remaining_days', $vacation->duration);
                break;
            case Order::BLADE_BUSINESS_TRIP:
                $this->businessTrips()->forceDelete();
                break;
        }

        $this->forceDelete();
    }

    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if ($field == 'order_no') {
                $query->where($field, 'LIKE', "%$value%");

                continue;
            }
            if ($field == 'given_date') {
                $_min = empty($value['min']) ? '1990-01-01' : Carbon::parse($value['min'])->format('Y-m-d');
                $_max = empty($value['max'])
                        ? Carbon::now()->format('Y-m-d')
                        : Carbon::parse($value['max'])->format('Y-m-d');
                $query->whereBetween($field, [$_min, $_max]);
            }
        }

        return $query;
    }
}
