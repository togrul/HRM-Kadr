<?php

namespace Tests\Feature\Security;

use App\Modules\Orders\Application\Document\DocxPlaceholderParser;
use App\Modules\Orders\Application\Document\UnsafeDocxException;
use App\Support\Exports\CsvSafe;
use App\Support\Exports\RawImportValueBinder;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Tests\TestCase;
use ZipArchive;

/**
 * İxrac faylları formula yeridilməsinə (=, +, -, @, TAB, CR) qarşı qorunur; rəqəmlər rəqəm qalır.
 * Word ixracında dəyərlər XML-ə escape olunur. Sifariş şablonu (docx) zip bomb-a qarşı yoxlanır.
 */
class ExportInjectionTest extends TestCase
{
    public function test_xlsx_from_array_stores_dangerous_strings_as_text_and_keeps_numbers_numeric(): void
    {
        $export = new class implements FromArray
        {
            public function array(): array
            {
                return [['=HYPERLINK("http://evil","x")', '+cmd|calc', '-2+3', '@SUM(A1)', 42, '-5', '12.50', 'Adi mətn', '-']];
            }
        };

        $sheet = $this->load(Excel::raw($export, ExcelFormat::XLSX), 'xlsx');

        foreach (['A1', 'B1', 'C1', 'D1'] as $cell) {
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell($cell)->getDataType(), $cell);
            $this->assertStringStartsWith("'", (string) $sheet->getCell($cell)->getValue(), $cell);
        }

        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('E1')->getDataType());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('F1')->getDataType());
        $this->assertEquals(-5, $sheet->getCell('F1')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('G1')->getDataType());
        $this->assertSame('Adi mətn', $sheet->getCell('H1')->getValue());
        $this->assertSame('-', $sheet->getCell('I1')->getValue(), 'Tək "-" yer tutucusuna toxunulmur.');
    }

    public function test_xlsx_from_view_exports_are_protected_too(): void
    {
        $export = new class implements FromView
        {
            public function view(): View
            {
                return view()->file($this->template());
            }

            private function template(): string
            {
                $path = storage_path('framework/testing/export-injection.blade.php');
                @mkdir(dirname($path), 0777, true);
                file_put_contents($path, '<table><tr><td>=1+1</td><td>150</td></tr></table>');

                return $path;
            }
        };

        $sheet = $this->load(Excel::raw($export, ExcelFormat::XLSX), 'xlsx');

        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A1')->getDataType());
        $this->assertSame("'=1+1", $sheet->getCell('A1')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('B1')->getDataType());
    }

    public function test_csv_exports_neutralise_formulas(): void
    {
        $export = new class implements FromArray
        {
            public function array(): array
            {
                return [['=1+1', 7, "\tTAB"]];
            }
        };

        $csv = (string) Excel::raw($export, ExcelFormat::CSV);
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString('"7"', $csv);

        $this->assertSame(["'=SUM(A1)", "'+cmd", "'@x", 15, '-3.5', '+994501112233', '', "'\rcmd"], CsvSafe::row(['=SUM(A1)', '+cmd', '@x', 15, '-3.5', '+994501112233', '', "\rcmd"]));
    }

    public function test_imports_read_values_unchanged(): void
    {
        $this->assertInstanceOf(RawImportValueBinder::class, new \App\Modules\PerformanceEvaluation\Imports\PerformanceTestQuestionSheetImport);

        $path = storage_path('framework/testing/import.csv');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, "sual\n-mətn\n");

        $rows = Excel::toArray(new RawImportValueBinder, $path, null, ExcelFormat::CSV)[0];
        $this->assertSame('-mətn', $rows[1][0]);
        @unlink($path);
    }

    public function test_word_output_escaping_is_enabled_globally(): void
    {
        $this->assertTrue(Settings::isOutputEscapingEnabled());

        $word = new PhpWord;
        $word->addSection()->addText('Əliyev </w:t><w:t>& Co <script>');
        $path = storage_path('framework/testing/escape.docx');
        @mkdir(dirname($path), 0777, true);
        $word->save($path);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('&lt;/w:t&gt;&lt;w:t&gt;&amp; Co &lt;script&gt;', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'Sənəd XML-i etibarlı qalmalıdır.');
    }

    public function test_docx_template_parser_rejects_a_zip_bomb(): void
    {
        $path = storage_path('framework/testing/bomb.docx');
        @mkdir(dirname($path), 0777, true);
        // Məzmun diskdə hissə-hissə yaradılır: 20 MB-lıq sətri yaddaşda qurmaq tam dəstdə
        // test prosesinin yaddaş limitini aşırdı.
        $payload = tempnam(sys_get_temp_dir(), 'bomb');
        $handle = fopen($payload, 'wb');
        $chunk = str_repeat('A', 1024 * 1024);
        for ($written = 0; $written <= DocxPlaceholderParser::MAX_TEXT_PART; $written += strlen($chunk)) {
            fwrite($handle, $chunk);
        }
        fclose($handle);

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($payload, 'word/document.xml');
        $zip->close();

        try {
            $this->expectException(UnsafeDocxException::class);
            app(DocxPlaceholderParser::class)->extract($path);
        } finally {
            @unlink($path);
            @unlink($payload);
        }
    }

    private function load(string $binary, string $extension): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $path = storage_path('framework/testing/export.'.$extension);
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $binary);

        $sheet = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        return $sheet;
    }
}
