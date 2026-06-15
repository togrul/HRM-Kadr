<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Services\Orders\Document\OrderDocumentDocxRenderer;
use App\Services\Orders\Document\OrderDocumentHtmlRenderer;
use App\Services\Orders\Document\OrderTemplateCompiler;
use App\Services\Orders\Document\OrderTemplateDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

/**
 * End-to-end pipeline (phases 1-3): a template + a real personnel + field values →
 * compiled OrderDocument → filled preview HTML and a valid .docx, with the
 * personnel's name and structure rendered in the correct grammatical case.
 */
class OrderTemplateCompilerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_compiles_a_filled_order_with_declension_into_html_and_docx(): void
    {
        $structure = Structure::query()->create([
            'name' => 'Keşlə Qeyri-Qida Satış mərkəzi',
            'shortname' => 'Keşlə QQS',
        ]);
        $position = Position::query()->create(['name' => 'satınalma operatoru']);
        $personnel = $this->makePersonnel($structure->id, $position->id);

        $template = new OrderTemplateDefinition(
            organizationName: '“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ',
            organizationCity: 'Bakı şəhəri',
            numberSuffix: '-M',
            subject: 'Əmək məzuniyyətinin verilməsi haqqında',
            preamble: 'Azərbaycan Respublikası Əmək Məcəlləsinin 138-ci maddəsinin 2-ci hissəsini rəhbər tutaraq',
            clauses: [
                '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_dative }} {{ field.days }} təqvim günü müddətində əmək məzuniyyəti verilsin.',
                'Məzuniyyətin başlanma tarixi {{ field.start_date }}, bitmə tarixi {{ field.end_date }} müəyyən edilsin.',
            ],
            basis: '{{ employee.initials_genitive }} ərizəsi.',
            signatoryTitleLines: ['Baş direktorun İnsan resursları,', 'təşkilati idarəetmə və', 'kommunikasiyalar üzrə müavini'],
            signatoryName: 'Sübhan İsmayılov',
        );

        $document = app(OrderTemplateCompiler::class)->compile($template, [
            'personnel' => $personnel->fresh(['structure', 'position']),
            'fields' => ['days' => '14', 'start_date' => '19.05.2026-cı il', 'end_date' => '03.06.2026-cı il'],
            'order_number' => '214-M',
            'order_date' => '14 may 2026-cı il',
        ]);

        $html = app(OrderDocumentHtmlRenderer::class)->render($document);

        // Declined forms must appear — the whole point of the redesign.
        $this->assertStringContainsString('Keşlə Qeyri-Qida Satış mərkəzinin', $html);
        $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', $html);
        $this->assertStringContainsString('R.B.Bayramovun ərizəsi.', $html);
        $this->assertStringContainsString('14 təqvim günü', $html);
        $this->assertStringContainsString('№ 214-M', $html);
        // No unresolved placeholders leaked through.
        $this->assertStringNotContainsString('{{', $html);
        $this->assertStringNotContainsString('___', $html);

        $path = app(OrderDocumentDocxRenderer::class)->renderToFile($document);
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', $xml);
        } finally {
            @unlink($path);
        }
    }

    private function makePersonnel(int $structureId, int $positionId): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Bayramov',
            'name' => 'Ruslan',
            'patronymic' => 'Bəxtiyar',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'work_norm_id' => 1,
            'structure_id' => $structureId,
            'position_id' => $positionId,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
