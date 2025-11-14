<?php

namespace App\Models;

use App\Traits\CreateDeleteTrait;
use App\Traits\DateCastTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderLog extends Model
{
    use CreateDeleteTrait;
    use DateCastTrait;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

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

    public function getForeignKeyName(): string
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
        DB::transaction(function () {
            if ($this->isEmrOrder()) {
                $this->rollbackEmrAssignments();
            }

            $blade = optional($this->order)->blade;

            switch ($blade) {
                case Order::BLADE_VACATION:
                    $this->rollbackVacationOrder();
                    break;
                case Order::BLADE_BUSINESS_TRIP:
                    $this->rollbackBusinessTripOrder();
                    break;
            }

            $this->forceDelete();
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if ($this->filterValueIsEmpty($value)) {
                continue;
            }

            if ($field === 'order_no') {
                $query->where($field, 'LIKE', '%'.trim((string) $value).'%');
                continue;
            }

            if ($field === 'given_date' && is_array($value)) {
                [$start, $end] = $this->normalizeDateRange($value);
                $query->whereBetween($field, [$start, $end]);
            }
        }

        return $query;
    }

    protected function rollbackEmrAssignments(): void
    {
        $this->loadMissing('personnels');

        foreach ($this->personnels as $personnel) {
            $this->restoreCandidateFromPersonnel($personnel);
            $this->rollbackStaffSchedule($personnel);
            $personnel->delete();
        }
    }

    protected function restoreCandidateFromPersonnel(Personnel $personnel): void
    {
        $candidateId = (int) str_replace('NMZD', '', (string) $personnel->tabel_no);

        if ($candidateId <= 0) {
            return;
        }

        Candidate::whereKey($candidateId)->update(['status_id' => 30]);
    }

    protected function rollbackStaffSchedule(Personnel $personnel): void
    {
        $staffSchedule = StaffSchedule::query()
            ->where('structure_id', $personnel->structure_id)
            ->where('position_id', $personnel->position_id)
            ->first();

        if (! $staffSchedule) {
            return;
        }

        $updatedData = [
            'total' => max(0, (int) $staffSchedule->total - 1),
        ];

        $vacancyField = $personnel->is_pending ? 'vacant' : 'filled';
        $updatedData[$vacancyField] = max(0, (int) $staffSchedule->{$vacancyField} - 1);

        $staffSchedule->update($updatedData);
    }

    protected function rollbackVacationOrder(): void
    {
        $vacation = $this->vacations()->first();

        if (! $vacation) {
            return;
        }

        $this->vacations()->forceDelete();

        $year = $vacation->start_date instanceof Carbon
            ? $vacation->start_date->year
            : Carbon::parse($vacation->start_date)->year;

        Vacation::where([
            'tabel_no' => $vacation->tabel_no,
            'year' => $year,
        ])->increment('remaining_days', (int) $vacation->duration);
    }

    protected function isEmrOrder(): bool
    {
        return (int) $this->order_id === Order::IG_EMR;
    }

    protected function filterValueIsEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->filterValueIsEmpty($item)) {
                    return false;
                }
            }

            return true;
        }

        if (is_bool($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === null;
    }

    protected function normalizeDateRange(array $value, ?string $defaultMin = null, ?string $defaultMax = null): array
    {
        $defaultMin ??= '1990-01-01';
        $defaultMax ??= Carbon::now()->format('Y-m-d');

        $min = $this->normalizeDateValue($value['min'] ?? null, $defaultMin);
        $max = $this->normalizeDateValue($value['max'] ?? null, $defaultMax);

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    protected function normalizeDateValue(?string $value, string $fallback): string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    protected function rollbackBusinessTripOrder(): void
    {
        $this->businessTrips()->forceDelete();
    }
}
