<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\PersonnelEventRecord;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use App\Models\ProfessionalEventRegistry;
use App\Models\ProfessionalMediaOutletRegistry;
use App\Models\ProfessionalProjectRegistry;

class ProfessionalPortfolioRegistrySyncService
{
    public function syncAll(): array
    {
        return [
            'events' => $this->syncEventRegistries(),
            'media_outlets' => $this->syncMediaOutletRegistries(),
            'projects' => $this->syncProjectRegistries(),
        ];
    }

    public function syncEventRegistries(): int
    {
        $count = 0;

        PersonnelEventRecord::query()
            ->whereNotNull('registry_key')
            ->orderBy('registry_key')
            ->get()
            ->groupBy('registry_key')
            ->each(function ($records, $registryKey) use (&$count) {
                $this->upsertEventRegistry((string) $registryKey, $records);
                $count++;
            });

        return $count;
    }

    public function syncMediaOutletRegistries(): int
    {
        $count = 0;

        PersonnelMediaMention::query()
            ->whereNotNull('publisher_registry_key')
            ->orderBy('publisher_registry_key')
            ->get()
            ->groupBy('publisher_registry_key')
            ->each(function ($records, $registryKey) use (&$count) {
                $this->upsertMediaRegistry((string) $registryKey, $records);
                $count++;
            });

        return $count;
    }

    public function syncProjectRegistries(): int
    {
        $count = 0;

        PersonnelProjectRecord::query()
            ->whereNotNull('registry_key')
            ->orderBy('registry_key')
            ->get()
            ->groupBy('registry_key')
            ->each(function ($records, $registryKey) use (&$count) {
                $this->upsertProjectRegistry((string) $registryKey, $records);
                $count++;
            });

        return $count;
    }

    public function syncEventRecord(PersonnelEventRecord $record): void
    {
        if (filled($record->registry_key)) {
            $this->upsertEventRegistry(
                (string) $record->registry_key,
                PersonnelEventRecord::query()->where('registry_key', $record->registry_key)->get(),
            );
        }
    }

    public function syncMediaRecord(PersonnelMediaMention $record): void
    {
        if (filled($record->publisher_registry_key)) {
            $this->upsertMediaRegistry(
                (string) $record->publisher_registry_key,
                PersonnelMediaMention::query()->where('publisher_registry_key', $record->publisher_registry_key)->get(),
            );
        }
    }

    public function syncProjectRecord(PersonnelProjectRecord $record): void
    {
        if (filled($record->registry_key)) {
            $this->upsertProjectRegistry(
                (string) $record->registry_key,
                PersonnelProjectRecord::query()->where('registry_key', $record->registry_key)->get(),
            );
        }
    }

    private function upsertEventRegistry(string $registryKey, $records): void
    {
        $first = $records->sortBy('start_date')->first();
        $last = $records->sortByDesc('start_date')->first();

        ProfessionalEventRegistry::query()->updateOrCreate(
            ['registry_key' => $registryKey],
            [
                'event_type' => $first->event_type,
                'title' => $first->title,
                'organizer_name' => $first->organizer_name,
                'country_id' => $first->country_id,
                'first_seen_at' => $first->start_date,
                'last_seen_at' => $last->start_date,
                'records_count' => $records->count(),
                'last_source_record_id' => $last->id,
            ],
        );
    }

    private function upsertMediaRegistry(string $registryKey, $records): void
    {
        $first = $records->sortBy('published_at')->first();
        $last = $records->sortByDesc('published_at')->first();

        ProfessionalMediaOutletRegistry::query()->updateOrCreate(
            ['registry_key' => $registryKey],
            [
                'publisher_name' => $first->publisher_name,
                'publisher_type' => $first->publisher_type,
                'first_seen_at' => $first->published_at,
                'last_seen_at' => $last->published_at,
                'mentions_count' => $records->count(),
                'last_source_record_id' => $last->id,
            ],
        );
    }

    private function upsertProjectRegistry(string $registryKey, $records): void
    {
        $first = $records->sortBy('start_date')->first();
        $last = $records->sortByDesc('start_date')->first();

        ProfessionalProjectRegistry::query()->updateOrCreate(
            ['registry_key' => $registryKey],
            [
                'project_name' => $first->project_name,
                'project_code' => $first->project_code,
                'project_type' => $first->project_type,
                'sponsor_unit_id' => $first->sponsor_unit_id,
                'first_seen_at' => $first->start_date,
                'last_seen_at' => $last->start_date,
                'records_count' => $records->count(),
                'last_source_record_id' => $last->id,
            ],
        );
    }
}
