<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Services\Orders\Document\OrderDocumentHtmlRenderer;
use App\Services\Orders\Document\OrderTemplateCompiler;
use App\Services\Orders\Document\OrderTemplatePresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 5 — the real customer order types, encoded as block lists, generate correct
 * documents. Proves the block model handles the structural variety (numbered vs
 * unnumbered clauses, applicant-in-preamble) and that declension flows through.
 */
class OrderTemplatePresetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A superset of every field any preset references; interpolation only consumes
     * the ones a given template uses, so unused keys are harmless and this keeps the
     * "no ___" guarantee for all presets.
     */
    private function fieldsFor(string $code): array
    {
        return [
            'work_year' => '26.11.2025-25.11.2026-cı il',
            'days' => '14',
            'start_date' => '19.05.2026-cı il',
            'end_date' => '03.06.2026-cı il',
            'return_date' => '04.06.2026-cı il',
            'position' => 'sürücü-ekspeditor',
            'responsible' => 'Səbuhi Bağırov',
            'new_surname' => 'Bababəyli',
            'basis' => 'ərizə və Şəxsiyyət vəsiqəsinin surəti.',
            'reason' => 'səhhəti ilə əlaqədar',
            'period' => '01.05-14.05.2026-cı il',
            'institution' => 'Odlar Yurdu Universitetinin',
            'course' => '2-ci kurs',
            'new_structure' => 'Dağıtım şöbəsinin',
            'new_position' => 'rəisi',
            'location' => 'Xəzər rayonunda',
            'date' => '09.06.2026-cı il',
            'legal_basis' => 'Əmək Məcəlləsinin 75-ci maddəsinə əsasən',
            'legal_frame' => 'Əmək Məcəlləsinin 72-ci maddəsini',
            'violation' => 'təqsirli hərəkətləri ilə maddi ziyan vurub.',
            'investigation' => 'Araşdırma aktlarla sübuta yetirilib.',
        ];
    }

    public function test_every_preset_generates_a_clean_filled_order(): void
    {
        $personnel = $this->makePersonnel();
        $presets = app(OrderTemplatePresets::class);
        $compiler = app(OrderTemplateCompiler::class);
        $htmlRenderer = app(OrderDocumentHtmlRenderer::class);

        foreach (array_keys($presets->available()) as $code) {
            $document = $compiler->compileBlocks($presets->blocks($code), [
                'personnel' => $personnel,
                'fields' => $this->fieldsFor($code),
                'order_number' => '214-M',
                'order_date' => '14 may 2026-cı il',
                'system' => ['organization_city' => 'Bakı şəhəri'],
            ]);

            $html = $htmlRenderer->render($document);

            $this->assertStringNotContainsString('{{', $html, "Unresolved placeholder in [$code]");
            $this->assertStringNotContainsString('___', $html, "Missing value in [$code]");
            $this->assertStringContainsString('“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ', $html);
            $this->assertStringContainsString('Sübhan İsmayılov', $html);
        }
    }

    public function test_leave_renders_dative_and_genitive_forms(): void
    {
        $html = app(OrderDocumentHtmlRenderer::class)->render(
            app(OrderTemplateCompiler::class)->compileBlocks(
                app(OrderTemplatePresets::class)->blocks('leave'),
                [
                    'personnel' => $this->makePersonnel(),
                    'fields' => $this->fieldsFor('leave'),
                    'order_number' => '214-M',
                    'order_date' => '14 may 2026-cı il',
                    'system' => ['organization_city' => 'Bakı şəhəri'],
                ]
            )
        );

        $this->assertStringContainsString('Keşlə Qeyri-Qida Satış mərkəzinin', $html);
        $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', $html);
        $this->assertStringContainsString('R.B.Bayramovun ərizəsi.', $html);
    }

    public function test_surname_change_uses_unnumbered_clauses(): void
    {
        $html = app(OrderDocumentHtmlRenderer::class)->render(
            app(OrderTemplateCompiler::class)->compileBlocks(
                app(OrderTemplatePresets::class)->blocks('surname_change'),
                [
                    'personnel' => $this->makePersonnel(),
                    'fields' => $this->fieldsFor('surname_change'),
                    'order_number' => '763-İ',
                    'order_date' => '29 oktyabr 2025-ci il',
                    'system' => ['organization_city' => 'Bakı şəhəri'],
                ]
            )
        );

        // Unnumbered → rendered as paragraphs, not an <ol> clause list.
        $this->assertStringNotContainsString('<ol class="order-clauses">', $html);
        $this->assertStringContainsString('“Bababəyli” olması nəzərə alınsın', $html);
    }

    private function makePersonnel(): Personnel
    {
        $structure = Structure::query()->create([
            'name' => 'Keşlə Qeyri-Qida Satış mərkəzi',
            'shortname' => 'Keşlə QQS',
        ]);
        $position = Position::query()->create(['name' => 'satınalma operatoru']);

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
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ])->fresh(['structure', 'position']));
    }
}
