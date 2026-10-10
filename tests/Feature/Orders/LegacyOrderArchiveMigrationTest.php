<?php

use App\Models\Order;
use App\Models\OrderCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dağıdıcı legacy Orders miqrasiyaları: drop-dan əvvəl hər sətir `legacy_order_archive`-ə
 * düşür, down() isə strukturu və məlumatı arxivdən eyni ilə bərpa edir.
 */
function legacyOrderMigration(string $file): object
{
    return require base_path('app/Modules/Orders/Database/Migrations/'.$file);
}

it('archives orders.content before dropping it and restores it on rollback', function (): void {
    $migration = legacyOrderMigration('2026_10_10_200000_archive_and_drop_orders_content.php');

    expect(Schema::hasColumn('orders', 'content'))->toBeFalse()
        ->and(Schema::hasTable('legacy_order_archive'))->toBeTrue();

    $migration->down();
    expect(Schema::hasColumn('orders', 'content'))->toBeTrue();

    $category = OrderCategory::query()->create(['id' => 1, 'name_az' => 'Kadr', 'name_en' => 'HR', 'name_ru' => 'HR']);
    DB::table('orders')->insert([
        ['id' => 1010, 'order_category_id' => $category->id, 'name' => 'İşə qəbul', 'content' => 'templates/ise-qebul.docx', 'order_model' => '', 'deleted_at' => null],
        ['id' => 1030, 'order_category_id' => $category->id, 'name' => 'Xitam', 'content' => '<div>${content}</div>', 'order_model' => '', 'deleted_at' => now()],
    ]);

    $migration->up();

    expect(Schema::hasColumn('orders', 'content'))->toBeFalse()
        ->and(Order::withTrashed()->count())->toBe(2);

    $archived = DB::table('legacy_order_archive')->where('source', 'orders.content')->orderBy('source_key')->get();
    expect($archived)->toHaveCount(2)
        ->and(json_decode($archived[0]->payload, true)['content'])->toBe('templates/ise-qebul.docx')
        ->and(json_decode($archived[1]->payload, true)['content'])->toBe('<div>${content}</div>');

    // Təkrar run (yarımçıq əvvəlki cəhd) dublikat yaratmır: sütun yoxdursa no-op.
    $migration->up();
    expect(DB::table('legacy_order_archive')->where('source', 'orders.content')->count())->toBe(2);

    $migration->down();

    expect(DB::table('orders')->orderBy('id')->pluck('content', 'id')->all())->toBe([
        1010 => 'templates/ise-qebul.docx',
        1030 => '<div>${content}</div>',
    ])->and(DB::table('legacy_order_archive')->where('source', 'orders.content')->count())->toBe(0);

    $migration->up();
    expect(Schema::hasColumn('orders', 'content'))->toBeFalse();
});

it('archives the legacy component tables before dropping them and restores them on rollback', function (): void {
    $migration = legacyOrderMigration('2026_06_20_120000_drop_legacy_component_tables.php');

    expect(Schema::hasTable('components'))->toBeFalse()
        ->and(Schema::hasTable('order_log_components'))->toBeFalse()
        ->and(Schema::hasTable('order_log_component_attributes'))->toBeFalse();

    $migration->down();

    DB::table('components')->insert([
        'id' => 7, 'name' => 'Məzuniyyət', 'content' => '$fullname $days gün', 'title' => null,
        'dynamic_fields' => json_encode(['$fullname', '$days']), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('order_log_components')->insert([
        'id' => 3, 'order_no' => 'OLD-1', 'component_id' => 7, 'row_number' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('order_log_component_attributes')->insert([
        'id' => 4, 'order_no' => 'OLD-1', 'component_id' => 7, 'row_number' => 0, 'attribute_id' => null,
        'attributes' => json_encode(['$fullname' => ['value' => 'Əliyev Əli'], '$days' => ['value' => 21]], JSON_UNESCAPED_UNICODE),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    Schema::withoutForeignKeyConstraints(fn () => DB::table('order_log_personnels')->insert([
        ['id' => 11, 'order_no' => 'OLD-1', 'tabel_no' => 'T-1', 'component_id' => 7],
        ['id' => 12, 'order_no' => 'OLD-1', 'tabel_no' => 'T-2', 'component_id' => null],
    ]));

    $migration->up();

    expect(Schema::hasTable('components'))->toBeFalse()
        ->and(Schema::hasTable('order_log_components'))->toBeFalse()
        ->and(Schema::hasTable('order_log_component_attributes'))->toBeFalse()
        ->and(Schema::hasColumn('order_log_personnels', 'component_id'))->toBeFalse()
        ->and(DB::table('legacy_order_archive')->pluck('source')->sort()->values()->all())
        ->toBe(['components', 'order_log_component_attributes', 'order_log_components', 'order_log_personnels.component_id']);

    $migration->down();

    expect(DB::table('components')->value('dynamic_fields'))->toBe(json_encode(['$fullname', '$days']))
        ->and(DB::table('order_log_components')->where('id', 3)->value('order_no'))->toBe('OLD-1')
        ->and(json_decode(DB::table('order_log_component_attributes')->where('id', 4)->value('attributes'), true))
        ->toBe(['$fullname' => ['value' => 'Əliyev Əli'], '$days' => ['value' => 21]])
        ->and(DB::table('order_log_personnels')->orderBy('id')->pluck('component_id', 'id')->all())->toBe([11 => 7, 12 => null])
        ->and(DB::table('legacy_order_archive')->count())->toBe(0);

    $migration->up();
    expect(Schema::hasTable('components'))->toBeFalse();
});
