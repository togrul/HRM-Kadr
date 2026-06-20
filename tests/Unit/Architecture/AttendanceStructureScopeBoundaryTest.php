<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AttendanceStructureScopeBoundaryTest extends TestCase
{
    public function test_attendance_livewire_components_do_not_query_structure_model_directly(): void
    {
        $files = [
            app_path('Modules/Attendance/Livewire/DailyMonitor.php'),
            app_path('Modules/Attendance/Livewire/PuantajGrid.php'),
            app_path('Modules/Attendance/Livewire/ManualEntries.php'),
            app_path('Modules/Attendance/Livewire/ExceptionsInbox.php'),
            app_path('Modules/Attendance/Livewire/OvertimeBoard.php'),
            app_path('Modules/Attendance/Livewire/ShiftManagement.php'),
        ];

        $forbiddenTokens = [
            'use App\\Models\\Structure',
            'Structure::query(',
        ];

        $violations = [];

        foreach ($files as $file) {
            if (! File::exists($file)) {
                continue;
            }

            $content = File::get($file);
            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);

            foreach ($forbiddenTokens as $token) {
                if (str_contains($content, $token)) {
                    $violations[] = $relative . ': contains forbidden token ' . $token;
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
