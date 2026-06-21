<?php

namespace App\Modules\Services\Livewire\Settings;

use App\Models\AppealStatus;
use App\Models\ChiefDelegation;
use App\Models\Personnel;
use App\Models\Setting;
use App\Services\Chief\ChiefResolver;
use App\Support\Language\AzerbaijaniDateFormatter;
use App\Support\Translations\ModuleTranslation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['settingsUpdated', 'settingsWasDeleted'])]
class SettingsList extends Component
{
    use AuthorizesRequests;

    private const CANDIDATE_FILTER_KEYS = ['fullname', 'gender', 'results', 'age', 'appeal_date'];

    public string $section = 'general';

    public $setting = [];
    public array $candidateStatusWhitelist = [
        'military' => [],
        'civilian' => [],
    ];
    public array $candidatePresetSettings = [
        'military' => [
            'default_status' => 'all',
            'show_deleted_tab' => true,
        ],
        'civilian' => [
            'default_status' => 'all',
            'show_deleted_tab' => true,
        ],
    ];
    public array $candidateEnabledFilters = [
        'military' => [],
        'civilian' => [],
    ];
    public array $candidateStatuses = [];
    public ?int $chiefPersonnelId = null;
    public array $chiefSnapshot = [];
    public array $chiefDelegationForm = [
        'delegate_personnel_id' => null,
        'starts_at' => null,
        'ends_at' => null,
        'reason' => null,
        'basis_document' => null,
    ];

    public function mount(string $section = 'general'): void
    {
        $this->section = in_array($section, ['general', 'candidate'], true) ? $section : 'general';

        $this->candidateStatuses = AppealStatus::query()
            ->where('locale', app()->getLocale())
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (AppealStatus $status) => [
                'id' => (int) $status->id,
                'name' => $status->name,
            ])
            ->values()
            ->all();

