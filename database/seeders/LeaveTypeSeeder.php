<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Xəstəlik',
                'max_days' => 3,
                'requires_document' => true,
            ],
            [
                'name' => 'İllik',
                'max_days' => 30,
                'requires_document' => false,
            ],
            [
                'name' => 'Xüsusi səbəb',
                'max_days' => 7,
                'requires_document' => false,
            ],
        ];

        LeaveType::upsert($types, ['name'], ['max_days', 'requires_document']);
    }
}
