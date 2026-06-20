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
