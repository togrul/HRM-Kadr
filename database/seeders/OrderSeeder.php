<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderCategory;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OrderCategory::upsert([
            [
                'id' => 10,
                'name_az' => 'Əmək müqaviləsi əmrləri',
                'name_en' => 'Employment contract orders',
                'name_ru' => 'Приказы о трудовом договоре',
            ],
        ], ['id'], ['name_az', 'name_en', 'name_ru']);

        Order::upsert([
            [
                'id' => 1010,
                'order_category_id' => 10,
                'name' => 'İşə qəbuletmə',
                'content' => '<div>No</div>',
                'order_model' => '\App\Models\Personnel',
            ],
            [
                'id' => 1030,
                'order_category_id' => 10,
                'name' => 'İşdən çıxarma',
                'content' => '<div>No</div>',
                'order_model' => '\App\Models\Personnel',
            ],
        ], ['id'], ['order_category_id', 'name', 'content', 'order_model']);
    }
}
