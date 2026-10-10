<?php

namespace App\Modules\Orders\Application\Document;

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * PhpWord's TemplateProcessor with the two repeat mechanisms a multi-participant
 * (çoxşəxsli) order uses, both indexing the repeated ${token}s as ${token#1}, ${token#2}…:
 *
 *   - a table row: every row of the document body holding a participant-only token is
 *     repeated once per participant. The row is located by its own <w:tr> element, so a
 *     shared token printed both in a sentence and in the row never anchors the wrong row
 *     (PhpWord's cloneRow() anchors on the first occurrence anywhere in the body);
 *   - a block: the paragraphs between the [İştirakçılar] and [/İştirakçılar] marker
 *     paragraphs (normalized to ${participants} / ${/participants}) are repeated, for orders
 *     that list people as numbered paragraphs instead of a table.
 */
class ParticipantTemplateProcessor extends TemplateProcessor
{
    /** Token name of the block markers ([İştirakçılar] … [/İştirakçılar]). */
    public const BLOCK = 'participants';

    /**
     * Repeat the marked participants block; false when the document has no complete block.
     */
    public function cloneParticipantBlock(int $count): bool
    {
        $open = '${'.self::BLOCK.'}';
        $close = '${/'.self::BLOCK.'}';

        if (! str_contains($this->tempDocumentMainPart, $open) || ! str_contains($this->tempDocumentMainPart, $close)) {
            return false;
        }

        return $this->cloneBlock(self::BLOCK, $count, true, true) !== null;
    }

    /**
     * Repeat every body table row that holds one of the given tokens; the number of rows
     * repeated (0 when no such row exists).
     *
     * @param  list<string>  $anchors  bare token names
     */
    public function cloneParticipantRows(array $anchors, int $count): int
    {
        if ($anchors === []) {
            return 0;
        }

        $xml = $this->tempDocumentMainPart;
        if (! preg_match_all('/<w:tr[\s>].*?<\/w:tr>/s', $xml, $rows, PREG_OFFSET_CAPTURE)) {
            return 0;
        }

        $result = '';
        $offset = 0;
        $cloned = 0;

        foreach ($rows[0] as [$row, $position]) {
            if (! $this->holdsAny($row, $anchors)) {
                continue;
            }

            $result .= substr($xml, $offset, $position - $offset).implode('', $this->indexClonedVariables($count, $row));
            $offset = $position + strlen($row);
            $cloned++;
        }

        if ($cloned > 0) {
            $this->tempDocumentMainPart = $result.substr($xml, $offset);
        }

        return $cloned;
    }

    /**
     * @param  list<string>  $anchors
     */
    private function holdsAny(string $xml, array $anchors): bool
    {
        foreach ($anchors as $anchor) {
            if (str_contains($xml, '${'.$anchor.'}')) {
                return true;
            }
        }

        return false;
    }
}
