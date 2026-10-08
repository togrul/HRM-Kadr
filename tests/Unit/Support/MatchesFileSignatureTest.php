<?php

namespace Tests\Unit\Support;

use App\Support\Uploads\MatchesFileSignature;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MatchesFileSignatureTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_text_renamed_to_pdf_is_rejected(): void
    {
        $this->assertFalse($this->passes('qeyd.pdf', "bu PDF deyil, adi mətndir\n"));
    }

    public function test_html_renamed_to_image_is_rejected(): void
    {
        $this->assertFalse($this->passes('şəkil.png', '<html><body><script>alert(1)</script></body></html>'));
    }

    public function test_real_pdf_is_accepted(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";

        $this->assertTrue($this->passes('əmr.pdf', $pdf));
    }

    public function test_unknown_extension_is_left_to_the_mimes_rule(): void
    {
        $this->assertTrue($this->passes('qeyd.txt', 'adi mətn'));
    }

    private function passes(string $name, string $contents): bool
    {
        $path = tempnam(sys_get_temp_dir(), 'sig');
        file_put_contents($path, $contents);
        $this->paths[] = $path;

        $file = new UploadedFile($path, $name, null, null, true);

        return Validator::make(['file' => $file], ['file' => [new MatchesFileSignature]])->passes();
    }
}
