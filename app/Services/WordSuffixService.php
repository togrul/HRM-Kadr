<?php

namespace App\Services;

use Illuminate\Support\Str;

class WordSuffixService
{
    private array $vowels = ['a', 'ı', 'u', 'o', 'i', 'ə', 'ü', 'e', 'ö'];

    private function checkLastCharAndVowel(string $text, int $offset = -1): array
    {
        $lastChar = mb_substr($text, $offset, 1);
        $isVowel = in_array($lastChar, $this->vowels);

        return [
            $lastChar, $isVowel,
        ];
    }

    private function determineSuffix(string $text): string
    {
        [$lastChar,$isVowel] = $this->checkLastCharAndVowel(text: $text);
        if (! $isVowel) {
            [$lastChar,] = $this->checkLastCharAndVowel(text: $text, offset: -2);
        }

        return match ($lastChar) {
            'a', 'ı' => $isVowel ? 'sı' : 'ı',
            'i', 'ə', 'e' => $isVowel ? 'si' : 'i',
            'u', 'o' => $isVowel ? 'su' : 'u',
            'ü', 'ö' => $isVowel ? 'sü' : 'ü',
        };
    }

    private function determineSurnameSuffix(string $text): string
    {
        [$lastChar,$isVowel] = $this->checkLastCharAndVowel(text: $text);

        if (! $isVowel) {
            [$lastChar,] = $this->checkLastCharAndVowel(text: $text, offset: -2);
        }

        return match ($lastChar) {
            'a', 'ı', 'u', 'o' => $isVowel ? 'ya' : 'a',
            'i', 'ə', 'ü', 'e', 'ö' => $isVowel ? 'yə' : 'ə',
        };
    }

     public function educationSuffix(string $text): string
    {
        [$lastChar,$isVowel] = $this->checkLastCharAndVowel(text: $text);

        if (! $isVowel) {
            [$lastChar,] = $this->checkLastCharAndVowel(text: $text, offset: -2);
        }                                       

        return match ($lastChar) {
            'a', 'ı' => $isVowel ? 'sını' : 'ını',
            'ə', 'e' => $isVowel ? 'sini' : 'ini',
            'u', 'o' => $isVowel ? 'nu' : 'unu',
            'ü', 'ö' => $isVowel ? 'nü' : 'ünü',
            'i' => $isVowel ? 'ni' : 'ini',
        };
    }

    public function getMilitarySuffix(string $text): string
    {
        [$lastChar,] = $this->checkLastCharAndVowel(text: $text);

        return match (Str::lower($lastChar)) {
            'q', 'o', 'a' => 'da',
            'n', 'x', 'm' => 'də',
        };
    }

    private function determineStructureSuffix(string $text): string
    {
        [$lastChar,$isVowel] = $this->checkLastCharAndVowel(text: $text);

        if (! $isVowel) {
            if ($lastChar === 'k') {
                $text = str_replace($lastChar, 'y', $text);
            }
            [$lastChar,] = $this->checkLastCharAndVowel(text: $text, offset: -2);
        }

        return match ($lastChar) {
            'a', 'ı' => $isVowel ? 'nın' : 'ın',
            'i', 'ə', 'e' => $isVowel ? 'nin' : 'in',
            'u', 'o' => $isVowel ? 'nun' : 'un',
            'ü', 'ö' => $isVowel ? 'nün' : 'ün',
            '1' => 'in',
            '2' => 'nin',
            '3' => 'ün',
        };
    }

    public function getNumberSuffix(int $year): string
    {
        $lastDigit = $year % 10;

        if ($lastDigit == 0) {
            // Remove trailing zeros
            while ($year % 10 === 0 && $year > 0) {
                $year /= 10;
                $lastDigit .= $year % 10;
            }

            $lastDigit = (int) strrev($lastDigit);
        }

        return $this->getSuffix($lastDigit);
    }

    public function getMultiSuffix($text, $multi = true): string
    {
        if (! $text) {
            return '';
        }

        $suffix = $this->determineSuffix($text);
        return $multi
                ? $text.$suffix.$this->getStructureSuffix($text, true)
                : $text.$suffix;
    }

    public function getSurnameSuffix($text): string
    {
        if (! $text) {
            return '';
        }

        $suffix = $this->determineSurnameSuffix($text);

        return $text.$suffix;
    }

    public function getStructureSuffix($text, $onlySuffix = false, $mainStructure = false, $useDetermine = false): string
    {
        if (! $text) {
            return '';
        }

        $append = $mainStructure ? 'nin' : ' sinin';

        $suffix = is_numeric($text)
            ? $this->getNumberSuffix((int) $text)." idarə{$append}"
            : ($useDetermine ? trim($append) : $this->determineStructureSuffix($text));

        return $onlySuffix ? $suffix : $text.$suffix;
    }

    public function getMonthDaySuffix($digit): string
    {
        $digit = (int) $digit;
        $lastDigit = $digit % 10 ?: $digit;
        $suffix = match ($lastDigit) {
            1,5,8 => '-i',
            2,7,20 => '-si',
            3,4 => '-ü',
            6 => '-sı',
            9,10,30 => '-u'
        };

        return "{$digit}{$suffix}";
    }

    public function getTimeSuffix($time): string
    {
        $digit = (int) $time;
        $lastDigit = $digit % 10 ?: $digit;
        $suffix = match ($lastDigit) {
            0,6,9,10,30,40 => '-da',
            default => '-də',
        };

        return "{$time}{$suffix}";
    }

    private function getSuffix($digit): string
    {
        return match ($digit) {
            0,6,40,60,90 => '-cı',
            3,4,100,200,300,400,500,600,700,800,900 => '-cü',
            9,10,30 => '-cu',
            default => '-ci',
        };
    }
}
