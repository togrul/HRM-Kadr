<?php

namespace App\Support\Livewire;

use App\Models\Personnel;
use App\Services\StructureScope;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Əmək haqqı / kompensasiya iş sahələrində işçidən törənən sətirlərin struktur
 * görünürlüyü: istifadəçi yalnız öz strukturlarındakı işçilərin maaşını, hesab-faktura
 * sətrini, kreditini görür. «Bütün strukturlar» bayrağı heç nə dəyişmir; struktur
 * verilməmiş istifadəçi heç nə görmür (fail closed).
 */
trait ScopesPersonnelByStructure
{
    protected function personnelScope(): StructureScope
    {
        return app(StructureService::class)->scopeFor();
    }

    /** Tabel nömrəsinin işçisi istifadəçinin görünürlüyündədirmi. */
    protected function tabelInScope(?string $tabelNo): bool
    {
        if ($tabelNo === null || $tabelNo === '') {
            return false;
        }

        $scope = $this->personnelScope();

        if ($scope->isAll()) {
            return true;
        }

        return $scope->constrain(Personnel::query()->where('tabel_no', $tabelNo), 'structure_id')->exists();
    }

    /**
     * tabel_no ilə işçiyə bağlanan modelin (PersonnelTrait) sorğusunu məhdudlaşdırır.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    protected function scopeByPersonnel(Builder $query, string $relation = 'personnel'): Builder
    {
        return $this->personnelScope()->constrainThrough($query, $relation);
    }
}
