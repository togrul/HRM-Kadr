<?php

namespace App\Modules\Personnel\Support\Traits\Personnel;

use App\Models\Award;
use App\Models\EducationDocumentType;
use App\Models\Kinship;
use App\Models\Language;
use App\Models\Punishment;
use App\Models\Rank;
use App\Models\RankReason;
use App\Models\ScientificDegreeAndName;

trait ResolvesPersonnelLabelCache
{
    protected array $rankLabelCache = [];

    protected array $rankReasonLabelCache = [];

    protected array $awardLabelCache = [];

    protected array $punishmentLabelCache = [];

    protected array $kinshipLabelCache = [];

    protected array $languageLabelCache = [];

    protected array $degreeLabelCache = [];

    protected array $educationDocumentLabelCache = [];

    public function rankLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        $locale = app()->getLocale();
        $cacheKey = "{$locale}:{$id}";

        if (! array_key_exists($cacheKey, $this->rankLabelCache)) {
            $column = "name_{$locale}";
            $this->rankLabelCache[$cacheKey] = Rank::query()->whereKey($id)->value($column);
        }

        return $this->rankLabelCache[$cacheKey];
    }

    public function rankReasonLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->rankReasonLabelCache)) {
            $this->rankReasonLabelCache[$id] = RankReason::query()->whereKey($id)->value('name');
        }

        return $this->rankReasonLabelCache[$id];
    }

    public function awardLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->awardLabelCache)) {
            $this->awardLabelCache[$id] = Award::query()->whereKey($id)->value('name');
        }

        return $this->awardLabelCache[$id];
    }

    public function punishmentLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->punishmentLabelCache)) {
            $this->punishmentLabelCache[$id] = Punishment::query()->whereKey($id)->value('name');
        }

        return $this->punishmentLabelCache[$id];
    }

    public function kinshipLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->kinshipLabelCache)) {
            $locale = app()->getLocale();
            $column = "name_{$locale}";
            $this->kinshipLabelCache[$id] = Kinship::query()->whereKey($id)->value($column);
        }

        return $this->kinshipLabelCache[$id];
    }

    public function languageLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->languageLabelCache)) {
            $this->languageLabelCache[$id] = Language::query()->whereKey($id)->value('name');
        }

        return $this->languageLabelCache[$id];
    }

    public function scientificDegreeLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->degreeLabelCache)) {
            $this->degreeLabelCache[$id] = ScientificDegreeAndName::query()->whereKey($id)->value('name');
        }

        return $this->degreeLabelCache[$id];
    }

    public function educationDocumentLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->educationDocumentLabelCache)) {
            $this->educationDocumentLabelCache[$id] = EducationDocumentType::query()->whereKey($id)->value('name');
        }

        return $this->educationDocumentLabelCache[$id];
    }
}
