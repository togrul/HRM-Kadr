<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_lifecycle_plan_templates') || ! Schema::hasTable('employee_lifecycle_task_templates')) {
            return;
        }

        foreach ($this->templates() as $template) {
            DB::table('employee_lifecycle_plan_templates')->updateOrInsert(
                [
                    'name' => $template['name'],
                    'type' => $template['type'],
                ],
                [
                    'description' => $template['description'],
                    'default_duration_days' => $template['default_duration_days'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $templateId = (int) DB::table('employee_lifecycle_plan_templates')
                ->where('name', $template['name'])
                ->where('type', $template['type'])
                ->value('id');

            foreach ($template['tasks'] as $index => $task) {
                DB::table('employee_lifecycle_task_templates')->updateOrInsert(
                    [
                        'plan_template_id' => $templateId,
                        'title' => $task['title'],
                    ],
                    [
                        'owner_type' => $task['owner_type'],
                        'due_offset_days' => $task['due_offset_days'],
                        'is_required' => true,
                        'sort_order' => ($index + 1) * 10,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employee_lifecycle_plan_templates') || ! Schema::hasTable('employee_lifecycle_task_templates')) {
            return;
        }

        $names = collect($this->templates())->pluck('name')->all();
        $ids = DB::table('employee_lifecycle_plan_templates')->whereIn('name', $names)->pluck('id');

        DB::table('employee_lifecycle_task_templates')->whereIn('plan_template_id', $ids)->delete();
        DB::table('employee_lifecycle_plan_templates')->whereIn('id', $ids)->delete();
    }

    private function templates(): array
    {
        return [
            [
                'name' => 'Yeni əməkdaş onboarding planı',
                'type' => 'onboarding',
                'description' => 'Yeni işə qəbul olunan əməkdaş üçün sənəd, giriş, tanışlıq və ilk hədəf addımlarını standartlaşdırır.',
                'default_duration_days' => 14,
                'tasks' => [
                    ['title' => 'Əməkdaş sənədlərini və şəxsi məlumatları tamamla', 'owner_type' => 'hr', 'due_offset_days' => 1],
                    ['title' => 'İT hesablarını, girişləri və avadanlığı hazırla', 'owner_type' => 'it', 'due_offset_days' => 2],
                    ['title' => 'Rəhbər və komanda ilə tanışlıq görüşünü keçir', 'owner_type' => 'manager', 'due_offset_days' => 3],
                    ['title' => 'İlk 30 gün üçün gözləntiləri və KPI-ları qeyd et', 'owner_type' => 'manager', 'due_offset_days' => 5],
                    ['title' => 'Onboarding tamamlanma yoxlamasını bağla', 'owner_type' => 'hr', 'due_offset_days' => 14],
                ],
            ],
            [
                'name' => 'Sınaq müddəti baxış planı',
                'type' => 'probation',
                'description' => '30/60/90 gün baxışlarını, rəhbər rəylərini və HR qərarını vahid sınaq müddəti axınına salır.',
                'default_duration_days' => 90,
                'tasks' => [
                    ['title' => '30 günlük ilkin adaptasiya baxışını apar', 'owner_type' => 'manager', 'due_offset_days' => 30],
                    ['title' => '60 günlük performans və davranış rəyini topla', 'owner_type' => 'manager', 'due_offset_days' => 60],
                    ['title' => '90 günlük yekun sınaq müddəti qərarını hazırla', 'owner_type' => 'hr', 'due_offset_days' => 88],
                    ['title' => 'Qərarı sistemdə bağla və növbəti addımı qeyd et', 'owner_type' => 'hr', 'due_offset_days' => 90],
                ],
            ],
            [
                'name' => 'Daxili yerdəyişmə planı',
                'type' => 'movement',
                'description' => 'Transfer, vəzifə dəyişikliyi və yüksəliş üçün order, təhvil-təslim və giriş yenilənməsini izləyir.',
                'default_duration_days' => 7,
                'tasks' => [
                    ['title' => 'Daxili yerdəyişmə orderini və qüvvəyə minmə tarixini təsdiqlə', 'owner_type' => 'hr', 'due_offset_days' => 1],
                    ['title' => 'Köhnə rol üzrə təhvil-təslim planını bağla', 'owner_type' => 'manager', 'due_offset_days' => 3],
                    ['title' => 'Yeni struktur və vəzifə girişlərini yenilə', 'owner_type' => 'it', 'due_offset_days' => 4],
                    ['title' => 'Yeni rəhbərlə başlanğıc hədəflərini razılaşdır', 'owner_type' => 'manager', 'due_offset_days' => 7],
                ],
            ],
            [
                'name' => 'İşdən ayrılma planı',
                'type' => 'offboarding',
                'description' => 'İşdən ayrılma sənədləri, aktivlərin təhvili, girişlərin bağlanması və exit müsahibəsini idarə edir.',
                'default_duration_days' => 10,
                'tasks' => [
                    ['title' => 'Son iş günü və ayrılma səbəbini təsdiqlə', 'owner_type' => 'hr', 'due_offset_days' => 1],
                    ['title' => 'Aktivlərin və sənədlərin təhvil-təslimini tamamla', 'owner_type' => 'manager', 'due_offset_days' => 5],
                    ['title' => 'Sistem girişlərini və təhlükəsizlik icazələrini bağla', 'owner_type' => 'it', 'due_offset_days' => 7],
                    ['title' => 'Exit müsahibəsini keçir və nəticələri qeyd et', 'owner_type' => 'hr', 'due_offset_days' => 10],
                ],
            ],
        ];
    }
};
