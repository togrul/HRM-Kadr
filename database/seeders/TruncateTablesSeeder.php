<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TruncateTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables
        DB::table('activity_log')->truncate();
        DB::table('structures')->truncate();
        DB::table('weapons')->truncate();
        DB::table('notifications')->truncate();
        DB::table('order_log_component_attributes')->truncate();
        DB::table('order_log_components')->truncate();
        DB::table('order_log_personnels')->truncate();
        DB::table('order_logs')->truncate();
        DB::table('orders')->truncate();
        DB::table('personnel_awards')->truncate();
        DB::table('personnel_business_trips')->truncate();
        DB::table('personnel_cards')->truncate();
        DB::table('personnel_contracts')->truncate();
        DB::table('personnel_criminals')->truncate();
        DB::table('personnel_disposals')->truncate();
        DB::table('personnel_documents')->truncate();
        DB::table('personnel_education')->truncate();
        DB::table('personnel_education_requests')->truncate();
        DB::table('personnel_elected_electorals')->truncate();
        DB::table('personnel_extra_education')->truncate();
        DB::table('personnel_foreign_languages')->truncate();
        DB::table('personnel_identity_documents')->truncate();
        DB::table('personnel_injuries')->truncate();
        DB::table('personnel_kinships')->truncate();
        DB::table('personnel_labor_activities')->truncate();
        DB::table('personnel_master_degrees')->truncate();
        DB::table('personnel_military_services')->truncate();
        DB::table('personnel_participation_events')->truncate();
        DB::table('personnel_passports')->truncate();
        DB::table('personnel_pension_cards')->truncate();
        DB::table('personnel_punishments')->truncate();
        DB::table('personnel_ranks')->truncate();
        DB::table('personnel_scientific_degree_and_names')->truncate();
        DB::table('personnel_taken_captives')->truncate();
        DB::table('personnel_vacations')->truncate();
        DB::table('personnel_weapons')->truncate();
        DB::table('personnels')->truncate();
        DB::table('settings')->truncate();
        DB::table('staff_schedules')->truncate();
        DB::table('vacations')->truncate();
        DB::table('kohne')->delete();
        DB::table('kohne2')->delete();
        DB::table('kohne3')->delete();
        DB::table('namized')->delete();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
