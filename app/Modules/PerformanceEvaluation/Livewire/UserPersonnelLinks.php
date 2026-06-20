<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use Livewire\Component;

class UserPersonnelLinks extends Component
{
    use ConfirmsDestructiveActions;
    use InteractsWithPerformanceEvaluationAccess;
    use WithRuntimeMemo;

    public string $searchLinks = '';

    public string $searchLinkedUser = '';

    public string $searchLinkedPersonnel = '';

    public array $linkForm = [
        'id' => null,
        'user_id' => null,
        'personnel_id' => null,
        'resolution_source' => 'manual',
    ];

    public function mount(): void
    {
        $this->authorizePerformanceEvaluationManage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['searchLinks', 'searchLinkedUser', 'searchLinkedPersonnel'], true)) {
            $this->resetRuntimeMemo();
        }
    }

    public function getBackUrlProperty(): string
    {
        $returnUrl = request()->query('return');

        if (is_string($returnUrl) && str_starts_with($returnUrl, url('/'))) {
            return $returnUrl;
        }

        return route('performance-evaluation');
    }

    public function getLinkStatsProperty(): array
    {
        return $this->rememberRuntime('performanceEvaluation.userPersonnelLinks.stats', function (): array {
            $startOfDay = now()->startOfDay();
            $endOfDay = $startOfDay->copy()->addDay();

            $stats = UserPersonnelLink::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN resolution_source = 'manual' THEN 1 ELSE 0 END) as manual_links")
                ->selectRaw('SUM(CASE WHEN resolved_at >= ? AND resolved_at < ? THEN 1 ELSE 0 END) as resolved_today', [
                    $startOfDay,
                    $endOfDay,
                ])
                ->first();

            return [
                'total' => (int) ($stats?->total ?? 0),
                'manual' => (int) ($stats?->manual_links ?? 0),
                'resolved_today' => (int) ($stats?->resolved_today ?? 0),
            ];
        });
    }

    public function getLinksProperty()
    {
        return $this->rememberRuntime(
            'performanceEvaluation.userPersonnelLinks.list.'.md5($this->searchLinks),
            function () {
                return UserPersonnelLink::query()
                    ->leftJoin('users', 'users.id', '=', 'user_personnel_links.user_id')
                    ->leftJoin('personnels', 'personnels.id', '=', 'user_personnel_links.personnel_id')
                    ->select([
                        'user_personnel_links.*',
                        'users.name as user_name',
                        'users.email as user_email',
                        'personnels.surname as personnel_surname',
                        'personnels.name as personnel_name',
                        'personnels.patronymic as personnel_patronymic',
                        'personnels.tabel_no as personnel_tabel_no',
                    ])
                    ->when($this->searchLinks !== '', function ($query): void {
                        $search = '%'.$this->searchLinks.'%';

                        $query->where(function ($nested) use ($search): void {
                            $nested
                                ->where('users.name', 'like', $search)
                                ->orWhere('users.email', 'like', $search)
                                ->orWhere('personnels.name', 'like', $search)
                                ->orWhere('personnels.surname', 'like', $search)
                                ->orWhere('personnels.tabel_no', 'like', $search);
                        });
                    })
                    ->latest('user_personnel_links.id')
                    ->limit(24)
                    ->get()
                    ->each(function (UserPersonnelLink $link): void {
                        $link->setAttribute('personnel_fullname', trim(implode(' ', array_filter([
                            $link->personnel_surname,
                            $link->personnel_name,
                            $link->personnel_patronymic,
                        ]))));
                    });
            }
        );
    }

    public function userOptions(): array
    {
        $selectedUserId = (int) ($this->linkForm['user_id'] ?? 0);

        return $this->rememberRuntime(
            'performanceEvaluation.userPersonnelLinks.userOptions.'.md5($this->searchLinkedUser.'|'.$selectedUserId),
            function () use ($selectedUserId): array {
                return User::query()
                    ->where(function ($query) use ($selectedUserId): void {
                        $query->whereNull('deleted_at')
                            ->when($this->searchLinkedUser !== '', function ($query): void {
                                $search = '%'.$this->searchLinkedUser.'%';
                                $query->where(function ($nested) use ($search): void {
                                    $nested->where('name', 'like', $search)
                                        ->orWhere('email', 'like', $search);
                                });
                            });

                        if ($selectedUserId > 0) {
                            $query->orWhere('id', $selectedUserId);
                        }
                    })
                    ->orderBy('name')
                    ->limit(20)
                    ->get(['id', 'name', 'email'])
                    ->unique('id')
                    ->map(fn (User $user) => [
                        'id' => $user->id,
                        'label' => trim($user->name.' / '.$user->email),
                    ])
                    ->values()
                    ->all();
            }
        );
    }

    public function personnelOptions(): array
    {
        $selectedPersonnelId = (int) ($this->linkForm['personnel_id'] ?? 0);

        return $this->rememberRuntime(
            'performanceEvaluation.userPersonnelLinks.personnelOptions.'.md5($this->searchLinkedPersonnel.'|'.$selectedPersonnelId),
            function () use ($selectedPersonnelId): array {
                return Personnel::query()
                    ->where(function ($query) use ($selectedPersonnelId): void {
                        $query->active()
                            ->when($this->searchLinkedPersonnel !== '', function ($query): void {
                                $search = '%'.$this->searchLinkedPersonnel.'%';
                                $query->where(function ($nested) use ($search): void {
                                    $nested->where('surname', 'like', $search)
                                        ->orWhere('name', 'like', $search)
                                        ->orWhere('patronymic', 'like', $search)
                                        ->orWhere('tabel_no', 'like', $search)
                                        ->orWhere('email', 'like', $search);
                                });
                            });

                        if ($selectedPersonnelId > 0) {
                            $query->orWhere('id', $selectedPersonnelId);
                        }
                    })
                    ->orderBy('surname')
                    ->limit(20)
                    ->get(['id', 'surname', 'name', 'patronymic', 'tabel_no'])
                    ->unique('id')
                    ->map(fn (Personnel $personnel) => [
                        'id' => $personnel->id,
                        'label' => trim($personnel->fullname.' (#'.$personnel->tabel_no.')'),
                    ])
                    ->values()
                    ->all();
            }
        );
    }

    public function saveLink(): void
    {
        $this->authorizePerformanceEvaluationManage();

        $validated = $this->validate([
            'linkForm.user_id' => 'required|exists:users,id',
            'linkForm.personnel_id' => 'required|exists:personnels,id',
        ], attributes: [
            'linkForm.user_id' => __('performance_evaluation::dashboard.fields.user'),
            'linkForm.personnel_id' => __('performance_evaluation::dashboard.fields.personnel'),
        ]);

        UserPersonnelLink::query()->updateOrCreate(
            ['user_id' => (int) data_get($validated, 'linkForm.user_id')],
            [
                'personnel_id' => (int) data_get($validated, 'linkForm.personnel_id'),
                'resolution_source' => 'manual',
                'resolved_at' => now(),
            ]
        );

        $this->linkForm = [
            'id' => null,
            'user_id' => null,
            'personnel_id' => null,
            'resolution_source' => 'manual',
        ];
        $this->resetValidation();
        $this->resetRuntimeMemo();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.user_personnel_link_saved'));
    }

    public function editLink(int $linkId): void
    {
        $this->authorizePerformanceEvaluationManage();

        $link = UserPersonnelLink::query()->findOrFail($linkId);

        $this->linkForm = [
            'id' => $link->id,
            'user_id' => $link->user_id,
            'personnel_id' => $link->personnel_id,
            'resolution_source' => (string) $link->resolution_source,
        ];
    }

    public function requestDeleteLink(int $linkId): void
    {
        $this->authorizePerformanceEvaluationManage();

        $this->confirmDeletion(
            'deleteLink',
            ['linkId' => $linkId],
            __('performance_evaluation::dashboard.modals.delete_user_personnel_link_title'),
            __('performance_evaluation::dashboard.modals.delete_user_personnel_link_message'),
            __('performance_evaluation::dashboard.modals.delete_user_personnel_link_description'),
            __('ui::common.actions.delete')
        );
    }

    public function deleteLink(int $linkId): void
    {
        $this->authorizePerformanceEvaluationManage();

        UserPersonnelLink::query()->whereKey($linkId)->delete();
        $this->resetRuntimeMemo();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.user_personnel_link_deleted'));
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.user-personnel-links');
    }
}
