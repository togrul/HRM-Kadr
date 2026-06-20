<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\MyHrNotifications;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_hr_notifications_tab_shows_employee_notifications_and_marks_them_as_read(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));

        $this->makePersonnel($user->email);

        $this->seedUserNotification($user, ['name' => 'İcazə statusu yeniləndi'], null, 10);
        $this->seedUserNotification($user, ['name' => 'Yeni HR elanı'], Carbon::parse('2026-03-20 09:00:00')->toDateTimeString(), 1440);

        $this->actingAs($user)
            ->get(route('my-hr', ['tab' => 'notifications']))
            ->assertOk()
            ->assertSee('Bildirişlər')
            ->assertSee('İcazə statusu yeniləndi')
            ->assertSee('Yeni HR elanı');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_my_hr_notifications_component_can_clear_notifications(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));

        $personnel = $this->makePersonnel($user->email);

        $this->seedUserNotification($user, ['name' => 'N1'], null, 10);
        $this->seedUserNotification($user, ['name' => 'N2'], null, 20);

        $this->actingAs($user);

        Livewire::test(MyHrNotifications::class, ['personnelId' => $personnel->id])
            ->call('clearNotifications')
            ->assertSee('Bildiriş yoxdur');

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }

    private function seedUserNotification(User $user, array $data = [], ?string $readAt = null, ?int $minutesAgo = null): DatabaseNotification
    {
        $createdAt = now();
        if (is_int($minutesAgo) && $minutesAgo > 0) {
            $createdAt = now()->subMinutes($minutesAgo);
        }

        return DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\SystemNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => array_merge([
                'type' => 'Personnel',
                'action' => 'create',
                'added_by' => 'System',
                'name' => 'Test notification',
                'message' => 'has notification',
                'category' => 'New personnel',
            ], $data),
            'read_at' => $readAt ? Carbon::parse($readAt) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function makePersonnel(string $email): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }

        if (! DB::table('country_translations')->where('id', 1)->exists()) {
            DB::table('country_translations')->insert([
                'id' => 1,
                'country_id' => 1,
                'locale' => 'az',
                'title' => 'Azərbaycan',
            ]);
        }

        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bachelor',
            ]);
        }

        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert([
                'id' => 1,
                'name' => 'HQ',
                'shortname' => 'HQ',
                'parent_id' => null,
                'coefficient' => 1.10,
                'code' => 10,
                'level' => 1,
            ]);
        }

        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert([
                'id' => 1,
                'name' => 'Officer',
            ]);
        }

        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert([
                'id' => 1,
                'name_az' => 'Tam iş günü',
                'name_en' => 'Full time',
                'name_ru' => 'Full time',
            ]);
        }
    }
}
