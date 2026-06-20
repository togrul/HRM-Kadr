<?php

namespace App\Services\Orders\Document;

use RuntimeException;
use ZipArchive;

/**
 * Parses an uploaded MS Word (.docx) order template for [bracket] placeholders and
 * rewrites them into clean ${token} markers for PhpWord's TemplateProcessor.
 *
 * The fragile work happens here, ONCE, at design time: Word splits a typed
 * "[Başlama tarixi]" across several <w:r> runs (spell-check / cursor boundaries), so a
 * naive replace over a single run misses it. We collapse each paragraph's <w:t> runs
 * into one logical string to detect placeholders, then on normalize we put the token
 * into the run that owned the placeholder's first character and strip the matched text
 * from every run it spanned — surrounding text keeps its own runs and formatting.
 *
 * The .docx is a zip; we only read/rewrite the text XML parts (document, headers,
 * footers) and never execute anything inside it.
 */
class DocxPlaceholderParser
{
    /** Bracket placeholder: any run of chars except brackets/newlines, e.g. [Başlama tarixi]. */
    private const PLACEHOLDER = '/\[([^\[\]\r\n]+)\]/u';

    /**
     * Distinct, trimmed [bracket] labels in first-seen order across the body, headers
     * and footers.
     *
     * @return array<int,string>
     */
    public function extract(string $docxPath): array
    {
        $labels = [];
        foreach ($this->textParts($docxPath) as $xml) {
            foreach ($this->paragraphTexts($xml) as $text) {
                if (! preg_match_all(self::PLACEHOLDER, $text, $matches)) {
                    continue;
                }
                foreach ($matches[1] as $raw) {
                    $label = trim($raw);
                    if ($label !== '' && ! in_array($label, $labels, true)) {
                        $labels[] = $label;
                    }
                }
            }
        }

        return $labels;
    }

