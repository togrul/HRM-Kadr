<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TruncateTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } catch (Throwable) {
            // Some managed DB environments may reject session-level FK toggles.
        }

        $truncateTables = [
            'activity_log',
            'structures',
            'weapons',
            'notifications',
            'order_log_component_attributes',
            'order_log_components',
            'order_log_personnels',
            'order_logs',
            'orders',
            'personnel_awards',
            'personnel_business_trips',
            'personnel_cards',
            'personnel_contracts',
            'personnel_criminals',
            'personnel_disposals',
            'personnel_documents',
            'personnel_education',
            'personnel_education_requests',
            'personnel_elected_electorals',
            'personnel_extra_education',
            'personnel_foreign_languages',
            'personnel_identity_documents',
            'personnel_injuries',
            'personnel_kinships',
            'personnel_labor_activities',
            'personnel_master_degrees',
            'personnel_military_services',
            'personnel_participation_events',
            'personnel_passports',
            'personnel_pension_cards',
            'personnel_punishments',
            'personnel_ranks',
            'personnel_scientific_degree_and_names',
            'personnel_taken_captives',
            'personnel_vacations',
            'personnel_weapons',
            'personnels',
            'settings',
            'staff_schedules',
            'vacations',
        ];

        $deleteTables = [
            'kohne',
            'kohne2',
            'kohne3',
            'namized',
        ];

        foreach ($truncateTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            try {
                DB::table($table)->truncate();
            } catch (Throwable) {
                DB::table($table)->delete();
            }
        }

        // foreach ($deleteTables as $table) {
        //     DB::table($table)->delete();
        // }

        // Re-enable foreign key checks
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (Throwable) {
            // Keep seeder non-blocking when FK toggle is unavailable.
        }
    }
}
