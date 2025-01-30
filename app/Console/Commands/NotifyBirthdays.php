<?php

namespace App\Console\Commands;

use App\Models\Personnel;
use App\Models\User;
use App\Notifications\BirthdayNotification;
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
            ->get();

        $adminUsers = User::role('admin')->permission('get-notification')->get();
        foreach ($birthdays as $birthday) {
            foreach ($adminUsers as $admin) {
                $admin->notify(new BirthdayNotification($birthday));
            }
        }
        $this->info('Sent successfully!');
    }
}
