<?php

namespace App\Support\Library;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class AbstractLibraryReadService
{
    use BuildsLibraryDirectoryPayload;

    public function build(string $librarySearch = '', string $personnelSearch = '', string $structureSearch = '', string $positionSearch = ''): array
    {
        return [
            'summary' => $this->summaryData(),
            'analytics' => $this->analyticsData(),
            $this->libraryItemsKey() => $this->libraryItems($librarySearch),
            $this->assignmentItemsKey() => $this->assignmentItems(),
            'personnels' => $this->personnels($personnelSearch),
            'structures' => $this->structures($structureSearch),
            'positions' => $this->positions($positionSearch),
            'recent_assignments' => $this->recentAssignmentsData(),
        ];
    }

    public function buildGeneral(
        string $personnelSearch = '',
        string $structureSearch = '',
        string $positionSearch = '',
        string $pageName = 'libraryRecentAssignmentsPage'
    ): array {
        return [
            'summary' => $this->summaryData(),
            $this->assignmentItemsKey() => $this->assignmentItems(),
            'personnels' => $this->personnels($personnelSearch),
            'structures' => $this->structures($structureSearch),
            'positions' => $this->positions($positionSearch),
            'recent_assignments' => $this->recentAssignmentsPaginatedData(10, $pageName),
        ];
    }

    public function buildSummary(): array
    {
        return [
            'summary' => $this->summaryData(),
        ];
    }

    public function buildLibrary(string $librarySearch = ''): array
    {
        return [
            $this->libraryItemsKey() => $this->libraryItems($librarySearch),
        ];
    }

    public function buildReports(): array
    {
        return [
            'analytics' => $this->analyticsData(),
        ];
    }

    abstract protected function summaryData(): array;

    abstract protected function analyticsData(): array;

    abstract protected function libraryItems(string $librarySearch): array;

    abstract protected function assignmentItems(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function recentAssignmentsData(): array;

    abstract protected function recentAssignmentsPaginatedData(int $perPage, string $pageName): LengthAwarePaginator;

    abstract protected function libraryItemsKey(): string;

    abstract protected function assignmentItemsKey(): string;
}
