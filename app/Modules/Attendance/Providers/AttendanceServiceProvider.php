<?php

namespace App\Modules\Attendance\Providers;

use App\Modules\Attendance\Console\Commands\AttendanceProcessPunchesCommand;
use App\Modules\Attendance\Console\Commands\AttendanceMonthlySnapshotCommand;
use App\Modules\Attendance\Console\Commands\AttendanceQueryBudgetCommand;
use App\Modules\Attendance\Console\Commands\AttendanceRecalculateLedgersCommand;
use App\Modules\Attendance\Console\Commands\AttendanceSeedWeekendCalendarsCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class AttendanceServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AttendanceProcessPunchesCommand::class,
                AttendanceMonthlySnapshotCommand::class,
                AttendanceRecalculateLedgersCommand::class,
                AttendanceQueryBudgetCommand::class,
                AttendanceSeedWeekendCalendarsCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('attendance')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'attendance');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'attendance');
    }

    protected function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\Attendance\Livewire\Dashboard::class,
            'manual-entries' => \App\Modules\Attendance\Livewire\ManualEntries::class,
            'puantaj-grid' => \App\Modules\Attendance\Livewire\PuantajGrid::class,
            'exceptions-inbox' => \App\Modules\Attendance\Livewire\ExceptionsInbox::class,
            'daily-monitor' => \App\Modules\Attendance\Livewire\DailyMonitor::class,
            'overtime-board' => \App\Modules\Attendance\Livewire\OvertimeBoard::class,
            'month-close' => \App\Modules\Attendance\Livewire\MonthClose::class,
            'settings' => \App\Modules\Attendance\Livewire\Settings::class,
            'shift-management' => \App\Modules\Attendance\Livewire\ShiftManagement::class,
            'calendar-regimes' => \App\Modules\Attendance\Livewire\CalendarRegimes::class,
        ];
    }
}
