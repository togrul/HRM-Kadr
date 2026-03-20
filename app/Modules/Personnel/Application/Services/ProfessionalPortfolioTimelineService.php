<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\Personnel;
use App\Models\PersonnelEventRecord;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use Illuminate\Support\Collection;

class ProfessionalPortfolioTimelineService
{
    public function build(Personnel $personnel, ?string $search = null): Collection
    {
        $search = filled($search) ? mb_strtolower(trim((string) $search)) : null;

        $eventItems = $personnel->eventRecords()
            ->verified()
            ->latest('start_date')
            ->limit(50)
            ->get()
            ->map(fn (PersonnelEventRecord $record) => [
                'type' => 'event',
                'occurred_at' => optional($record->start_date)?->toDateString(),
                'title' => $record->title,
                'role' => __('personnel::portfolio.options.participation_role.'.$record->participation_role),
                'summary' => $record->result_summary ?: $record->impact_summary,
                'status' => $record->verification_status,
                'record_id' => $record->id,
            ]);

        $mediaItems = $personnel->mediaMentions()
            ->whereIn('verification_status', [
                PersonnelMediaMention::STATUS_VERIFIED,
                PersonnelMediaMention::STATUS_BROKEN_LINK,
                PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
            ])
            ->latest('published_at')
            ->limit(50)
            ->get()
            ->map(fn (PersonnelMediaMention $record) => [
                'type' => 'media',
                'occurred_at' => optional($record->published_at)?->toDateString(),
                'title' => $record->headline,
                'role' => __('personnel::portfolio.options.mention_type.'.$record->mention_type),
                'summary' => $record->summary,
                'status' => $record->verification_status,
                'record_id' => $record->id,
            ]);

        $projectItems = $personnel->projectRecords()
            ->verified()
            ->latest('start_date')
            ->limit(50)
            ->get()
            ->map(fn (PersonnelProjectRecord $record) => [
                'type' => 'project',
                'occurred_at' => optional($record->start_date)?->toDateString(),
                'title' => $record->project_name,
                'role' => $record->role_title,
                'summary' => $record->impact_summary ?: $record->outcome_summary,
                'status' => $record->verification_status,
                'record_id' => $record->id,
            ]);

        return $eventItems
            ->concat($mediaItems)
            ->concat($projectItems)
            ->when($search, function (Collection $items) use ($search) {
                return $items->filter(function (array $item) use ($search) {
                    return str_contains(mb_strtolower(($item['title'] ?? '').' '.($item['summary'] ?? '').' '.($item['role'] ?? '')), $search);
                });
            })
            ->sortByDesc('occurred_at')
            ->values();
    }
}
