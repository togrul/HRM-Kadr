<?php

namespace App\Support\Language;

/**
 * Azerbaijani noun declension (hal-çəkim) with vowel harmony.
 *
 * Built for order (əmr) generation, where a personnel's name must appear in the
 * grammatical case the sentence requires, e.g.
 *   nominative: "Cəfərova Fidan Məsud oğlu"
 *   dative:     "Cəfərova Fidan Məsud oğluna"   (… oğluna … verilsin)
 *   genitive:   "Rzayev Murad Elşən oğlunun"    (… oğlunun … iştirakına)
 *
 * The engine covers the regular vowel-harmony rules and the special possessive
 * forms "oğlu"/"qızı" (which take the -n- buffer). It is intentionally
 * deterministic and side-effect free so it can be unit-tested exhaustively;
 * the ~5% of irregular surnames are meant to be corrected by the HR user in the
 * order preview, per the agreed "auto + editable preview" approach.
 */
class AzerbaijaniDeclension
{
    private const BACK_VOWELS = ['a', 'ı', 'o', 'u'];

    private const FRONT_VOWELS = ['e', 'ə', 'i', 'ö', 'ü'];

    /** 3rd-person possessive words decline with the -n- buffer (fixed forms). */
    private const POSSESSIVE_FORMS = [
        'oğlu' => [
            'genitive' => 'oğlunun',
            'dative' => 'oğluna',
            'accusative' => 'oğlunu',
            'locative' => 'oğlunda',
            'ablative' => 'oğlundan',
        ],
        'qızı' => [
            'genitive' => 'qızının',
            'dative' => 'qızına',
            'accusative' => 'qızını',
            'locative' => 'qızında',
            'ablative' => 'qızından',
        ],
    ];

    public function genitive(string $word): string
    {
        return $this->declineWord($word, 'genitive');
    }

    public function dative(string $word): string
    {
        return $this->declineWord($word, 'dative');
    }

    public function accusative(string $word): string
    {
        return $this->declineWord($word, 'accusative');
    }

    public function locative(string $word): string
    {
        return $this->declineWord($word, 'locative');
    }

    public function ablative(string $word): string
    {
        return $this->declineWord($word, 'ablative');
    }

    /**
     * Decline a full personal name ("Surname Name Patronymic [oğlu|qızı]") by
     * inflecting its last token (the oğlu/qızı suffix when present, otherwise the
     * trailing surname/patronymic).
     */
    public function nameGenitive(string $fullName): string
    {
        return $this->declineLastToken($fullName, 'genitive');
    }

    public function nameDative(string $fullName): string
    {
        return $this->declineLastToken($fullName, 'dative');
    }

    public function declineName(string $fullName, string $case): string
    {
        return $this->declineLastToken($fullName, $case);
    }

    private function declineLastToken(string $fullName, string $case): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $fullName));
        if ($name === '') {
            return '';
        }

        $tokens = explode(' ', $name);
        $last = array_pop($tokens);
        $declined = $this->declineWord($last, $case);

        $tokens[] = $declined;

        return implode(' ', $tokens);
    }

    private function declineWord(string $word, string $case): string
    {
        $word = trim($word);
        if ($word === '') {
            return '';
        }

        $lower = $this->mbLower($word);
        if (isset(self::POSSESSIVE_FORMS[$lower])) {
            return self::POSSESSIVE_FORMS[$lower][$case];
        }

        $lastVowel = $this->lastVowel($word);
        if ($lastVowel === null) {
            // No vowel to harmonize against (initials, abbreviations) — leave as-is.
            return $word;
        }

        $endsWithVowel = $this->isVowel($this->mbLastChar($word));

        return $word.$this->suffix($case, $lastVowel, $endsWithVowel);
    }

    private function suffix(string $case, string $lastVowel, bool $afterVowel): string
    {
        $two = $this->twoWay($lastVowel);   // a / ə
        $four = $this->fourWay($lastVowel); // ı / i / u / ü

        return match ($case) {
            'genitive' => ($afterVowel ? 'n' : '').$four.'n',
            'dative' => $afterVowel ? 'y'.$two : $two,
            'accusative' => ($afterVowel ? 'n' : '').$four,
            'locative' => 'd'.$two,
            'ablative' => 'd'.$two.'n',
            default => '',
        };
    }

    /** 2-way harmony: back vowels → "a", front vowels → "ə". */
    private function twoWay(string $lastVowel): string
    {
        return in_array($lastVowel, self::BACK_VOWELS, true) ? 'a' : 'ə';
    }

    /** 4-way harmony: a,ı→ı  e,ə,i→i  o,u→u  ö,ü→ü. */
    private function fourWay(string $lastVowel): string
    {
        return match ($lastVowel) {
            'a', 'ı' => 'ı',
            'o', 'u' => 'u',
            'ö', 'ü' => 'ü',
            default => 'i', // e, ə, i
        };
    }

    private function lastVowel(string $word): ?string
    {
        $vowels = array_merge(self::BACK_VOWELS, self::FRONT_VOWELS);
        $chars = $this->mbChars($this->mbLower($word));

        for ($i = count($chars) - 1; $i >= 0; $i--) {
            if (in_array($chars[$i], $vowels, true)) {
                return $chars[$i];
            }
        }

        return null;
    }

    private function isVowel(string $char): bool
    {
        $vowels = array_merge(self::BACK_VOWELS, self::FRONT_VOWELS);

        return in_array($this->mbLower($char), $vowels, true);
    }

    /** @return string[] */
    private function mbChars(string $value): array
    {
        return mb_str_split($value);
    }

    private function mbLastChar(string $value): string
    {
        $chars = $this->mbChars($value);

        return $chars === [] ? '' : $chars[count($chars) - 1];
    }

    private function mbLower(string $value): string
    {
        // mb_strtolower does not lowercase the dotted/dotless I pair the Azerbaijani
        // way; handle those explicitly before the generic lowercase.
        $value = str_replace(['I', 'İ'], ['ı', 'i'], $value);

        return mb_strtolower($value, 'UTF-8');
    }
}
