<?php

namespace Tests\Feature\Orders;

use App\Models\OrderWordTemplate;
use App\Modules\Orders\Application\Document\DocxPlaceholderParser;
use App\Modules\Orders\Application\Document\LegacyOrderTemplateNeutralizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Standart əmr şablonları heç bir konkret şəxsin və ya başqa şirkətin adını daşımır; artıq
 * quraşdırılmış köhnə şablonlar yalnız dəyişdirilməyibsə yerində düzəldilir.
 */
class OrderTemplateNeutralizationTest extends TestCase
{
    use RefreshDatabase;

    private const FORBIDDEN = ['Səbuhi', 'Bağırov', 'Dinçer', 'DİNÇER', 'Carçıoğlu', 'CARÇIOĞLU', 'Sübhan', 'İsmayılov'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->artisan('orders:seed-word-templates')->assertSuccessful();
    }

    public function test_no_seeded_template_names_a_person_or_another_company(): void
    {
        foreach (OrderWordTemplate::query()->get() as $template) {
            $text = $this->text($template);

            foreach (self::FORBIDDEN as $name) {
                $this->assertStringNotContainsString($name, $text, "{$template->code} contains {$name}");
            }
        }
    }

    public function test_labour_code_references_follow_the_official_articles(): void
    {
        $expected = [
            'emek_mezuniyyeti' => '114-cü və 131-ci maddələrini, 138-ci maddəsinin 1-ci hissəsini',
            'tehsil_mezuniyyeti' => '123-cü və 124-cü maddələrini',
            'odenissiz_mezuniyyet' => '129-cu maddəsinin 1-ci hissəsini',
            'ataliq_mezuniyyeti' => '125-ci maddəsinin 4-cü hissəsini',
            'analiq_mezuniyyeti' => '125-ci maddəsini',
            'ezamiyyet' => '179-cu maddəsinin 2-ci hissəsinin “x” bəndini və 181-ci maddəsini',
            'herbi_toplanti' => '179-cu maddəsinin 2-ci hissəsinin “g” bəndinə',
            'evezetme' => '61-ci və 162-ci maddələrini',
            'intizam_tenbehi' => '186-cı və 187-ci maddələrini',
            'ise_qebul' => '81-ci maddəsinin 1-ci hissəsini',
            'basqa_ise_kecirilme' => '59-cu maddəsinə',
        ];

        foreach ($expected as $code => $reference) {
            $template = OrderWordTemplate::query()->where('code', $code)->sole();
            $this->assertStringContainsString($reference, $this->text($template), $code);
        }
    }

    public function test_unedited_legacy_templates_are_rewritten_in_place_and_edited_ones_are_kept(): void
    {
        $parser = app(DocxPlaceholderParser::class);

        // ise_qebul: the a99d5451 generation — header placeholder already, named accounting head.
        $hire = $this->template('ise_qebul');
        $hireVariables = $hire->variables;
        $this->rewrite($hire, ['Mühasibatlıq bu əmrdən' => 'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov bu əmrdən']);

        // emek_mezuniyyeti: the oldest generation — company name baked into the header,
        // the old article reference, and no organisation variable in the mapping.
        $leave = $this->template('emek_mezuniyyeti');
        $headerToken = collect($leave->variables)->firstWhere('label', 'Təşkilatın adı')['token'];
        $this->rewrite($leave, [
            '${'.$headerToken.'}' => '“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ',
            '114-cü və 131-ci maddələrini, 138-ci maddəsinin 1-ci hissəsini' => '138-ci maddəsinin 2-ci hissəsini',
            'Mühasibatlıq bu əmrdən' => 'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Bağırov Səbuhi bu əmrdən',
        ]);
        $leave->forceFill(['variables' => collect($leave->variables)->reject(fn ($v) => $v['label'] === 'Təşkilatın adı')->values()->all()])->save();

        // xitam: HR reworded it — must be left alone, legacy name and all.
        $termination = $this->template('xitam');
        $this->rewrite($termination, ['Mühasibatlıq bu əmrdən' => 'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi Səbuhi Bağırov (HR redaktəsi) bu əmrdən']);
        $editedBytes = Storage::disk('local')->get($termination->docx_path);

        $dryRun = app(LegacyOrderTemplateNeutralizer::class)->run(dryRun: true);
        $this->assertSame(['emek_mezuniyyeti', 'ise_qebul'], $dryRun['updated']);
        $this->assertSame(['xitam'], $dryRun['edited']);
        $this->assertStringContainsString('Bağırov', $this->text($hire->fresh()));

        $this->artisan('orders:neutralize-word-templates')->assertSuccessful();

        // Rewritten in place: same tokens for the hire, names gone.
        $hire->refresh();
        $this->assertSame($hireVariables, $hire->variables);
        $this->assertStringNotContainsString('Bağırov', $this->text($hire));
        $this->assertSame(1, $hire->versions()->count());

        // The old header became a new automatic variable, appended so no token shifts.
        $leave->refresh();
        $header = collect($leave->variables)->firstWhere('label', 'Təşkilatın adı');
        $this->assertSame('system.organization_name', $header['auto_key']);
        $this->assertSame('${'.$header['token'].'}', $parser->paragraphs(Storage::disk('local')->path($leave->docx_path))[0]);
        $this->assertStringContainsString('114-cü və 131-ci maddələrini', $this->text($leave));
        $this->assertStringNotContainsString('DİNÇER', $this->text($leave));

        $this->assertSame($editedBytes, Storage::disk('local')->get($termination->docx_path));

        // Idempotent.
        $again = app(LegacyOrderTemplateNeutralizer::class)->run();
        $this->assertSame([], $again['updated']);
        $this->assertContains('ise_qebul', $again['current']);
        $this->assertContains('emek_mezuniyyeti', $again['current']);
    }

    private function template(string $code): OrderWordTemplate
    {
        return OrderWordTemplate::query()->where('code', $code)->sole();
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewrite(OrderWordTemplate $template, array $map): void
    {
        $path = Storage::disk('local')->path($template->docx_path);
        $tmp = tempnam(sys_get_temp_dir(), 'tpl_').'.docx';
        app(DocxPlaceholderParser::class)->rewriteLiterals($path, $map, $tmp);
        File::copy($tmp, $path);
        @unlink($tmp);
    }

    private function text(OrderWordTemplate $template): string
    {
        return implode("\n", app(DocxPlaceholderParser::class)->paragraphs(Storage::disk('local')->path($template->docx_path)));
    }
}
