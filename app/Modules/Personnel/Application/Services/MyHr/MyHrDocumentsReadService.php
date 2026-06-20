<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\Personnel;
use App\Models\PersonnelDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MyHrDocumentsReadService
{
    public function build(Personnel $personnel): array
    {
        $documents = PersonnelDocument::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->where('employee_visibility', 'visible')
            ->where(function ($query): void {
                $query->whereNull('visible_from')
                    ->orWhere('visible_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('visible_until')
                    ->orWhere('visible_until', '>=', now());
            })
            ->latest('created_at')
            ->get(['id', 'tabel_no', 'file', 'filename', 'employee_visibility', 'visible_from', 'visible_until', 'created_at']);

        $rows = $documents->map(function (PersonnelDocument $document): array {
            $extension = Str::upper((string) pathinfo((string) ($document->file ?: $document->filename), PATHINFO_EXTENSION) ?: 'FILE');
            $category = $this->category($extension);

            return [
                'id' => $document->id,
                'title' => $document->filename ?: basename((string) $document->file),
                'extension' => $extension,
                'category' => $category,
                'category_label' => __('personnel::my_hr.documents.categories.'.$category),
                'url' => Storage::disk('public')->url((string) $document->file),
                'created_at' => optional($document->created_at)?->format('d.m.Y H:i') ?: '—',
                'size_label' => $this->sizeLabel((string) $document->file),
            ];
        })->all();

        $byCategory = collect($rows)->groupBy('category');

        return [
            'summary' => [
                'total' => count($rows),
                'pdf' => $byCategory->get('pdf', collect())->count(),
                'image' => $byCategory->get('image', collect())->count(),
                'other' => $byCategory->get('other', collect())->count(),
            ],
            'documents' => $rows,
        ];
    }

    private function category(string $extension): string
    {
        return match (Str::lower($extension)) {
            'pdf' => 'pdf',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg' => 'image',
            default => 'other',
        };
    }

    private function sizeLabel(string $path): string
    {
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return '—';
        }

        $bytes = (int) Storage::disk('public')->size($path);

        if ($bytes <= 0) {
            return '0 KB';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1).' '.$units[$power];
    }
}
