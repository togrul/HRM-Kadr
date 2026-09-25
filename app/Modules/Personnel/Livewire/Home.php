<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\PersonnelVacation;
use App\Modules\Attendance\Contracts\ManualEntryApprover;
use App\Modules\Personnel\Application\Services\HomeOverviewService;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The attention tiles and the today rail render with the page; the attendance chart,
 * activity feed and structure coverage are lazy islands, so each block's read only
 * runs when that island is requested.
 *
 * @property-read list<array<string,mixed>> $attention
 */
class Home extends Component
{
    /** Tiles whose rows can be opened in place; the approvable ones also decide in place. */
    public const EXPANDABLE_QUEUES = ['attendance_pending', 'unsigned_orders', 'vacation_requests'];

    /** The attention tile whose rows are open, if any. */
    public ?string $queue = null;

    public function toggleQueue(string $key): void
    {
        if (! in_array($key, self::EXPANDABLE_QUEUES, true)) {
            return;
        }

        $this->queue = $this->queue === $key ? null : $key;
        unset($this->queueItems);
    }

    /**
     * @return list<array{id:int,title:string,meta:string,url?:string}>
     */
    #[Computed]
    public function queueItems(): array
    {
        return $this->queue === null ? [] : $this->overview()->queueItems($this->queue, auth()->user());
    }

    /** Whether the open tile's rows carry approve / reject buttons for this viewer. */
    #[Computed]
    public function queueDecidable(): bool
    {
        return match ($this->queue) {
            'attendance_pending' => app(ManualEntryApprover::class)->canApprove(auth()->user()),
            'vacation_requests' => true, // the rows are already limited to what the viewer may review
            default => false,
        };
    }

    public function decide(int $id, bool $approve): void
    {
        $user = auth()->user();

        try {
            match ($this->queue) {
                'attendance_pending' => $approve
                    ? app(ManualEntryApprover::class)->approve($id, $user)
                    : app(ManualEntryApprover::class)->reject($id, $user),
                'vacation_requests' => $this->decideVacation($id, $approve),
                default => abort(404),
            };
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: (string) collect($exception->errors())->flatten()->first());

            return;
        }

        unset($this->queueItems, $this->attention, $this->today);

        $this->dispatch('notify', type: 'success', message: __($approve ? 'personnel::home.queue.approved' : 'personnel::home.queue.rejected'));
    }

    private function decideVacation(int $id, bool $approve): void
    {
        $service = app(MyHrRequestReviewService::class);
        $vacation = PersonnelVacation::query()->findOrFail($id);

        abort_unless($service->canReviewVacation($vacation, auth()->user()), 403);

        $approve
            ? $service->approveVacation($vacation, auth()->user())
            : $service->rejectVacation($vacation, auth()->user());
    }

    /**
     * @return list<array<string,mixed>>
     */
    #[Computed]
    public function attention(): array
    {
        return $this->overview()->attention(auth()->user());
    }

    /**
     * @return list<array{key:string,count:int,accent:string,note:string|null,route:string|null}>
     */
    #[Computed]
    public function today(): array
    {
        return $this->overview()->today(auth()->user(), $this->attention);
    }

    /**
     * @return list<array<string,mixed>>
     */
    #[Computed]
    public function attendanceWeek(): array
    {
        return $this->overview()->attendanceWeek(auth()->user());
    }

    /**
     * @return list<array{id:int,event:string,subject:string,subject_id:int|null,actor:string,at:\Carbon\Carbon|null}>
     */
    #[Computed]
    public function activity(): array
    {
        return $this->overview()->activity(auth()->user());
    }

    /**
     * @return list<array{id:int,name:string,total:int,filled:int,vacant:int,pct:int}>
     */
    #[Computed]
    public function structureFill(): array
    {
        return $this->overview()->structureFill(auth()->user());
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.home');
    }

    private function overview(): HomeOverviewService
    {
        return app(HomeOverviewService::class);
    }
}
