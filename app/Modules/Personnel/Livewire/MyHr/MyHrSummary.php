<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestsReadService;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Personnel $personnel
 */
class MyHrSummary extends Component
{
    /** Rows shown in the overview's request preview; the requests tab owns the full list. */
    public const RECENT_LIMIT = 4;

    public int $personnelId;

    public function mount(int $personnelId): void
    {
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
    }

    #[Computed]
    public function personnel(): Personnel
    {
        return Personnel::query()
            ->select([
                'id',
                'tabel_no',
                'surname',
                'name',
                'patronymic',
                'email',
                'phone',
                'mobile',
                'structure_id',
                'position_id',
                'join_work_date',
            ])
            ->with([
                'position:id,name',
                'structure' => fn ($query) => $query
                    ->select('id', 'parent_id', 'name')
                    ->withRecursive('parent', false),
            ])
            ->findOrFail($this->personnelId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function recentRequests(): array
    {
        $payload = app(MyHrRequestsReadService::class)->build($this->personnel);

        return array_slice($payload['rows'], 0, self::RECENT_LIMIT);
    }

    /**
     * Quick links the employee is actually allowed to start. The request tiles follow the
     * self-service submit permissions; the payslip tile is just tab navigation.
     *
     * @return array<int, array{key:string, tab:string, form:string, label:string}>
     */
    #[Computed]
    public function quickActions(): array
    {
        $actions = [];

        foreach (app(MyHrAccess::class)->allowedRequestForms(Auth::user()) as $form) {
            $actions[] = [
                'key' => $form,
                'tab' => 'requests',
                'form' => $form,
                'label' => __('personnel::my_hr.requests.actions.create_'.$form),
            ];
        }

        $actions[] = [
            'key' => 'payslips',
            'tab' => 'payslips',
            'form' => '',
            'label' => __('personnel::my_hr.tabs.payslips'),
        ];

        return $actions;
    }

    /** Hand navigation back to the dashboard shell, which owns the active tab. */
    public function goto(string $tab, string $form = ''): void
    {
        $this->dispatch('my-hr:goto', tab: $tab, form: $form)->to(MyHrDashboard::class);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.summary');
    }
}
