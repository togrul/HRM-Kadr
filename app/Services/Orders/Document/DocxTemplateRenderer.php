<?php

namespace App\Services\Orders\Document;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Fills a normalized ${token} Word master with resolved values and writes the final
 * .docx, mirroring the proven TemplateProcessor pattern (see Vacation/BusinessTrips).
 * The author's original Word formatting is preserved verbatim — only the tokens change.
 */
class DocxTemplateRenderer
{
    /**
     * @param  array<string,string>  $tokenValues  bare token => value (no ${} braces)
     * @return string  absolute path to the generated temp .docx
     */
    public function renderToFile(string $masterDocxPath, array $tokenValues): string
    {
        // Escape XML-special characters in substituted values so data containing
        // &, <, > can't corrupt the document.
        Settings::setOutputEscapingEnabled(true);

        $processor = new TemplateProcessor(Storage::disk('local')->path($masterDocxPath));

        foreach ($tokenValues as $token => $value) {
            // setValue replaces every occurrence; setting every declared token guarantees
            // no stray ${token} survives in the output.
            $processor->setValue($token, $value);
        }

        $path = storage_path('app/tmp/order_'.Str::uuid()->toString().'.docx');
        File::ensureDirectoryExists(dirname($path));
        $processor->saveAs($path);

        return $path;
    }
}
