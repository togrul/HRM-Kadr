<?php

namespace App\Modules\Services\Livewire\Roles;

use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

#[On('permissionWasDeleted')]
class Permissions extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $permission_id;

    public $permission_name;

    public string $permission_description = '';

    public string $search = '';

    public int $perPage = 25;

    public bool $showPermissionModal = false;

    protected function rules(): array
    {
        return [
            'permission_name' => [
                'required',
                Rule::unique('permissions', 'name')->ignore($this->permission_id),
            ],
            'permission_description' => ['required', 'string', 'min:12'],
        ];
    }

    public function createPermission(): void
    {
        $this->resetInputFields();
        $this->resetErrorBag();
        $this->showPermissionModal = true;
    }

    public function editPermission($id): void
    {
        $permission = Permission::findOrFail($id);
        $this->permission_id = $id;
        $this->permission_name = $permission->name;
        $this->permission_description = (string) ($permission->description ?? '');
        $this->resetErrorBag();
        $this->showPermissionModal = true;
    }

    public function closePermissionModal(): void
    {
        $this->showPermissionModal = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function store(): void
    {
        $data = $this->validate();

        if ($this->permission_id) {
            $permission = Permission::findOrFail($this->permission_id);
            $permission->forceFill([
                'name' => $data['permission_name'],
                'description' => $data['permission_description'],
            ])->save();
        } else {
            $permission = new Permission;
            $permission->forceFill([
                'name' => $data['permission_name'],
                'description' => $data['permission_description'],
                'guard_name' => 'web',
            ])->save();
        }

        $this->dispatch('permissionUpdated', __('services::roles.messages.permission_saved'));
        $this->showPermissionModal = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function setDeletePermission($permissionId)
    {
        $this->dispatch('setDeletePermission', $permissionId);
    }

    public function cancel(): void
    {
        $this->closePermissionModal();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function resetInputFields()
    {
        $this->permission_name = '';
        $this->permission_description = '';
        $this->permission_id = null;
    }

    public function moduleBadge(string $permissionName): array
    {
        $normalized = Str::of($permissionName)->lower()->replace('_', '-')->toString();

        return match (true) {
            str_contains($normalized, 'attendance') => ['label' => __('services::roles.badges.modules.attendance'), 'mode' => 'sky'],
            str_contains($normalized, 'order') || str_contains($normalized, 'template') || str_contains($normalized, 'component') => ['label' => __('services::roles.badges.modules.orders'), 'mode' => 'amber'],
            str_contains($normalized, 'candidate') => ['label' => __('services::roles.badges.modules.candidates'), 'mode' => 'purple'],
            str_contains($normalized, 'leave') || str_contains($normalized, 'vacation') || str_contains($normalized, 'business-trip') => ['label' => __('services::roles.badges.modules.time_off'), 'mode' => 'green'],
            str_contains($normalized, 'structure') || str_contains($normalized, 'staff') || str_contains($normalized, 'personnel') => ['label' => __('services::roles.badges.modules.workforce'), 'mode' => 'blue'],
            str_contains($normalized, 'service') || str_contains($normalized, 'role') || str_contains($normalized, 'permission') || str_contains($normalized, 'menu') || str_contains($normalized, 'user') || str_contains($normalized, 'rank') => ['label' => __('services::roles.badges.modules.admin'), 'mode' => 'secondary'],
            default => ['label' => __('services::roles.badges.modules.general'), 'mode' => 'secondary'],
        };
    }

    public function riskBadge(string $permissionName): array
    {
        $normalized = Str::of($permissionName)->lower()->replace('_', '-')->toString();

        return match (true) {
            str_contains($normalized, 'delete')
                || str_contains($normalized, 'remove')
                || str_contains($normalized, 'approve')
                || str_contains($normalized, 'reject')
                || str_contains($normalized, 'publish')
                || str_contains($normalized, 'close')
                || str_contains($normalized, 'manage')
                || str_contains($normalized, 'settings')
                    => ['label' => __('services::roles.badges.risks.high'), 'mode' => 'red'],
            str_contains($normalized, 'create')
                || str_contains($normalized, 'add')
                || str_contains($normalized, 'edit')
                || str_contains($normalized, 'update')
                || str_contains($normalized, 'assign')
                || str_contains($normalized, 'export')
                || str_contains($normalized, 'import')
                    => ['label' => __('services::roles.badges.risks.medium'), 'mode' => 'amber'],
            default => ['label' => __('services::roles.badges.risks.low'), 'mode' => 'green'],
        };
    }

    public function adminBadge(string $permissionName): ?array
    {
        $normalized = Str::of($permissionName)->lower()->replace('_', '-')->toString();

        if (
            str_contains($normalized, 'admin')
            || str_contains($normalized, 'settings')
            || str_contains($normalized, 'role')
            || str_contains($normalized, 'permission')
            || str_contains($normalized, 'manage')
        ) {
            return ['label' => __('services::roles.badges.admin_only'), 'mode' => 'purple'];
        }

        return null;
    }

    public function highlightText(?string $value): string
    {
        $text = (string) $value;
        $needle = trim($this->search);

        if ($text === '') {
            return '';
        }

        if ($needle === '') {
            return e($text);
        }

        $pattern = '/(' . preg_quote($needle, '/') . ')/iu';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return e($text);
        }

        return collect($parts)
            ->map(function (string $part) use ($needle): string {
                if (mb_strtolower($part) === mb_strtolower($needle)) {
                    return '<mark class="rounded bg-amber-100 px-1 text-zinc-900">' . e($part) . '</mark>';
                }

                return e($part);
            })
            ->implode('');
    }

    public function render()
    {
        $permissions = Permission::query()
            ->select('id', 'name', 'description')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($innerQuery): void {
                    $innerQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('services::livewire.services.roles.permissions', compact('permissions'));
    }
}
