<?php

namespace App\Modules\Personnel\Support\ProfessionalPortfolio;

use App\Models\ProfessionalRecordAttachment;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait HandlesPortfolioAttachments
{
    protected function storePortfolioAttachment(?TemporaryUploadedFile $upload, int $personnelId, string $kind, ?ProfessionalRecordAttachment $existing = null): ?ProfessionalRecordAttachment
    {
        if (! $upload) {
            return $existing;
        }

        if ($existing && filled($existing->file_path)) {
            Storage::disk($existing->disk ?: 'public')->delete($existing->file_path);
            $existing->delete();
        }

        $disk = 'public';
        $path = $upload->store("professional-portfolio/{$personnelId}/{$kind}", $disk);

        return ProfessionalRecordAttachment::query()->create([
            'display_name' => pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $upload->getClientOriginalName(),
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $upload->getMimeType(),
            'extension' => $upload->getClientOriginalExtension(),
            'size_bytes' => $upload->getSize(),
            'kind' => $kind,
            'uploaded_by' => auth()->id(),
        ]);
    }
}
