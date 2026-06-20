<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TrainingDeliveryRecord extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'training_session_id',
        'personnel_id',
        'training_program_id',
        'training_competency_id',
        'training_need_item_id',
        'attended_hours',
        'result_status',
        'certificate_path',
        'certificate_name',
        'completed_at',
    ];

    protected $casts = [
        'attended_hours' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    public function trainingNeed(): BelongsTo
    {
        return $this->belongsTo(TrainingNeedItem::class, 'training_need_item_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('training_delivery_record')
            ->logFillable()
            ->logOnlyDirty();
    }

    public function certificateExtension(): ?string
    {
        $name = $this->certificate_name ?: $this->certificate_path;

        return $name ? strtolower((string) pathinfo($name, PATHINFO_EXTENSION)) : null;
    }

    public function certificateUrl(): ?string
    {
        if (! $this->certificate_path) {
            return null;
        }

        return Storage::disk('public')->url($this->certificate_path);
    }

    public function isPreviewableImage(): bool
    {
        return in_array($this->certificateExtension(), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    public function isPreviewablePdf(): bool
    {
        return $this->certificateExtension() === 'pdf';
    }
}
