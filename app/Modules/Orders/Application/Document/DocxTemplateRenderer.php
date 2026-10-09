<?php

namespace App\Modules\Orders\Application\Document;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;

/**
 * Fills a normalized ${token} Word master with resolved values and writes the final
 * .docx, mirroring the proven TemplateProcessor pattern (see Vacation/BusinessTrips).
 * The author's original Word formatting is preserved verbatim — only the tokens change.
 *
 * A multi-participant order also passes its per-participant values under PARTICIPANT_ROWS
 * (and the tokens that mark the repeating row under PARTICIPANT_ANCHORS): the marked block
 * and every table row holding such a token are repeated once per participant and filled
 * with that person's values ({@see ParticipantTemplateProcessor}). Without them the
 * document renders exactly as before.
 */
class DocxTemplateRenderer
{
    /** Value-map key carrying list<array<token,value>>, one map per participant. */
    public const PARTICIPANT_ROWS = '@participant_rows';

    /** Value-map key carrying the participant-only tokens that mark the repeating row. */
    public const PARTICIPANT_ANCHORS = '@participant_anchors';

    /**
     * @param  array<string,mixed>  $tokenValues  bare token => value (no ${} braces), plus the optional participant keys
     * @return string absolute path to the generated temp .docx
     */
    public function renderToFile(string $masterDocxPath, array $tokenValues): string
    {
        // Escape XML-special characters in substituted values so data containing
        // &, <, > can't corrupt the document.
        Settings::setOutputEscapingEnabled(true);

        $processor = new ParticipantTemplateProcessor(Storage::disk('local')->path($masterDocxPath));

        $rows = $tokenValues[self::PARTICIPANT_ROWS] ?? null;
        $anchors = (array) ($tokenValues[self::PARTICIPANT_ANCHORS] ?? []);
        unset($tokenValues[self::PARTICIPANT_ROWS], $tokenValues[self::PARTICIPANT_ANCHORS]);

        if (is_array($rows)) {
            $this->repeat($processor, array_values($rows), array_values($anchors));
        }

        foreach ($tokenValues as $token => $value) {
            // setValue replaces every occurrence; setting every declared token guarantees
            // no stray ${token} survives in the output.
            $processor->setValue((string) $token, (string) $value);
        }

        // Block markers left over (a single-person render, or a block never repeated) print nothing.
        foreach ([ParticipantTemplateProcessor::BLOCK, '/'.ParticipantTemplateProcessor::BLOCK] as $marker) {
            if (! array_key_exists($marker, $tokenValues)) {
                $processor->setValue($marker, '');
            }
        }

        $path = storage_path('app/tmp/order_'.Str::uuid()->toString().'.docx');
        File::ensureDirectoryExists(dirname($path));
        $processor->saveAs($path);

        return $path;
    }

    /**
     * Repeat the participants block / rows and fill each copy with its participant's values.
     *
     * @param  list<array<string,string>>  $rows
     * @param  list<string>  $anchors
     */
    private function repeat(ParticipantTemplateProcessor $processor, array $rows, array $anchors): void
    {
        $count = count($rows);
        $repeated = $processor->cloneParticipantBlock($count);
        $repeated = $processor->cloneParticipantRows($anchors, $count) > 0 || $repeated;

        if (! $repeated) {
            return;
        }

        foreach ($rows as $index => $row) {
            foreach ($row as $token => $value) {
                $processor->setValue($token.'#'.($index + 1), (string) $value);
            }
        }
    }
}
