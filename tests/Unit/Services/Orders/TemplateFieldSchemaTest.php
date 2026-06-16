<?php

namespace Tests\Unit\Services\Orders;

use App\Services\Orders\Document\OrderTemplatePresets;
use App\Services\Orders\Document\TemplateBlock;
use App\Services\Orders\Document\TemplateFieldSchema;
use App\Services\Orders\Variables\VariableInterpolator;
use PHPUnit\Framework\TestCase;

class TemplateFieldSchemaTest extends TestCase
{
    private function schema(): TemplateFieldSchema
    {
        return new TemplateFieldSchema(new VariableInterpolator);
    }

    public function test_it_derives_the_fields_a_template_references(): void
    {
        $fields = $this->schema()->for((new OrderTemplatePresets)->blocks('leave'));
        $keys = array_column($fields, 'key');

        $this->assertEqualsCanonicalizing(
            ['work_year', 'days', 'start_date', 'end_date', 'return_date', 'responsible'],
            $keys
        );

        $days = collect($fields)->firstWhere('key', 'days');
        $this->assertSame('Gün sayı', $days['label']);
        $this->assertSame('number', $days['type']);
        $this->assertSame('field.days', $days['placeholder']);
    }

    public function test_employee_and_system_placeholders_are_not_fields(): void
    {
        // The leave template references employee.* and system.* too — they must NOT
        // become input fields (they resolve from personnel / order context).
        $fields = $this->schema()->for((new OrderTemplatePresets)->blocks('leave'));
        $keys = array_column($fields, 'key');

        $this->assertNotContains('full_name_dative', $keys);
        $this->assertNotContains('order_number', $keys);
    }

    public function test_unknown_field_falls_back_to_a_text_input(): void
    {
        $fields = $this->schema()->for([TemplateBlock::paragraph('{{ field.custom_thing }}')]);

        $this->assertSame('custom_thing', $fields[0]['key']);
        $this->assertSame('text', $fields[0]['type']);
        $this->assertSame('Custom thing', $fields[0]['label']);
    }
}
