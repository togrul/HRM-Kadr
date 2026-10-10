<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $file_path
 * @property string $disk
 */
class ProfessionalRecordAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'display_name',
        'original_name',
        'file_path',
        'disk',
        'mime_type',
        'extension',
        'size_bytes',
        'kind',
        'uploaded_by',
        'notes',
        'sort_order',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function fileUrl(): ?string
    {
        if (! filled($this->file_path)) {
            return null;
        }

        // Fayl özəl diskdədir; icazəni yoxlayan route ilə verilir.
        return route('personnel.portfolio-attachments.show', $this);
    }
}
