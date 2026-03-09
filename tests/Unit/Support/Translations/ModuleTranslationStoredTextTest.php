<?php

namespace Tests\Unit\Support\Translations;

use App\Support\Translations\ModuleTranslation;
use Tests\TestCase;

class ModuleTranslationStoredTextTest extends TestCase
{
    public function test_it_keeps_literal_stored_text_untouched(): void
    {
        $this->assertSame('Select personnel', ModuleTranslation::resolveStoredText('Select personnel'));
        $this->assertSame('Timing', ModuleTranslation::resolveStoredText(' Timing '));
    }

    public function test_it_translates_canonical_namespaced_keys(): void
    {
        app()->setLocale('az');

        $this->assertSame(
            'Əməkdaş seçin',
            ModuleTranslation::resolveStoredText('orders::template_metadata_defaults.fields.select_personnel')
        );

        $this->assertSame(
            'Qrup başlığı',
            ModuleTranslation::resolveStoredText('orders::template_set_type.labels.group_title')
        );
    }

    public function test_it_falls_back_to_original_key_when_namespaced_key_is_missing(): void
    {
        $missingKey = 'orders::template_runtime.messages.missing_example_key';

        $this->assertSame($missingKey, ModuleTranslation::resolveStoredText($missingKey));
    }
}
