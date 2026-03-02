<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OrdersDomainApplicationBoundaryTest extends TestCase
{
    public function test_domain_and_application_layers_do_not_use_external_models_directly(): void
    {
        $scanRoots = [
            app_path('Modules/Orders/Domain'),
            app_path('Modules/Orders/Application'),
        ];

        $forbiddenTokens = [
            'use App\\Models\\Personnel;',
            'use App\\Models\\Candidate;',
            'use App\\Models\\Structure;',
            'use App\\Models\\Rank;',
            'use App\\Models\\Position;',
            'use App\\Models\\OrderStatus;',
            'Personnel::query(',
            'Personnel::find(',
            'Personnel::where(',
            'Candidate::query(',
            'Candidate::find(',
            'Candidate::where(',
            'Structure::query(',
            'Structure::find(',
            'Structure::where(',
            'Rank::query(',
            'Rank::find(',
            'Rank::where(',
            'Position::query(',
            'Position::find(',
            'Position::where(',
            'OrderStatus::query(',
            'OrderStatus::find(',
            'OrderStatus::where(',
        ];

        $violations = [];

        foreach ($scanRoots as $root) {
            if (! File::isDirectory($root)) {
                continue;
            }

            foreach (File::allFiles($root) as $file) {
                $content = File::get($file->getPathname());
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());

                foreach ($forbiddenTokens as $token) {
                    if (str_contains($content, $token)) {
                        $violations[] = $relative.': contains forbidden token '.$token;
                    }
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}

