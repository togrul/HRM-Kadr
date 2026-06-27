<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Services\PersonnelServiceBookWordExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

/**
 * Characterization smoke test for the 16-page service-book DOCX export. The
 * service had no coverage; this pins that export() produces a valid, non-empty
 * Word2007 (zip) document for a representative personnel, providing a safety net
 * for future refactoring of the renderer.
 */
class PersonnelServiceBookWordExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_produces_a_valid_non_empty_docx(): void
    {
        $personnel = $this->makePersonnel();

        $path = app(PersonnelServiceBookWordExportService::class)->export($personnel);

        try {
            $this->assertFileExists($path);
            $this->assertGreaterThan(0, filesize($path));

            // A .docx is a zip; confirm it opens and carries the main document part.
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true, 'Exported file is not a valid zip/docx.');
            $this->assertNotFalse($zip->locateName('word/document.xml'), 'docx is missing word/document.xml.');
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    /**
     * Characterization test pinning the *structure* of the rendered document so a
     * future refactor of the page renderers cannot silently change the output.
     * It asserts the page-break count, the presence of every static section
     * heading, the seeded field values, and the relative order of the numbered
     * sections inside word/document.xml.
     */
    public function test_export_document_structure_is_stable(): void
    {
        $personnel = $this->makePersonnel();

        $path = app(PersonnelServiceBookWordExportService::class)->export($personnel);

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true, 'Exported file is not a valid zip/docx.');
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertIsString($xml, 'docx is missing word/document.xml.');

            // The renderer emits exactly five hard page breaks between the pages.
            $this->assertSame(
                5,
                substr_count($xml, 'w:type="page"'),
                'Page-break count drifted from the characterized output.'
            );

            // Static headings/labels that must remain in the document verbatim.
            $staticLabels = [
                'Azərbaycan Respublikası',
                'Dövlət Mühafizə Xidməti',
                'Şəxsi nömrəsi',
                'Hərbi və ya xüsusi rütbə',
                '1. Anadan olduğu gün, ay və il',
                '5. Təhsili',
                '9. Əmək fəaliyyəti',
                '10. Silahlı Qüvvələrdə',
                '11. Pensiya təyin edilərkən',
                '12. Xidməti vəzifələrini',
                '13. Azərbaycan Respublikasının',
                '14. Xarici ezamiyyətlər',
                '15. Hansı seçki orqanlarına seçilmişdir',
                '16. Əsirlikdə olubmu',
                '17. Atasının və anasının',
                '18. Ailə vəziyyəti',
                '19. Yaşadığı ünvan',
            ];
            foreach ($staticLabels as $label) {
                $this->assertStringContainsString(
                    $label,
                    $xml,
                    "Static label \"{$label}\" disappeared from the rendered document."
                );
            }

            // Seeded personnel values surfaced by the renderer.
            foreach (['Doe', 'Jane Smith', '01.01.1990', 'Main st', 'Subay'] as $value) {
                $this->assertStringContainsString(
                    $value,
                    $xml,
                    "Seeded value \"{$value}\" no longer appears in the rendered document."
                );
            }

            // Numbered sections must keep their original top-to-bottom order.
            $orderedSections = [
                '1. Anadan olduğu gün, ay və il',
                '9. Əmək fəaliyyəti',
                '10. Silahlı Qüvvələrdə',
                '11. Pensiya təyin edilərkən',
                '13. Azərbaycan Respublikasının',
                '14. Xarici ezamiyyətlər',
                '17. Atasının və anasının',
            ];
            $previous = -1;
            foreach ($orderedSections as $section) {
                $position = strpos($xml, $section);
                $this->assertNotFalse($position, "Section \"{$section}\" missing from document.");
                $this->assertGreaterThan(
                    $previous,
                    $position,
                    "Section \"{$section}\" is out of its characterized order."
                );
                $previous = $position;
            }
        } finally {
            @unlink($path);
        }
    }

    private function makePersonnel(): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com',
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
}
