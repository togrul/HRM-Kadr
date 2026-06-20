<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OrdersCrossModuleReadIsolationTest extends TestCase
{
    public function test_orders_livewire_layer_has_no_direct_lookup_model_queries(): void
    {
        $targets = [
            app_path('Modules/Orders/Livewire/AllOrders.php'),
        ];

        $forbiddenTokens = [
            'use App\\Models\\Candidate;',
            'use App\\Models\\Personnel;',
            'use App\\Models\\Structure;',
            'use App\\Models\\OrderType;',
            'use App\\Models\\OrderStatus;',
            'use App\\Models\\Rank;',
            'use App\\Models\\Position;',
            'Candidate::query(',
            'Candidate::find(',
            'Candidate::where(',
            'Personnel::query(',
            'Personnel::find(',
            'Personnel::where(',
            'Structure::query(',
            'Structure::find(',
            'Structure::where(',
            'Rank::query(',
            'Position::query(',
            'Rank::find(',
            'Position::find(',
            'OrderType::query(',
            'OrderType::with(',
            'OrderType::where(',
            'OrderType::find(',
            'OrderStatus::query(',
            'OrderStatus::where(',
            'OrderStatus::find(',
        ];

        $violations = [];

        foreach ($targets as $target) {
            if (! File::exists($target)) {
                continue;
            }

            $content = File::get($target);
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $target);

            foreach ($forbiddenTokens as $token) {
                if (str_contains($content, $token)) {
                    $violations[] = $relative.': contains forbidden token '.$token;
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
