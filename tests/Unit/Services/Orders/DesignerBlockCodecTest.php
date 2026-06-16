<?php

namespace Tests\Unit\Services\Orders;

use App\Services\Orders\Document\DesignerBlockCodec;
use App\Services\Orders\Document\TemplateBlock;
use PHPUnit\Framework\TestCase;

class DesignerBlockCodecTest extends TestCase
{
    private function codec(): DesignerBlockCodec
    {
        return new DesignerBlockCodec;
    }

    public function test_blocks_round_trip_through_editable_rows(): void
    {
        $blocks = [
            TemplateBlock::heading('“ŞİRKƏT” MMC'),
            TemplateBlock::paragraph('{{ employee.full_name_dative }} icazə verilsin.'),
            TemplateBlock::clauses(['Birinci bənd.', 'İkinci bənd.'], numbered: true),
            TemplateBlock::signature(['müavin', 'üzrə'], 'Ad Soyad'),
        ];

        $rows = $this->codec()->toEditable($blocks);
        $rebuilt = $this->codec()->toBlocks($rows);

        $this->assertSame(TemplateBlock::HEADING, $rebuilt[0]->kind);
        $this->assertSame('“ŞİRKƏT” MMC', $rebuilt[0]->data['text']);

        $this->assertSame(TemplateBlock::CLAUSES, $rebuilt[2]->kind);
        $this->assertSame(['Birinci bənd.', 'İkinci bənd.'], $rebuilt[2]->data['items']);

        $this->assertSame(TemplateBlock::SIGNATURE, $rebuilt[3]->kind);
        $this->assertSame(['müavin', 'üzrə'], $rebuilt[3]->data['titleLines']);
        $this->assertSame('Ad Soyad', $rebuilt[3]->data['name']);
    }

    public function test_clauses_are_edited_one_item_per_line(): void
    {
        $row = ['kind' => TemplateBlock::CLAUSES, 'content' => "Bir.\nİki.\nÜç.", 'numbered' => true];
        $block = $this->codec()->toBlocks([$row])[0];

        $this->assertSame(['Bir.', 'İki.', 'Üç.'], $block->data['items']);
    }

    public function test_blank_row_defaults_to_a_paragraph(): void
    {
        $row = $this->codec()->blankRow();

        $this->assertSame(TemplateBlock::PARAGRAPH, $row['kind']);
        $this->assertSame('', $row['content']);
    }
}
