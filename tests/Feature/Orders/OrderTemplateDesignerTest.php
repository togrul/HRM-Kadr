<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Modules\Orders\Livewire\OrderTemplateDesigner;
use App\Services\Orders\Document\OrderTemplateRepository;
use App\Services\Orders\Document\TemplateBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderTemplateDesignerTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_without_edit_orders_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(OrderTemplateDesigner::class)->assertForbidden();
    }

    public function test_it_creates_and_saves_a_new_order_type(): void
    {
        $this->actingAs($this->editor());

        Livewire::test(OrderTemplateDesigner::class)
            ->set('code', 'warning_letter')
            ->set('label', 'Xəbərdarlıq')
            ->set('rows', [
                ['kind' => TemplateBlock::HEADING, 'content' => '“ŞİRKƏT” MMC', 'bold' => true],
                ['kind' => TemplateBlock::PARAGRAPH, 'content' => '{{ employee.full_name_dative }} xəbərdarlıq edilsin.', 'align' => 'left'],
                ['kind' => TemplateBlock::SIGNATURE, 'content' => "müavin\nAd Soyad"],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('templateSaved');

        $repo = app(OrderTemplateRepository::class);
        $this->assertTrue($repo->exists('warning_letter'));

        $blocks = $repo->blocks('warning_letter');
        $this->assertCount(3, $blocks);
        $this->assertSame('{{ employee.full_name_dative }} xəbərdarlıq edilsin.', $blocks[1]->data['text']);
    }

    public function test_it_loads_an_existing_template_for_editing(): void
    {
        app(OrderTemplateRepository::class)->save('leave', 'Məzuniyyət', [
            TemplateBlock::heading('Head'),
            TemplateBlock::clauses(['Bənd bir.']),
        ]);

        $this->actingAs($this->editor());

        Livewire::test(OrderTemplateDesigner::class, ['code' => 'leave'])
            ->assertSet('isNew', false)
            ->assertSet('label', 'Məzuniyyət')
            ->assertSet('rows.0.kind', TemplateBlock::HEADING)
            ->assertSet('rows.1.content', 'Bənd bir.');
    }

    public function test_validation_rejects_a_bad_code(): void
    {
        $this->actingAs($this->editor());

        Livewire::test(OrderTemplateDesigner::class)
            ->set('code', 'Bad Code!!')
            ->set('label', 'X')
            ->call('save')
            ->assertHasErrors('code');
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('edit-orders', 'web'));

        return $user;
    }
}
