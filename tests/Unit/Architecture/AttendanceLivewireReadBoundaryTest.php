<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AttendanceLivewireReadBoundaryTest extends TestCase
{
    public function test_read_heavy_attendance_livewire_components_do_not_query_models_directly(): void
    {
        $files = [
            app_path('Modules/Attendance/Livewire/Dashboard.php'),
            app_path('Modules/Attendance/Livewire/DailyMonitor.php'),
            app_path('Modules/Attendance/Livewire/PuantajGrid.php'),
        ];

        $forbiddenTokens = [
            'use App\\Models\\',
            '::query(',
            '::where(',
            '::find(',
            'DB::',
        ];

        $violations = [];

        foreach ($files as $file) {
            if (! File::exists($file)) {
                continue;
            }

            $content = File::get($file);
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);

            foreach ($forbiddenTokens as $token) {
                if (str_contains($content, $token)) {
                    $violations[] = $relative.': contains forbidden token '.$token;
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
