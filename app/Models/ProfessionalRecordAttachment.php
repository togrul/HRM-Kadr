<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

        return Storage::disk($this->disk ?: 'public')->url($this->file_path);
    }
}
