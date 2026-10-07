<?php

namespace Tests\Feature\Orders;

use App\Models\OrderWordTemplate;
use App\Models\Setting;
use App\Modules\Orders\Infrastructure\Document\OrganizationName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class OrganizationNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_templates_carry_a_header_placeholder_not_another_companys_name(): void
    {
        Storage::fake('local');

        $this->artisan('orders:seed-word-templates', ['--only' => 'emek_mezuniyyeti'])->assertSuccessful();

        $template = OrderWordTemplate::query()->where('code', 'emek_mezuniyyeti')->sole();
        $header = collect($template->variables)->firstWhere('label', 'Təşkilatın adı');

        $this->assertSame('system.organization_name', $header['auto_key'] ?? null);

        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($template->docx_path));
        $this->assertStringNotContainsString('DİNÇER', (string) $zip->getFromName('word/document.xml'));
        $zip->close();
    }

    public function test_the_name_comes_from_settings_then_the_root_structure(): void
    {
        DB::table('structures')->insert(['id' => 1, 'name' => 'TeConctrol LLC', 'shortname' => 'TC', 'parent_id' => null, 'code' => 1, 'level' => 0]);
        Setting::query()->where('name', OrganizationName::SETTING)->delete();

        $this->assertSame('TeConctrol LLC', app(OrganizationName::class)->current());

        Setting::query()->create(['name' => OrganizationName::SETTING, 'value' => '“TECONTROL” MMC', 'type' => 'string']);

        $this->assertSame('“TECONTROL” MMC', app(OrganizationName::class)->current());
    }
}
