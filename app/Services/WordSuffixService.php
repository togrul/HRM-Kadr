<?php

namespace App\Services;

class WordSuffixService
{
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

    public function getStructureSuffix($text)
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
                'i','ə','e' => 'nin',
                'u','o' => 'nun',
                'ü','ö' => 'nün'
            };
        }

        return $text . $suffix;
    }
}
