<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

class AwardsPunishmentsForm extends Form
{
    public array $award = [];

    public array $awardList = [];

    public array $punishment = [];

    public array $punishmentList = [];

    public function resetForm(): void
    {
        $this->award = $this->defaultAward();
        $this->awardList = [];
        $this->punishment = $this->defaultPunishment();
        $this->punishmentList = [];
    }

    public function resetAward(): void
    {
        $this->award = $this->defaultAward();
    }

    public function resetPunishment(): void
    {
        $this->punishment = $this->defaultPunishment();
    }

    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $personnel->loadMissing(['awards.award', 'punishments.punishment']);

        $this->awardList = $personnel->awards
            ->map(function ($award) {
                $payload = array_replace(
                    $this->defaultAward(),
                    Arr::only($award->toArray(), ['reason', 'given_date', 'is_old'])
                );

                $payload['award_id'] = $award->award_id;

                return $payload;
            })
            ->values()
            ->all();

        $this->punishmentList = $personnel->punishments
            ->map(function ($punishment) {
                $payload = array_replace(
                    $this->defaultPunishment(),
                    Arr::only($punishment->toArray(), ['reason', 'given_date', 'expired_date'])
                );

                $payload['punishment_id'] = $punishment->punishment_id;

                return $payload;
            })
            ->values()
            ->all();
    }

    public function addAwardEntry(): void
    {
        $entry = $this->award;
        $entry['is_old'] = (bool) ($entry['is_old'] ?? false);

        $this->awardList[] = $entry;
        $this->resetAward();
    }

    public function removeAwardEntry(int $index): void
    {
        if (! array_key_exists($index, $this->awardList)) {
            return;
        }

        unset($this->awardList[$index]);
        $this->awardList = array_values($this->awardList);
    }

    public function addPunishmentEntry(): void
    {
        $entry = $this->punishment;
        $entry['expired_date'] = $entry['expired_date'] ?? null;

        $this->punishmentList[] = $entry;
        $this->resetPunishment();
    }

    public function removePunishmentEntry(int $index): void
    {
        if (! array_key_exists($index, $this->punishmentList)) {
            return;
        }

        unset($this->punishmentList[$index]);
        $this->punishmentList = array_values($this->punishmentList);
    }

    protected function defaultAward(): array
    {
        return [
            'award_id' => null,
            'reason' => null,
            'given_date' => null,
            'is_old' => false,
        ];
    }

    protected function defaultPunishment(): array
    {
        return [
            'punishment_id' => null,
            'reason' => null,
            'given_date' => null,
            'expired_date' => null,
        ];
    }

    public function awardsForPersistence(): array
    {
        return $this->awardList ?? [];
    }

    public function punishmentsForPersistence(): array
    {
        return $this->punishmentList ?? [];
    }
}
