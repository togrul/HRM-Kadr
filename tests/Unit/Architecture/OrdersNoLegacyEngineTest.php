<?php

namespace Tests\Unit\Architecture;

use App\Models\Order;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Orders no-legacy rollout bağlanışı: köhnə component/`${content}` DOCX mühərrikinin
 * runtime izləri (state adları, trait-lər, print payload, cədvəl/sütun adları) tətbiq
 * koduna geri qayıtmamalıdır. Miqrasiyalar istisnadır — tarixi sxemi onlar daşıyır.
 */
class OrdersNoLegacyEngineTest extends TestCase
{
    /** @var list<string> */
    private const FORBIDDEN = [
        'componentForms',
        'selectedComponents',
        'OrderCrud',
        'HandlesComponentRows',
        'HandlesOrderComponentFieldState',
        'ManagesOrderComponents',
        'OrderPrintPayloadFactory',
        'GenerateWordReplaceContent',
        'OrderLegacyComponentSnapshotPersister',
        'order_log_components',
        'order_log_component_attributes',
        'dynamic_fields',
        'componentFieldValue',
        'x-dynamic-input',
    ];

    public function test_retired_order_engine_symbols_do_not_come_back(): void
    {
        $violations = [];

        foreach ([app_path(), resource_path('views'), config_path(), base_path('routes'), database_path('seeders')] as $root) {
            foreach (File::allFiles($root) as $file) {
                $path = $file->getRealPath();

                if ($file->getExtension() !== 'php' || str_contains($path, DIRECTORY_SEPARATOR.'Migrations'.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $content = File::get($path);

                foreach (self::FORBIDDEN as $needle) {
                    if (str_contains($content, $needle)) {
                        $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).': '.$needle;
                    }
                }
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_order_definitions_no_longer_carry_the_legacy_content_column(): void
    {
        $this->assertNotContains('content', (new Order)->getFillable());
    }
}
