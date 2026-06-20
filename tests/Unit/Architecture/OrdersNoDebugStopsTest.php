<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OrdersNoDebugStopsTest extends TestCase
{
    public function test_orders_domain_has_no_dd_or_die_calls(): void
    {
        $paths = [
            app_path('Models'),
            app_path('Services/Orders'),
            app_path('Modules/Orders'),
        ];

        $violations = [];

        foreach ($paths as $path) {
            if (! File::exists($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = File::get($file->getRealPath());
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());

                if (preg_match('/\bdd\s*\(/', $content)) {
                    $violations[] = $relative.': dd(...) found';
                }

                if (preg_match('/\bdie\s*\(/', $content)) {
                    $violations[] = $relative.': die(...) found';
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}

