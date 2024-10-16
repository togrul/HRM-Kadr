<?php

namespace App\Services;

use App\Enums\TransportationEnum;
use App\Models\Order;

class GenerateWordReplaceContent
{
    public function __construct(
        public string $selectedBlade,
        public array $_replace_texts
    ) {}

    public function handle($key, $text, $index): array
    {
        switch ($this->selectedBlade) {
            case Order::BLADE_BUSINESS_TRIP:
                $title = str_replace(
                    array_keys($this->_replace_texts[$key][$index ?? 0]),
                    array_values($this->_replace_texts[$key][$index ?? 0]),
                    $text['title']
                );
                break;
            default:
                $title = str_replace(
                    array_keys($this->_replace_texts[$index ?? 0]),
                    array_values($this->_replace_texts[$index ?? 0]),
                    $text['title']
                );
                break;
        }
        $content = '';

        foreach ($text['content'] as $keyContent => $contentData) {
            $replaceKey = $index++;
            if (
                $this->selectedBlade == Order::BLADE_BUSINESS_TRIP
                && $this->_replace_texts[$key][$keyContent]['$transportation'] == TransportationEnum::CAR->name
                && ! empty($this->_replace_texts[$key][$keyContent]['$car'])
            ) {
                $this->_replace_texts[$key][$keyContent]['$car'] = 'Avtomaşın: '.$this->_replace_texts[$key][$keyContent]['$car'];
            }

            switch ($this->selectedBlade) {
                case Order::BLADE_BUSINESS_TRIP:
                    $replacedText = $this->_replace_texts[$key][$keyContent];
                    break;
                default:
                    $replacedText = $this->_replace_texts[$replaceKey];
                    break;
            }

            $replacedContent = str_replace(
                array_keys($replacedText),
                array_values($replacedText),
                $contentData
            );

            $replacedContent = in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP])
                ? '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr>'.$replacedContent.'<w:br/>'
                : $replacedContent;

            $content .= $replacedContent;
        }

        $content = str_replace('<w:br/>', '</w:t><w:br/><w:tab/><w:t xml:space="preserve">', $content);

        return [
            'title' => $title,
            'content' => $content,
        ];

    }
}
