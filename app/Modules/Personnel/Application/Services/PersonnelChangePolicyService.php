<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\PersonnelChangePolicy;
use App\Modules\Personnel\Contracts\ManagesPersonnelChangePolicy;
use App\Modules\Personnel\Contracts\PersonnelChangeMode;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Qurumun dəyişiklik siyasəti: sahə qrupu → rejim. Sətir yoxdursa (və ya cədvəl hələ
 * qurulmayıbsa) reyestrin ilkin rejimi işləyir.
 *
 * Konteyner singleton-udur: işçi formasının hər render-i və model observer-i rejimi
 * soruşur, siyasət isə sorğu başına bir dəfə oxunur. Yazma yaddaşı təmizləyir.
 */
class PersonnelChangePolicyService implements ManagesPersonnelChangePolicy
{
    /** @var array<string, PersonnelChangePolicy>|null */
    private ?array $rows = null;

    public function __construct(private readonly PersonnelFieldGroupRegistry $registry) {}

    public function modeFor(string $group): PersonnelChangeMode
    {
        $row = $this->stored()[$group] ?? null;

        return ($row !== null ? PersonnelChangeMode::tryFrom($row->mode) : null)
            ?? $this->registry->defaultMode($group);
    }

    public function isCustomized(string $group): bool
    {
        $row = $this->stored()[$group] ?? null;

        return $row !== null && PersonnelChangeMode::tryFrom($row->mode) !== null;
    }

    public function rows(): array
    {
        $stored = $this->stored();

        return array_map(function (string $group) use ($stored): array {
            $row = $stored[$group] ?? null;

            return [
                'group' => $group,
                'label' => $this->registry->label($group),
                'description' => $this->registry->description($group),
                'mode' => $this->modeFor($group)->value,
                'default_mode' => $this->registry->defaultMode($group)->value,
                'customized' => $this->isCustomized($group),
                'owner' => $this->registry->owner($group),
                'updated_at' => $row?->updated_at?->format('d.m.Y H:i'),
                'updated_by' => $row?->editor?->name,
            ];
        }, $this->registry->keys());
    }

    public function modeOptions(): array
    {
        $options = [];
        foreach (PersonnelChangeMode::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }

    public function setMode(string $group, string $mode, ?int $userId = null): void
    {
        $this->assertGroup($group);
        $next = PersonnelChangeMode::tryFrom($mode)
            ?? throw new InvalidArgumentException("Unknown change-policy mode [{$mode}].");

        $previous = $this->modeFor($group);
        if ($previous === $next) {
            return;
        }

        // İlkin rejimə qayıdış sətri saxlamır: mənbə yenidən «İlkin» görünür.
        if ($next === $this->registry->defaultMode($group)) {
            $this->resetToDefault($group, $userId);

            return;
        }

        DB::transaction(function () use ($group, $next, $previous, $userId): void {
            $row = PersonnelChangePolicy::query()->updateOrCreate(
                ['field_group' => $group],
                ['mode' => $next->value, 'updated_by' => $userId],
            );

            $this->audit($row, 'updated', $group, $previous, $next, $userId);
        });

        $this->flush();
    }

    public function resetToDefault(string $group, ?int $userId = null): void
    {
        $this->assertGroup($group);
        $row = PersonnelChangePolicy::query()->where('field_group', $group)->first();
        if ($row === null) {
            return;
        }

        $previous = $this->modeFor($group);
        $default = $this->registry->defaultMode($group);

        DB::transaction(function () use ($row, $group, $previous, $default, $userId): void {
            $row->delete();
            $this->audit($row, 'reset', $group, $previous, $default, $userId);
        });

        $this->flush();
    }

    /** Yaddaşdakı siyasəti atır; növbəti sorğu cədvəldən yenidən oxuyur. */
    public function flush(): void
    {
        $this->rows = null;
    }

    /**
     * @return array<string, PersonnelChangePolicy>
     */
    private function stored(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        if (! InstalledTables::has('personnel_change_policies')) {
            return $this->rows = [];
        }

        return $this->rows = PersonnelChangePolicy::query()
            ->with('editor:id,name')
            ->get()
            ->keyBy('field_group')
            ->all();
    }

    private function assertGroup(string $group): void
    {
        if (! $this->registry->has($group)) {
            throw new InvalidArgumentException("Unknown personnel field group [{$group}].");
        }
    }

    private function audit(PersonnelChangePolicy $row, string $event, string $group, PersonnelChangeMode $old, PersonnelChangeMode $new, ?int $userId): void
    {
        $activity = activity('personnel_change_policy')
            ->performedOn($row)
            ->event($event)
            ->withProperties([
                'field_group' => $group,
                'old' => ['mode' => $old->value],
                'attributes' => ['mode' => $new->value],
            ]);

        if ($userId !== null) {
            $activity->causedBy($userId);
        }

        $activity->log("personnel.change_policy.{$event}");
    }
}