        $this->loadCandidateStatusWhitelist();
        $this->loadChiefGovernance();
    }

    public function updatedSetting($value, $name)
    {
        $_key = explode('.', $name)[0];
        $_setting = Setting::where('id', $this->setting[$_key]['id'])->firstOrFail();
        $_setting->update([
            'value' => $value,
        ]);

        $this->dispatch('settingsUpdated', __('services::settings.messages.saved'));
    }

    public function setDeleteSettings($settingsId)
    {
        $this->dispatch('setDeleteSettings', $settingsId);
    }

    public function saveCandidateStatusWhitelist(): void
    {
        foreach ($this->candidateWhitelistSettingKeys() as $mode => $key) {
            $normalized = $this->parseWhitelistInput($this->candidateStatusWhitelist[$mode] ?? '');

            Setting::updateOrCreate(
                ['name' => $key],
                [
                    'value' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
                    'type' => 'string',
                ]
            );
        }

        foreach ($this->candidatePresetSettingKeys() as $mode => $keys) {
            $defaultStatus = $this->normalizeDefaultStatus($this->candidatePresetSettings[$mode]['default_status'] ?? 'all');
            $showDeleted = (bool) ($this->candidatePresetSettings[$mode]['show_deleted_tab'] ?? true);

            Setting::updateOrCreate(
                ['name' => $keys['default_status']],
                [
                    'value' => (string) $defaultStatus,
                    'type' => 'string',
                ]
            );

            Setting::updateOrCreate(
                ['name' => $keys['show_deleted_tab']],
                [
                    'value' => $showDeleted ? '1' : '0',
                    'type' => 'bool',
                ]
            );

            $filters = $this->normalizeEnabledFilters($this->candidateEnabledFilters[$mode] ?? [], $mode);
            Setting::updateOrCreate(
                ['name' => $keys['enabled_filters']],
                [
                    'value' => json_encode($filters, JSON_UNESCAPED_UNICODE),
                    'type' => 'string',
                ]
            );
        }

        $this->loadCandidateStatusWhitelist();
        $this->dispatch('settingsUpdated', __('services::settings.messages.saved'));
    }

    public function selectAllCandidateStatuses(string $mode): void
    {
        if (! array_key_exists($mode, $this->candidateWhitelistSettingKeys())) {
            return;
        }

        $this->candidateStatusWhitelist[$mode] = collect($this->candidateStatuses)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    public function clearAllCandidateStatuses(string $mode): void
    {
        if (! array_key_exists($mode, $this->candidateWhitelistSettingKeys())) {
            return;
        }

        $this->candidateStatusWhitelist[$mode] = [];
    }

    public function saveChiefPersonnel(): void
    {
        if ($this->chiefPersonnelId) {
            Setting::updateOrCreate(
                ['name' => 'Chief personnel id'],
                ['value' => (string) $this->chiefPersonnelId, 'type' => 'string']
            );
        } else {
            Setting::query()->whereIn('name', ['Chief personnel id', 'Chief personnel_id', 'chief_personnel_id'])->delete();
        }

        $this->syncLegacyChiefSettings();
        $this->loadChiefGovernance();

        $this->dispatch('settingsUpdated', __('services::settings.messages.saved'));
    }

    public function createChiefDelegation(): void
    {
        $validated = $this->validate([
            'chiefDelegationForm.delegate_personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'chiefDelegationForm.starts_at' => ['required'],
            'chiefDelegationForm.ends_at' => ['nullable'],
            'chiefDelegationForm.reason' => ['nullable', 'string', 'max:255'],
            'chiefDelegationForm.basis_document' => ['nullable', 'string', 'max:255'],
        ], [], [
            'chiefDelegationForm.delegate_personnel_id' => 'Vəzifəni icra edən əməkdaş',
            'chiefDelegationForm.starts_at' => 'Başlama tarixi',
            'chiefDelegationForm.ends_at' => 'Bitmə tarixi',
            'chiefDelegationForm.reason' => 'Səbəb',
            'chiefDelegationForm.basis_document' => 'Əsas sənəd',
        ]);

        $form = $validated['chiefDelegationForm'];

        // The date pickers emit "DD.MM.YYYY" (app-wide), not ISO — parse format-agnostically
        // (ISO / DD.MM.YYYY / "19.05.2026-cı il") and store as Y-m-d so the date-range
        // resolution works. Reject unparseable dates instead of silently storing 0000-00-00.
        $dates = app(AzerbaijaniDateFormatter::class);
        $startsAt = $dates->parse($form['starts_at']);
        if ($startsAt === null) {
            $this->addError('chiefDelegationForm.starts_at', 'Başlama tarixi düzgün deyil.');

            return;
        }

        $endsAt = null;
        if (filled($form['ends_at'])) {
            $endsAt = $dates->parse($form['ends_at']);
            if ($endsAt === null) {
                $this->addError('chiefDelegationForm.ends_at', 'Bitmə tarixi düzgün deyil.');

                return;
            }
            if ($endsAt->lt($startsAt)) {
                $this->addError('chiefDelegationForm.ends_at', 'Bitmə tarixi başlama tarixindən əvvəl ola bilməz.');

                return;
            }
        }

        $chiefId = $this->chiefPersonnelId ?: data_get(app(ChiefResolver::class)->current(), 'permanent_chief_personnel_id');
        if (! $chiefId) {
            $this->addError('chiefDelegationForm.delegate_personnel_id', 'Daimi rəhbər təyin edilməyib.');

            return;
        }

        ChiefDelegation::query()->create([
            'chief_personnel_id' => (int) $chiefId,
            'delegate_personnel_id' => (int) $form['delegate_personnel_id'],
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt?->toDateString(),
            'reason' => $form['reason'] ?? null,
            'basis_document' => $form['basis_document'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        $this->resetChiefDelegationForm();

        $this->syncLegacyChiefSettings();
        $this->loadChiefGovernance();

        $this->dispatch('settingsUpdated', 'Rəhbər həvaləsi yaradıldı.');
    }

    public function revokeChiefDelegation(int $delegationId): void
    {
        $delegation = ChiefDelegation::query()
            ->whereKey($delegationId)
            ->where('is_active', true)
            ->firstOrFail();

        $delegation->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
        ]);

        $this->syncLegacyChiefSettings();
        $this->loadChiefGovernance();

        $this->dispatch('settingsUpdated', 'Rəhbər həvaləsi dayandırıldı.');
    }

    public function resetChiefDelegationForm(): void
    {
        $this->resetErrorBag([
            'chiefDelegationForm.delegate_personnel_id',
            'chiefDelegationForm.starts_at',
            'chiefDelegationForm.ends_at',
            'chiefDelegationForm.reason',
            'chiefDelegationForm.basis_document',
        ]);

        $this->chiefDelegationForm = [
            'delegate_personnel_id' => null,
            'starts_at' => null,
            'ends_at' => null,
            'reason' => null,
            'basis_document' => null,
        ];
    }

    public function render()
    {
        $settings = collect();

        if ($this->section === 'general') {
            $settings = Setting::query()
                ->whereNotIn('name', $this->candidateManagedSettingKeys())
                ->whereNotIn('name', $this->chiefManagedSettingKeys())
                ->whereNotIn('name', $this->coefficientSettingKeys())
                ->get();
        }

        $coefficientSettings = $this->section === 'general'
            ? Setting::query()
                ->whereIn('name', $this->coefficientSettingKeys())
                ->get()
                ->sortBy(fn (Setting $setting) => array_search($setting->name, $this->coefficientSettingKeys(), true))
                ->values()
            : collect();

        $this->setting = $settings
            ->concat($coefficientSettings)
            ->values()
            ->toArray();

        $coefficientSettingIndexes = collect($this->setting)
            ->mapWithKeys(fn (array $setting, int $index) => [(string) $setting['name'] => $index])
            ->only($this->coefficientSettingKeys())
            ->all();

        $chiefDelegations = $this->activeChiefDelegations();

        return view('services::livewire.services.settings.settings-list', compact(
            'settings',
            'chiefDelegations',
            'coefficientSettings',
            'coefficientSettingIndexes'
        ));
    }

    private function candidateWhitelistSettingKeys(): array
    {
        return [
            'military' => 'candidates.list_presets.military.status_whitelist',
            'civilian' => 'candidates.list_presets.civilian.status_whitelist',
        ];
    }

    private function candidatePresetSettingKeys(): array
    {
        return [
            'military' => [
                'default_status' => 'candidates.list_presets.military.default_status',
                'show_deleted_tab' => 'candidates.list_presets.military.show_deleted_tab',
                'enabled_filters' => 'candidates.list_presets.military.enabled_filters',
            ],
            'civilian' => [
                'default_status' => 'candidates.list_presets.civilian.default_status',
                'show_deleted_tab' => 'candidates.list_presets.civilian.show_deleted_tab',
                'enabled_filters' => 'candidates.list_presets.civilian.enabled_filters',
            ],
        ];
    }

    private function candidateManagedSettingKeys(): array
    {
        $whitelistKeys = array_values($this->candidateWhitelistSettingKeys());
        $presetKeys = collect($this->candidatePresetSettingKeys())
            ->flatMap(fn (array $keys) => array_values($keys))
            ->values()
            ->all();

        return array_values(array_unique(array_merge($whitelistKeys, $presetKeys)));
    }

    private function chiefManagedSettingKeys(): array
    {
        return ['Chief', 'Chief rank', 'Chief personnel id', 'Chief personnel_id', 'chief_personnel_id'];
    }

    private function coefficientSettingKeys(): array
    {
        return ['Work coefficient', 'Education coefficient'];
    }

    private function loadCandidateStatusWhitelist(): void
    {
        $settings = Setting::query()
            ->whereIn('name', $this->candidateManagedSettingKeys())
            ->pluck('value', 'name')
            ->toArray();

        foreach ($this->candidateWhitelistSettingKeys() as $mode => $key) {
            $parsed = $this->parseWhitelistInput($settings[$key] ?? '');
            $this->candidateStatusWhitelist[$mode] = array_map(static fn (int $id) => (string) $id, $parsed);
        }

        foreach ($this->candidatePresetSettingKeys() as $mode => $keys) {
            $default = $settings[$keys['default_status']] ?? config("candidates.list_presets.{$mode}.default_status", 'all');
            $showDeletedRaw = $settings[$keys['show_deleted_tab']] ?? config("candidates.list_presets.{$mode}.show_deleted_tab", true);
            $enabledFiltersRaw = $settings[$keys['enabled_filters']] ?? config("candidates.list_presets.{$mode}.enabled_filters", []);

            $this->candidatePresetSettings[$mode]['default_status'] = (string) $this->normalizeDefaultStatus($default);
            $this->candidatePresetSettings[$mode]['show_deleted_tab'] = $this->toBool($showDeletedRaw);
            $this->candidateEnabledFilters[$mode] = array_map('strval', $this->normalizeEnabledFilters($enabledFiltersRaw, $mode));
        }
    }

    private function loadChiefGovernance(): void
    {
        $manualChief = Setting::query()
            ->whereIn('name', ['Chief personnel id', 'Chief personnel_id', 'chief_personnel_id'])
            ->pluck('value', 'name')
            ->toArray();

        $selected = collect(['Chief personnel id', 'Chief personnel_id', 'chief_personnel_id'])
            ->map(fn (string $key) => $manualChief[$key] ?? null)
            ->first(fn ($value) => is_numeric($value));

        $this->chiefPersonnelId = is_numeric($selected) ? (int) $selected : null;
        $this->chiefSnapshot = app(ChiefResolver::class)->current();
    }

    private function syncLegacyChiefSettings(): void
    {
        $snapshot = app(ChiefResolver::class)->current();

        Setting::updateOrCreate(
            ['name' => 'Chief'],
            ['value' => (string) data_get($snapshot, 'fullname', ''), 'type' => 'string']
        );

        Setting::updateOrCreate(
            ['name' => 'Chief rank'],
            ['value' => (string) data_get($snapshot, 'title', ''), 'type' => 'string']
        );
    }

    public function chiefPersonnelOptions(): array
    {
        return Personnel::query()
            ->select('personnels.id', 'personnels.surname', 'personnels.name', 'personnels.patronymic', 'positions.name as position_name')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->whereNull('personnels.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('personnels.leave_work_date')
                    ->orWhereDate('personnels.leave_work_date', '>=', now()->toDateString());
            })
            ->orderByDesc('positions.approval_rank')
            ->orderBy('personnels.surname')
            ->limit(200)
            ->get()
            ->map(fn ($personnel) => [
                'id' => (int) $personnel->id,
                'label' => trim($personnel->surname.' '.$personnel->name.' '.$personnel->patronymic),
                'position' => (string) $personnel->position_name,
            ])
            ->all();
    }

    private function activeChiefDelegations()
    {
        return ChiefDelegation::query()
            ->with(['chief:id,surname,name,patronymic', 'delegate:id,surname,name,patronymic'])
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->latest('starts_at')
            ->limit(10)
            ->get();
    }

    private function parseWhitelistInput(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_filter(array_map('intval', $value), static fn (int $v) => $v > 0)));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_unique(array_filter(array_map('intval', $decoded), static fn (int $v) => $v > 0)));
            }

            $parts = array_filter(array_map('trim', explode(',', $value)), static fn (string $v) => $v !== '');

            return array_values(array_unique(array_filter(array_map('intval', $parts), static fn (int $v) => $v > 0)));
        }

        return [];
    }

    private function normalizeDefaultStatus(mixed $value): string
    {
        if (is_numeric($value)) {
            return (string) ((int) $value);
        }

        if (is_string($value)) {
            $normalized = trim($value);

            if ($normalized === 'all' || $normalized === 'deleted') {
                return $normalized;
            }

            if (is_numeric($normalized)) {
                return (string) ((int) $normalized);
            }
        }

        return 'all';
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private function normalizeEnabledFilters(mixed $value, ?string $mode = null): array
    {
        $raw = [];

        if (is_array($value)) {
            $raw = $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = array_filter(array_map('trim', explode(',', $value)), static fn (string $v) => $v !== '');
            }
        }

        $allowed = self::CANDIDATE_FILTER_KEYS;
        if ($mode === 'civilian') {
            $allowed = array_values(array_filter($allowed, static fn (string $key) => $key !== 'results'));
        }

        return array_values(array_unique(array_intersect(array_map('strval', $raw), $allowed)));
    }

    public function candidateFilterOptionsForMode(string $mode): array
    {
        $options = [
            ['key' => 'fullname', 'label' => __('services::common.labels.fullname')],
            ['key' => 'gender', 'label' => __('services::common.labels.gender')],
            ['key' => 'results', 'label' => __('services::common.labels.test_results')],
            ['key' => 'age', 'label' => __('services::common.labels.age')],
            ['key' => 'appeal_date', 'label' => __('services::common.labels.appeal_date')],
        ];

        if ($mode === 'civilian') {
            $options = array_values(array_filter($options, static fn (array $option) => $option['key'] !== 'results'));
        }

        return $options;
    }

    public function candidateModes(): array
    {
        return [
            'military' => [
                'title' => __('services::settings.labels.military_mode_status_ids'),
                'accent' => 'emerald',
            ],
            'civilian' => [
                'title' => __('services::settings.labels.civilian_mode_status_ids'),
                'accent' => 'sky',
            ],
        ];
    }

    public function resolveSettingLabel(string $value): string
    {
        return match ($value) {
            'Work coefficient' => 'İş əmsalı',
            'Education coefficient' => 'Təhsil əmsalı',
            default => ModuleTranslation::resolveStoredText($value),
        };
    }

    public function settingIconName(string $value): string
    {
        return match ($value) {
            'Work coefficient' => 'briefcase',
            'Education coefficient' => 'academic',
            default => 'settings',
        };
    }
}
