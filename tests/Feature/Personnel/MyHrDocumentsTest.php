<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\PersonnelDocument;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\MyHrDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_hr_documents_tab_shows_visible_documents(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        $user->givePermissionTo(
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('view-own-personnel-documents', 'web'),
        );

        $personnel = $this->makePersonnel($user->email);
        PersonnelDocument::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'file' => 'files/test-manual.pdf',
            'filename' => 'İş qaydaları.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('my-hr', ['tab' => 'documents']))
            ->assertOk()
            ->assertSee('Sənədlərim')
            ->assertSee('İş qaydaları.pdf');
    }

    public function test_employee_can_open_visible_document(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        $user->givePermissionTo(
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('view-own-personnel-documents', 'web'),
        );

        $personnel = $this->makePersonnel($user->email);
        $document = PersonnelDocument::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'file' => 'files/test-manual.pdf',
            'filename' => 'İş qaydaları.pdf',
        ]);

        $this->actingAs($user);

        Livewire::test(MyHrDocuments::class, ['personnelId' => $personnel->id])
            ->call('openDocument', $document->id)
            ->assertRedirect('/storage/files/test-manual.pdf');
    }

    public function test_hidden_or_future_documents_do_not_appear_in_my_hr_documents(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        $user->givePermissionTo(
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('view-own-personnel-documents', 'web'),
        );

        $personnel = $this->makePersonnel($user->email);

        PersonnelDocument::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'file' => 'files/public.pdf',
            'filename' => 'Görünən sənəd.pdf',
        ]);

        PersonnelDocument::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'file' => 'files/hidden.pdf',
            'filename' => 'Gizli sənəd.pdf',
            'employee_visibility' => 'hidden',
        ]);

        PersonnelDocument::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'file' => 'files/future.pdf',
            'filename' => 'Gələcək sənəd.pdf',
            'visible_from' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('my-hr', ['tab' => 'documents']))
            ->assertOk()
            ->assertSee('Görünən sənəd.pdf')
            ->assertDontSee('Gizli sənəd.pdf')
            ->assertDontSee('Gələcək sənəd.pdf');
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
            DB::table('country_translations')->insert(['id' => 1, 'country_id' => 1, 'locale' => 'az', 'title' => 'Azərbaycan']);
        }
        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        }
        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert(['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1]);
        }
        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer']);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
