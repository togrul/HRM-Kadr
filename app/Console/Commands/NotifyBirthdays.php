<?php

namespace App\Console\Commands;

use App\Models\Personnel;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyBirthdays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:birthdays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $birthdays = Personnel::whereMonth('birthdate', '=', Carbon::now()->format('m'))
            ->whereDay('birthdate', '=', Carbon::now()->format('d'))
            ->whereNull('leave_work_date')
            ->where('is_pending', false)
            ->with(['position:id,name', 'structure:id,parent_id,name'])
            ->get();

        $dispatcher = app(NotificationCampaignDispatcher::class);
        $sent = 0;

        foreach ($birthdays as $birthday) {
            $sent += $dispatcher->dispatchBirthday($birthday);
        }

        $this->info("Sent successfully! Dispatches: {$sent}");
    }
}
