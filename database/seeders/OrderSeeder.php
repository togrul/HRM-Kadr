<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //        OrderCategory::firstOrCreate([
//            'id' => 10,
//            'name_az' => 'Əmək müqaviləsi əmrləri',
//            'name_en' => 'Employment contract orders',
//            'name_ru' => 'Приказы о трудовом договоре',
//        ]);

        Order::firstOrCreate([
            'id' => 1010,
            'order_category_id' => 10,
            'name' => 'İşə qəbuletmə',
            'content' => '<div>No</div>',
            'order_model' => '\App\Models\Personnel'
        ]);

        Order::firstOrCreate([
            'id' => 1030,
            'order_category_id' => 10,
            'name' => 'İşdən çıxarma',
            'content' => '<div>No</div>',
            'order_model' => '\App\Models\Personnel'
        ]);

        OrderStatus::create([
            'id' => 10,
            'locale' => 'az',
            'name' => 'Təsdiq gözləyən'
        ]);

        OrderStatus::create([
            'id' => 20,
            'locale' => 'az',
            'name' => 'Təsdiqlənmiş'
        ]);

        OrderStatus::create([
            'id' => 30,
            'locale' => 'az',
            'name' => 'Ləğv edilmiş'
        ]);
    }
}