    /**
     * Copy the source .docx to $destDocxPath with every recognised [label] rewritten to
     * its ${token}. Tokens must match ${[A-Za-z0-9_]+} for TemplateProcessor.
     *
     * @param  array<string,string>  $labelToToken  raw label => bare token (no braces)
     */
    public function normalize(string $srcDocxPath, array $labelToToken, string $destDocxPath): void
    {
        if (! @copy($srcDocxPath, $destDocxPath)) {
            throw new RuntimeException("Unable to copy template to {$destDocxPath}.");
        }

        $zip = new ZipArchive;
        if ($zip->open($destDocxPath) !== true) {
            throw new RuntimeException("Unable to open .docx archive {$destDocxPath}.");
        }

        $temps = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! $this->isTextPart($name)) {
                continue;
            }
            $xml = (string) $zip->getFromIndex($i);
            // replaceFile defers the write until close(), so the temp must outlive it.
            $temps[] = $temp = $this->writeTemp($this->normalizeXml($xml, $labelToToken));
            $zip->replaceFile($temp, $i);
        }

        $zip->close();

        foreach ($temps as $temp) {
            @unlink($temp);
        }
    }

    /**
     * The text-bearing XML parts of the archive (document body + every header/footer).
     *
     * @return array<int,string>
     */
    private function textParts(string $docxPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException("Unable to open .docx archive {$docxPath}.");
        }

        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($this->isTextPart($name)) {
                $parts[] = (string) $zip->getFromIndex($i);
            }
        }
        $zip->close();

        return $parts;
    }

    private function isTextPart(string $name): bool
    {
        return $name === 'word/document.xml'
            || (bool) preg_match('#^word/(header|footer)\d*\.xml$#', $name);
    }

    /**
     * The plain text of each <w:p> paragraph, with run-splitting collapsed.
     *
     * @return array<int,string>
     */
    private function paragraphTexts(string $xml): array
    {
        if (! preg_match_all('/<w:p\b[^>]*>.*?<\/w:p>/su', $xml, $paragraphs)) {
            return [];
        }

        return array_map(fn (string $p) => $this->concatRuns($p), $paragraphs[0]);
    }

    /** Concatenate (decoded) text of every <w:t> in a paragraph, in document order. */
    private function concatRuns(string $paragraph): string
    {
        if (! preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/su', $paragraph, $runs)) {
            return '';
        }

        return implode('', array_map(fn (string $t) => $this->xmlDecode($t), $runs[1]));
    }

    private function normalizeXml(string $xml, array $labelToToken): string
    {
        if ($labelToToken === []) {
            return $xml;
        }

        return (string) preg_replace_callback(
            '/<w:p\b[^>]*>.*?<\/w:p>/su',
            fn (array $m) => $this->normalizeParagraph($m[0], $labelToToken),
            $xml,
        );
    }

    /**
     * Copy the source .docx to $destDocxPath with each given literal string rewritten to
     * its replacement (e.g. turning a real value into a "[label]" while preparing a
     * template). Run-split and tab-bearing paragraphs are handled the same way as
     * normalize() — surrounding text and layout are preserved.
     *
     * @param  array<string,string>  $literalToReplacement  exact text => replacement
     */
    public function rewriteLiterals(string $srcDocxPath, array $literalToReplacement, string $destDocxPath): void
    {
        if (! @copy($srcDocxPath, $destDocxPath)) {
            throw new RuntimeException("Unable to copy template to {$destDocxPath}.");
        }

        $zip = new ZipArchive;
        if ($zip->open($destDocxPath) !== true) {
            throw new RuntimeException("Unable to open .docx archive {$destDocxPath}.");
        }

        $temps = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (! $this->isTextPart($zip->getNameIndex($i))) {
                continue;
            }
            $xml = (string) $zip->getFromIndex($i);
            $rewritten = (string) preg_replace_callback(
                '/<w:p\b[^>]*>.*?<\/w:p>/su',
                fn (array $m) => $this->rewriteParagraph($m[0], fn (string $c) => $this->literalReplacements($c, $literalToReplacement)),
                $xml,
            );
            $temps[] = $temp = $this->writeTemp($rewritten);
            $zip->replaceFile($temp, $i);
        }

        $zip->close();

        foreach ($temps as $temp) {
            @unlink($temp);
        }
    }

    /**
     * Rewrite [label] → ${token} inside one paragraph, preserving the runs around the
     * placeholder. Only touched if the paragraph actually contains a known placeholder.
     *
     * @param  array<string,string>  $labelToToken
     */
    private function normalizeParagraph(string $paragraph, array $labelToToken): string
    {
        return $this->rewriteParagraph($paragraph, fn (string $concat) => $this->matchReplacements($concat, $labelToToken));
    }

    /**
     * Shared run-preserving rewrite: the finder returns, keyed by the start character
     * index in the paragraph's concatenated text, the replacement string and the index
     * just past the matched span. Text and elements (tabs, run properties) around the
     * matches are kept byte-for-byte.
     *
     * @param  \Closure(string):array<int,array{token:string,end:int}>  $finder
     */
    private function rewriteParagraph(string $paragraph, \Closure $finder): string
    {
        if (! preg_match_all('/(<w:t\b[^>]*>)(.*?)(<\/w:t>)/su', $paragraph, $runs, PREG_OFFSET_CAPTURE)) {
            return $paragraph;
        }

        // Build the concatenated decoded text and a per-character owner map.
        $concat = '';
        $owner = [];
        $segments = [];
        foreach ($runs[2] as $idx => [$inner]) {
            $text = $this->xmlDecode($inner);
            $start = mb_strlen($concat, 'UTF-8');
            $chars = $this->chars($text);
            foreach ($chars as $offset => $_) {
                $owner[$start + $offset] = $idx;
            }
            $concat .= $text;
            $segments[$idx] = [
                'fullStart' => $runs[0][$idx][1],
                'fullLength' => strlen($runs[0][$idx][0]),
                'close' => $runs[3][$idx][0],
                'new' => '',
            ];
        }

        $replacements = $finder($concat);
        if ($replacements === []) {
            return $paragraph;
        }

        // Walk the concatenated text, emitting characters into their owning run and the
        // ${token} (once) into the run that owned the placeholder's first character.
        $charList = $this->chars($concat);
        $count = count($charList);
        $i = 0;
        while ($i < $count) {
            if (isset($replacements[$i])) {
                $segments[$owner[$i]]['new'] .= $replacements[$i]['token'];
                $i = $replacements[$i]['end'];

                continue;
            }
            $segments[$owner[$i]]['new'] .= $charList[$i];
            $i++;
        }

        // Rebuild the paragraph, swapping each <w:t> body for its rewritten text and
        // keeping everything between runs (run properties, etc.) byte-for-byte.
        $out = '';
        $cursor = 0;
        foreach ($segments as $segment) {
            $out .= substr($paragraph, $cursor, $segment['fullStart'] - $cursor);
            $out .= '<w:t xml:space="preserve">'.$this->xmlEncode($segment['new']).$segment['close'];
            $cursor = $segment['fullStart'] + $segment['fullLength'];
        }
        $out .= substr($paragraph, $cursor);

        return $out;
    }

    /**
     * Map each placeholder match's starting character index to its ${token} and the
     * character index just past the match, so the walker can skip the matched span.
     *
     * @param  array<string,string>  $labelToToken
     * @return array<int,array{token:string,end:int}>
     */
    private function matchReplacements(string $concat, array $labelToToken): array
    {
        if (! preg_match_all(self::PLACEHOLDER, $concat, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $replacements = [];
        foreach ($matches[0] as $i => [$full, $byteOffset]) {
            $label = trim($matches[1][$i][0]);
            if (! isset($labelToToken[$label])) {
                continue;
            }
            $start = mb_strlen(substr($concat, 0, $byteOffset), 'UTF-8');
            $end = $start + mb_strlen($full, 'UTF-8');
            $replacements[$start] = ['token' => '${'.$labelToToken[$label].'}', 'end' => $end];
        }

        return $replacements;
    }

    /**
     * Find every occurrence of each literal search string in the concatenated text,
     * keyed by start character index → replacement + end index.
     *
     * @param  array<string,string>  $map  search => replacement
     * @return array<int,array{token:string,end:int}>
     */
    private function literalReplacements(string $concat, array $map): array
    {
        $replacements = [];
        foreach ($map as $search => $replacement) {
            if ($search === '') {
                continue;
            }
            $offset = 0;
            $length = mb_strlen($search, 'UTF-8');
            while (($pos = mb_strpos($concat, $search, $offset, 'UTF-8')) !== false) {
                $replacements[$pos] = ['token' => $replacement, 'end' => $pos + $length];
                $offset = $pos + $length;
            }
        }
        ksort($replacements);

        return $replacements;
    }

    /** @return array<int,string> */
    private function chars(string $text): array
    {
        return $text === '' ? [] : (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private function xmlDecode(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xmlEncode(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function writeTemp(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docxpart_');
        file_put_contents($path, $contents);

        return $path;
    }
}
