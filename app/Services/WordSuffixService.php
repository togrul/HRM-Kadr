<?php

namespace App\Services;

class WordSuffixService
{
    public function getNumberSuffix(int $year)
    {
        $lastDigit = $year % 10;

        if($lastDigit == 0)
        {
            // Remove trailing zeros
            while ($year % 10 === 0 && $year > 0) {
                $year /= 10;
                $lastDigit .= $year % 10;
            }

            $lastDigit = (int)strrev($lastDigit);
        }

        return $this->getSuffix($lastDigit);
    }

    public function getMultiSuffix($text,$multi = true)
    {
        if (!$text) {
            return '';
        }

        $_char_list = ['a','ı','u','o','i','ə','ü','e','ö'];

        // eger son herf sait deilse 2 ci herfi gotur ona uygun elave et.
        $lastChar = mb_substr($text, -1);

        if (!in_array($lastChar, $_char_list))
        {
            $lastChar = mb_substr($text, -2,1);
            $suffix = match($lastChar)
            {
                'a','ı' => 'ı',
                'i','ə','e' => 'i',
                'u','o' => 'u',
                'ü','ö' => 'ü'
            };
        }
        else
        {
            $suffix = match($lastChar)
            {
                'a','ı' => 'sı',
                'i','ə','e' => 'si',
                'u','o' => 'su',
                'ü','ö' => 'sü'
            };
        }

        return $multi
                ? $text . $suffix . $this->getStructureSuffix($text,true)
                : $text. $suffix;
    }

    public function getSurnameSuffix($text)
    {
        if (!$text) {
            return '';
        }

        $_char_list = ['a','ı','u','o','i','ə','ü','e','ö'];

        // eger son herf sait deilse 2 ci herfi gotur ona uygun elave et.
        $lastChar = mb_substr($text, -1);

        if (!in_array($lastChar, $_char_list))
        {
            $lastChar = mb_substr($text, -2,1);
            $suffix = match($lastChar)
            {
                'a','ı','u','o' => 'a',
                'i','ə','ü','e','ö' => 'ə'
            };
        }
        else
        {
            $suffix = match($lastChar)
            {
                'a','ı','u','o' => 'ya',
                'i','ə','ü','e','ö' => 'yə'
            };
        }

        return $text . $suffix;
    }

    public function getStructureSuffix($text , $onlySuffix = false,$mainStructure = false)
    {
        if (!$text) {
            return '';
        }

        if(is_numeric($text))
        {
           $suffix = $this->getNumberSuffix($text) . ($mainStructure ? " idarənin"  : " idarəsinin");
        }
        else
        {
            $_char_list = ['a','ı','u','o','i','ə','ü','e','ö','1','2','3'];

            // eger son herf sait deilse 2 ci herfi gotur ona uygun elave et.
            $lastChar = mb_substr($text, -1);

            if (!in_array($lastChar, $_char_list))
            {
                if($lastChar == 'k')
                {
                    $text = str_replace($lastChar,'y',$text);
                }
                $lastChar = mb_substr($text, -2,1);
                $suffix = match($lastChar)
                {
                    'a','ı' => 'ın',
                    'i','ə','e' => 'in',
                    'u','o' => 'un',
                    'ü','ö' => 'ün'
                };
            }
            else
            {
                $suffix = match($lastChar)
                {
                    'a','ı' => 'nın',
                    'i','ə','e','2' => 'nin',
                    'u','o' => 'nun',
                    'ü','ö' => 'nün',
                    '1' => 'in',
                    '3' => 'ün'
                };
            }
        }

        return $onlySuffix ? $suffix : $text . $suffix;
    }

    private function getSuffix($digit)
    {
        return match ($digit)
        {
            0,6,40,60,90 => "-cı",
            3,4,100,200,300,400,500,600,700,800,900 => "-cü",
            9,10,30 => "-cu",
            default => "-ci",
        };
    }
}
