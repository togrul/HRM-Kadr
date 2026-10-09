<?php

namespace Tests\Feature\Orders;

use App\Models\OrderLog;
use App\Models\OrderParticipant;
use App\Models\OrderWordTemplate;
use App\Models\OutboxEvent;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelPunishment;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Modules\BusinessTrips\Livewire\BusinessTrips;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Modules\Integration\Infrastructure\EloquentIntegrationOutbox;
use App\Modules\Orders\Application\Document\DocxTemplateRenderer;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderLookupFieldRegistry;
use App\Modules\Orders\Infrastructure\Document\OrderNumbering;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Modules\Orders\Livewire\OrderTemplateDesigner;
use App\Modules\Personnel\Application\Services\Personnel360TimelineService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

/**
 * Multi-participant (çoxşəxsli) orders: the standard business trip sends a whole team in one
 * order — a participants table in the document, one trip per person on approval (all or
 * nothing, the blocking person named), one outbox event each, and the reversal undoing them
 * all. Single-person orders keep working exactly as before.
 */
class MultiParticipantOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->app->instance(IntegrationOutbox::class, new EloquentIntegrationOutbox);

        foreach (['ezamiyyet', 'emek_mezuniyyeti'] as $code) {
            $this->artisan('orders:seed-word-templates', ['--only' => $code])->assertSuccessful();
        }
    }

    public function test_the_standard_business_trip_is_a_multi_participant_template_with_the_new_fields(): void
    {
        $trip = $this->template('ezamiyyet');

        $this->assertTrue($trip->isMultiParticipant());
        $this->assertSame('override', $this->field($trip, 'Başlama tarixi')['scope']);
        $this->assertSame('order', $this->field($trip, 'Ezamiyyə yeri')['scope']);
        $this->assertSame('trip_type', $this->field($trip, 'Ezamiyyətin növü')['type']);
        $this->assertSame('1', $this->field($trip, 'Ezamiyyətin növü')['default']);
        $this->assertFalse($this->field($trip, 'Maliyyələşmə mənbəyi')['required']);
        $this->assertContains('participant.full_name', array_column($trip->variables, 'auto_key'));
        $this->assertNotSame([], $trip->participantRowTokens());

        $this->assertFalse($this->template('emek_mezuniyyeti')->isMultiParticipant());
    }

    public function test_a_three_person_trip_is_issued_approved_and_reverted_as_one_order(): void
    {
        [$a, $b, $c] = [$this->makePersonnel('Əliyev'), $this->makePersonnel('Həsənova', 2), $this->makePersonnel('Quliyev')];
        $template = $this->template('ezamiyyet');

        $outcome = $this->issue($template, [
            ['personnel_id' => $a->id],
            // Bitmə tarixi overridden for one person only.
            ['personnel_id' => $b->id, 'fields' => [$this->token($template, 'Bitmə tarixi') => '2026-11-07', $this->token($template, 'İşə başlama tarixi') => '2026-11-08']],
            ['personnel_id' => $c->id],
        ]);
        $this->assertTrue($outcome->isSaved(), json_encode($outcome->errors, JSON_UNESCAPED_UNICODE).' '.$outcome->message);

        $order = OrderLog::query()->sole();
        $this->assertSame($a->id, (int) $order->template_snapshot['personnel_id'], 'The snapshot keeps the first participant.');
        $this->assertSame([$a->id, $b->id, $c->id], $order->participants()->pluck('personnel_id')->all());
        $this->assertEqualsCanonicalizing(
            [$a->tabel_no, $b->tabel_no, $c->tabel_no],
            DB::table('order_log_personnels')->where('order_no', $order->order_no)->pluck('tabel_no')->all(),
        );

        // The document repeats the participants row: header + 3 people.
        $xml = $this->documentXml((string) $order->fresh()->template_snapshot['docx_path']);
        $this->assertSame(4, preg_match_all('/<w:tr[\s>]/', $xml));
        foreach (['Əliyev Ruslan Bəxtiyar', 'Həsənova Ruslan Bəxtiyar', 'Quliyev Ruslan Bəxtiyar'] as $name) {
            $this->assertStringContainsString($name, $xml);
        }
        $this->assertStringContainsString('07.11.2026', $xml);
        $this->assertStringNotContainsString('${', $xml);

        app(OrderStatusTransitionService::class)->approve($order->fresh());

        $trips = PersonnelBusinessTrip::query()->orderBy('id')->get();
        $this->assertCount(3, $trips);
        $this->assertSame([$a->tabel_no, $b->tabel_no, $c->tabel_no], $trips->pluck('tabel_no')->all());
        $this->assertSame(['2026-11-05', '2026-11-07', '2026-11-05'], $trips->map(fn ($trip) => $trip->getRawOriginal('end_date'))->all());
        $this->assertSame(['foreign'], $trips->pluck('trip_type')->unique()->values()->all());
        $this->assertSame('dövlət büdcəsi hesabına', $trips->first()->funding_source);
        $this->assertSame(1, PersonnelBusinessTrip::query()->filter(['trip_type' => 'foreign', 'funding_source' => 'büdcə'])->distinct()->count('order_no'));
        $this->assertSame(0, PersonnelBusinessTrip::query()->filter(['trip_type' => 'domestic'])->count());

        // Every participant's card shows the order and their trip (kind and funding included).
        $timeline = app(Personnel360TimelineService::class);
        foreach ([$a, $b, $c] as $person) {
            $items = $timeline->build($person);
            $this->assertNotNull($items->firstWhere('type', 'order'), $person->surname.' sees the order in the feed.');
            $trip = (string) $items->firstWhere('type', 'business_trip')['summary'];
            $this->assertStringContainsString(__('personnel::portfolio.timeline_trip_types.foreign'), $trip);
            $this->assertStringContainsString('dövlət büdcəsi hesabına', $trip);
        }

        // One outbox event per participant, same payload shape plus the participant index.
        $approved = OutboxEvent::query()->orderBy('id')->get()->pluck('payload');
        $this->assertCount(3, $approved);
        $this->assertSame([1, 2, 3], $approved->pluck('participant_index')->all());
        $this->assertSame([(string) $a->id, (string) $b->id, (string) $c->id], $approved->pluck('employee_external_id')->all());
        $this->assertSame([(string) $order->id], $approved->pluck('order_external_id')->unique()->values()->all());
        $this->assertSame([$order->id.'-1', $order->id.'-2', $order->id.'-3'], $approved->pluck('external_id')->all());
        $this->assertSame('2026-11-07', $approved[1]['end_date']);
        $this->assertSame('approved', $approved[0]['status']);

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Səhv tərtib edilib');

        $this->assertSame(0, PersonnelBusinessTrip::withTrashed()->count());
        $reversed = OutboxEvent::query()->orderBy('id')->skip(3)->take(10)->get()->pluck('payload');
        $this->assertCount(3, $reversed);
        $this->assertSame(['reversed'], $reversed->pluck('status')->unique()->values()->all());

        // Approving again works from a clean state.
        app(OrderStatusTransitionService::class)->approve($order->fresh());
        $this->assertSame(3, PersonnelBusinessTrip::query()->count());
    }

    public function test_an_automatic_number_at_approval_relinks_every_participant(): void
    {
        foreach ([
            OrderNumbering::SETTING_FORMAT => ['{il}/E-{N:3}', 'string'],
            OrderNumbering::SETTING_SCOPE => ['global', 'string'],
            OrderNumbering::SETTING_YEARLY_RESET => ['1', 'bool'],
            OrderNumbering::SETTING_TYPE_CODES => ['', 'string'],
        ] as $name => [$value, $type]) {
            Setting::query()->updateOrCreate(['name' => $name], ['value' => $value, 'type' => $type]);
        }

        [$a, $b] = [$this->makePersonnel('Əliyev'), $this->makePersonnel('Quliyev')];
        $this->assertTrue($this->issue($this->template('ezamiyyet'), [['personnel_id' => $a->id], ['personnel_id' => $b->id]], '')->isSaved());

        $order = OrderLog::query()->sole();
        $this->assertTrue(OrderNumbering::isProvisional($order->order_no));

        app(OrderStatusTransitionService::class)->approve($order);
        $number = (string) $order->fresh()->order_no;

        $this->assertFalse(OrderNumbering::isProvisional($number));
        $this->assertEqualsCanonicalizing([$a->tabel_no, $b->tabel_no], DB::table('order_log_personnels')->where('order_no', $number)->pluck('tabel_no')->all());
        $this->assertSame([$number], PersonnelBusinessTrip::query()->pluck('order_no')->unique()->values()->all());
    }

    public function test_one_overlapping_participant_refuses_the_whole_order_and_is_named(): void
    {
        [$a, $b, $c] = [$this->makePersonnel('Əliyev'), $this->makePersonnel('Məmmədov'), $this->makePersonnel('Quliyev')];
        $template = $this->template('ezamiyyet');
        $participants = [['personnel_id' => $a->id], ['personnel_id' => $b->id], ['personnel_id' => $c->id]];

        $this->assertTrue($this->issue($template, $participants)->isSaved());

        // Məmmədov goes on vacation over the same days after the draft was issued.
        $this->vacation($b);

        $second = $this->issue($template, $participants, '101-M');
        $this->assertFalse($second->isSaved());
        $this->assertStringContainsString('Məmmədov', (string) $second->message);
        $this->assertArrayHasKey('participants.1', $second->errors);

        try {
            app(OrderStatusTransitionService::class)->approve(OrderLog::query()->sole());
            $this->fail('An order with one unavailable participant must not be approved.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Məmmədov', $exception->getMessage());
            $this->assertStringContainsString('məzuniyyət qeydi var', $exception->getMessage());
        }

        // All or nothing: no one was sent.
        $this->assertSame(0, PersonnelBusinessTrip::query()->count());
        $this->assertSame(0, OutboxEvent::query()->count());
        $this->assertSame(10, (int) OrderLog::query()->sole()->status_id);
    }

    public function test_participants_are_required_and_unique(): void
    {
        $a = $this->makePersonnel('Əliyev');
        $template = $this->template('ezamiyyet');

        $none = app(OrderCompositionIssuer::class)->issue($template, $this->composition($template, [], personnelId: null), false);
        $this->assertSame(__('orders::order_composer.errors.participants_required'), $none->errors['participants']);

        $twice = $this->issue($template, [['personnel_id' => $a->id], ['personnel_id' => $a->id]]);
        $this->assertFalse($twice->isSaved());
        $this->assertStringContainsString('Əliyev', $twice->errors['participants']);
    }

    public function test_the_composer_collects_participants_and_their_own_values(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-orders', 'web'));
        $this->actingAs($user);

        [$a, $b] = [$this->makePersonnel('Əliyev'), $this->makePersonnel('Həsənova', 2)];
        $template = $this->template('ezamiyyet');
        $end = $this->token($template, 'Bitmə tarixi');

        $component = Livewire::test(OrderComposer::class, ['presetCode' => 'ezamiyyet', 'personnelId' => $a->id])
            ->assertSet('participantIds', [$a->id])
            ->assertSet('fields.'.$this->token($template, 'Ezamiyyətin növü'), '1')
            ->call('addParticipant', $b->id)
            ->call('addParticipant', $b->id)
            ->assertHasErrors('participants')
            ->assertSet('participantIds', [$a->id, $b->id])
            ->set('participantFields.'.$b->id.'.'.$end, '2026-11-07')
            ->set('orderNumber', '300-E')
            ->set('orderDate', '2026-10-08');

        foreach ($this->tripFields($template) as $token => $value) {
            $component->set('fields.'.$token, $value);
        }

        $component->call('issue')->assertHasNoErrors();

        $order = OrderLog::query()->sole();
        $this->assertSame([$a->id, $b->id], $order->participants()->pluck('personnel_id')->all());
        // The person's own end date also moved their return-to-work date (autofilled).
        $this->assertSame(
            [$end => '2026-11-07', $this->token($template, 'İşə başlama tarixi') => '2026-11-08'],
            OrderParticipant::query()->where('personnel_id', $b->id)->sole()->fields,
        );

        // Reopening the draft restores the list and the override; removing a person drops them.
        Livewire::test(OrderComposer::class, ['orderId' => $order->id])
            ->assertSet('participantIds', [$a->id, $b->id])
            ->assertSet('participantFields.'.$b->id.'.'.$end, '2026-11-07')
            ->call('removeParticipant', $a->id)
            ->assertSet('participantIds', [$b->id])
            ->call('issue');

        $this->assertSame([$b->id], $order->participants()->pluck('personnel_id')->all());
        $this->assertSame([$b->tabel_no], DB::table('order_log_personnels')->where('order_no', $order->order_no)->pluck('tabel_no')->all());
        $this->assertSame($b->id, (int) $order->fresh()->template_snapshot['personnel_id']);
    }

    public function test_a_single_person_order_is_unchanged(): void
    {
        $personnel = $this->makePersonnel('Əliyev');
        $template = $this->template('emek_mezuniyyeti');

        $fields = [];
        foreach (['İş ili' => '2026-01-01', 'Gün sayı' => '5', 'Başlama tarixi' => '2026-11-02', 'Bitmə tarixi' => '2026-11-06', 'İşə başlama tarixi' => '2026-11-07', 'Əsas mətni' => 'ərizə'] as $label => $value) {
            $fields[$this->token($template, $label)] = $value;
        }

        $outcome = app(OrderCompositionIssuer::class)->issue($template, new OrderComposition(
            $template->code, $personnel->id, null, null, null, $fields, '200-M', '08.10.2026', 'Bakı şəhəri',
        ), false);
        $this->assertTrue($outcome->isSaved(), json_encode($outcome->errors, JSON_UNESCAPED_UNICODE).' '.$outcome->message);

        $order = OrderLog::query()->sole();
        app(OrderStatusTransitionService::class)->approve($order);

        $this->assertSame(0, OrderParticipant::query()->count());
        $this->assertSame(1, PersonnelVacation::query()->count());
        $payload = OutboxEvent::query()->sole()->payload;
        $this->assertSame((string) $order->id, $payload['external_id']);
        $this->assertSame((string) $personnel->id, $payload['employee_external_id']);
        $this->assertArrayNotHasKey('participant_index', $payload);
        $this->assertArrayNotHasKey('order_external_id', $payload);
    }

    public function test_a_marked_block_repeats_per_participant(): void
    {
        $word = new PhpWord;
        $section = $word->addSection();
        $section->addText('Aşağıdakılar mükafatlandırılsın:');
        $section->addText('${participants}');
        $section->addText('${n}. ${name} — ${amount}');
        $section->addText('${/participants}');
        $section->addText('Cəmi: ${total}');
        $tmp = tempnam(sys_get_temp_dir(), 'blk').'.docx';
        $word->save($tmp, 'Word2007');
        Storage::disk('local')->put('order-templates/block.docx', (string) file_get_contents($tmp));
        @unlink($tmp);

        $out = app(DocxTemplateRenderer::class)->renderToFile('order-templates/block.docx', [
            'n' => '', 'name' => '', 'amount' => '100', 'total' => '300',
            DocxTemplateRenderer::PARTICIPANT_ROWS => [
                ['n' => '1', 'name' => 'Əliyev', 'amount' => '100'],
                ['n' => '2', 'name' => 'Həsənova', 'amount' => '100'],
                ['n' => '3', 'name' => 'Quliyev', 'amount' => '100'],
            ],
            DocxTemplateRenderer::PARTICIPANT_ANCHORS => ['n', 'name'],
        ]);

        $text = strip_tags($this->zipPart($out, 'word/document.xml'));
        File::delete($out);

        $this->assertStringContainsString('1. Əliyev — 100', $text);
        $this->assertStringContainsString('3. Quliyev — 100', $text);
        $this->assertStringContainsString('Cəmi: 300', $text);
        $this->assertStringNotContainsString('participants', $text);
    }

    public function test_the_designer_marks_a_template_multi_participant_with_field_scopes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('edit-orders', 'web'));
        $this->actingAs($user);

        $word = new PhpWord;
        $section = $word->addSection();
        $section->addText('[İştirakçılar]');
        $section->addText('[İştirakçının sıra №]. [İştirakçı – Tam ad (S.A.A.)] — [Məbləğ]');
        $section->addText('[/İştirakçılar]');
        $section->addText('Səbəb: [Səbəb]');
        $tmp = tempnam(sys_get_temp_dir(), 'dsg').'.docx';
        $word->save($tmp, 'Word2007');
        $upload = UploadedFile::fake()->createWithContent('team-award.docx', (string) file_get_contents($tmp));
        @unlink($tmp);

        $component = Livewire::test(OrderTemplateDesigner::class)
            ->set('code', 'komanda_mukafati')
            ->set('label', 'Komanda mükafatı')
            ->set('effect', 'award')
            ->set('upload', $upload)
            ->assertSet('multiParticipant', true)
            ->assertCount('variables', 4);

        $variables = $component->get('variables');
        $amount = array_search('Məbləğ', array_column($variables, 'label'), true);
        $this->assertSame('participant.n', $variables[0]['auto_key']);
        $this->assertSame('participant.full_name', $variables[1]['auto_key']);

        $component->set('variables.'.$amount.'.scope', 'participant')
            ->set('variables.'.$amount.'.effect_role', 'amount')
            ->call('save')
            ->assertHasNoErrors();

        $template = $this->template('komanda_mukafati');
        $this->assertTrue($template->isMultiParticipant());
        $this->assertSame('participant', $this->field($template, 'Məbləğ')['scope']);
        $this->assertSame('order', $this->field($template, 'Səbəb')['scope']);
        $this->assertStringContainsString('${participants}', $this->zipPart(Storage::disk('local')->path($template->docx_path), 'word/document.xml'));

        // A one-person effect cannot be multi-participant.
        $component->set('effect', 'transfer')->assertSet('multiParticipant', false)
            ->set('multiParticipant', true)->call('save')->assertHasErrors('multiParticipant');
    }

    public function test_resaving_the_business_trip_in_the_designer_keeps_its_participant_setup(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('edit-orders', 'web'));
        $this->actingAs($user);

        Livewire::test(OrderTemplateDesigner::class, ['code' => 'ezamiyyet'])
            ->assertSet('multiParticipant', true)
            ->call('save')
            ->assertHasNoErrors();

        $trip = $this->template('ezamiyyet');
        $this->assertTrue($trip->isMultiParticipant());
        $this->assertSame('override', $this->field($trip, 'Bitmə tarixi')['scope']);
        $this->assertFalse($this->field($trip, 'Ezamiyyətin növü')['required']);
        $this->assertSame('1', $this->field($trip, 'Ezamiyyətin növü')['default']);
        $this->assertSame('trip_type', collect($trip->variables)->firstWhere('label', 'Ezamiyyətin növü')['effect_role']);
    }

    public function test_the_trip_register_filters_and_shows_the_trip_kind(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('show-business_trips', 'web'));
        $this->actingAs($viewer);

        $this->assertSame(
            [['id' => 'domestic', 'label' => __('business_trips::common.trip_types.domestic')], ['id' => 'foreign', 'label' => __('business_trips::common.trip_types.foreign')]],
            Livewire::test(BusinessTrips::class)->instance()->tripTypeOptions(),
        );
        $this->assertSame('foreign', OrderLookupFieldRegistry::tripTypeCode('2'));
        $this->assertSame('domestic', OrderLookupFieldRegistry::tripTypeCode('domestic'));
        $this->assertNull(OrderLookupFieldRegistry::tripTypeCode('x'));
    }

    public function test_each_participant_keeps_their_own_effect_state_and_the_reversal_undoes_all(): void
    {
        $this->artisan('orders:seed-word-templates', ['--only' => 'intizam_tenbehi'])->assertSuccessful();
        $template = $this->template('intizam_tenbehi');
        $template->forceFill(['multi_participant' => true])->save();

        [$a, $b] = [$this->makePersonnel('Əliyev'), $this->makePersonnel('Quliyev')];
        $fields = [];
        foreach (['Pozuntunun təsviri' => 'Gecikmə', 'Tənbehin növü' => 'töhmət', 'Əsas mətni' => 'izahat'] as $label => $value) {
            $fields[$this->token($template, $label)] = $value;
        }

        $outcome = app(OrderCompositionIssuer::class)->issue($template, new OrderComposition(
            $template->code, null, null, null, null, $fields, '400-T', '08.10.2026', 'Bakı şəhəri',
            participants: [['personnel_id' => $a->id], ['personnel_id' => $b->id]],
        ), false);
        $this->assertTrue($outcome->isSaved(), json_encode($outcome->errors, JSON_UNESCAPED_UNICODE).' '.$outcome->message);

        $order = OrderLog::query()->sole();
        app(OrderStatusTransitionService::class)->approve($order);

        $records = PersonnelPunishment::query()->orderBy('id')->get();
        $this->assertSame([$a->tabel_no, $b->tabel_no], $records->pluck('tabel_no')->all());
        $this->assertSame(
            $records->pluck('id')->map(fn ($id) => ['punishment_record_id' => (int) $id])->all(),
            $order->participants()->get()->pluck('effect_state')->all(),
        );
        $this->assertArrayNotHasKey('effect_state', $order->fresh()->template_snapshot);

        app(OrderStatusTransitionService::class)->cancel($order->fresh(), 'Səhv tərtib edilib');

        $this->assertSame(0, PersonnelPunishment::query()->count());
        $this->assertSame([null, null], $order->participants()->get()->pluck('effect_state')->all());
    }

    public function test_the_migration_upgrades_an_unedited_business_trip_template_and_rekeys_its_orders(): void
    {
        $old = $this->installPreviousBusinessTripTemplate();
        $oldTokens = array_column(array_filter($old->variables, fn (array $v): bool => $v['source'] === 'manual'), 'token', 'label');

        $personnel = $this->makePersonnel('Əliyev');
        $draft = OrderLog::query()->create([
            'order_no' => '77-E', 'given_date' => now(), 'given_by' => 'HR', 'given_by_rank' => '', 'status_id' => 10,
            'template_render_mode' => 'docx_v1',
            'template_snapshot' => [
                'engine' => 'docx_v1', 'template_code' => 'ezamiyyet', 'label' => 'Ezamiyyət', 'personnel_id' => $personnel->id,
                'fields' => [$oldTokens['Ezamiyyə yeri'] => 'Gəncə şəhəri', $oldTokens['Başlama tarixi'] => '2026-11-02'],
                'order_date_text' => '08.10.2026', 'docx_path' => null,
            ],
        ]);

        $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_10_120000_make_standard_business_trip_template_multi_participant.php');
        $migration->up();

        $template = $this->template('ezamiyyet');
        $this->assertTrue($template->isMultiParticipant());
        $this->assertSame(1, $template->versions()->count(), 'The replaced master is archived.');
        $this->assertStringContainsString('${', $this->zipPart(Storage::disk('local')->path($template->docx_path), 'word/document.xml'));

        $fields = $draft->fresh()->template_snapshot['fields'];
        $this->assertSame('Gəncə şəhəri', $fields[$this->token($template, 'Ezamiyyə yeri')]);
        $this->assertSame('2026-11-02', $fields[$this->token($template, 'Başlama tarixi')]);

        // Idempotent.
        $migration->up();
        $this->assertSame(1, $this->template('ezamiyyet')->versions()->count());
    }

    public function test_an_edited_business_trip_template_is_left_alone(): void
    {
        $old = $this->installPreviousBusinessTripTemplate();
        $variables = $old->variables;
        foreach ($variables as $i => $variable) {
            if ($variable['label'] === 'Nəqliyyat') {
                $variables[$i]['effect_role'] = null; // reworked in the designer
            }
        }
        $old->forceFill(['variables' => $variables])->save();

        $result = app(\App\Modules\Orders\Infrastructure\Document\StandardBusinessTripTemplateUpgrader::class)->run();

        $this->assertSame(['ezamiyyet'], $result['edited']);
        $template = $this->template('ezamiyyet');
        $this->assertFalse($template->isMultiParticipant());
        $this->assertSame($variables, $template->variables);
        $this->assertSame(0, $template->versions()->count());
    }

    /**
     * Put the business-trip template on this install exactly as the earlier catalogue seeded
     * it (single employee, no participants table).
     */
    private function installPreviousBusinessTripTemplate(): OrderWordTemplate
    {
        OrderWordTemplate::query()->where('code', 'ezamiyyet')->delete();

        $upgrader = \App\Modules\Orders\Infrastructure\Document\StandardBusinessTripTemplateUpgrader::class;
        $auto = [
            'Təşkilatın adı' => 'system.organization_name', 'Əmrin nömrəsi' => 'system.order_number', 'Tarix' => 'system.order_date',
            'İş yeri' => 'employee.structure_genitive', 'Vəzifə' => 'employee.position', 'İşçi' => 'employee.full_name_with_suffix',
            'İmzalayan' => 'system.signatory_full_name', 'İmzalayanın vəzifəsi' => 'system.signatory_title',
        ];
        $parser = app(\App\Modules\Orders\Application\Document\DocxPlaceholderParser::class);
        $built = app(\App\Modules\Orders\Application\Document\OrderTemplateDocxBuilder::class)->build($upgrader::PREVIOUS_SPEC + ['organization' => '[Təşkilatın adı]']);

        $variables = [];
        $labelToToken = [];
        foreach ($parser->extract($built) as $i => $label) {
            $token = 'var_'.($i + 1);
            $labelToToken[$label] = $token;
            $role = $upgrader::PREVIOUS_MANUAL[$label] ?? null;
            $variables[] = isset($auto[$label])
                ? ['token' => $token, 'label' => $label, 'source' => 'auto', 'auto_key' => $auto[$label], 'field' => null, 'effect_role' => null]
                : ['token' => $token, 'label' => $label, 'source' => 'manual', 'auto_key' => null,
                    'field' => ['key' => $token, 'type' => str_ends_with((string) $role, 'date') ? 'date' : 'text'] + ($label === 'Ezamiyyə xərcləri (gündəlik)' ? ['required' => false] : []),
                    'effect_role' => $role];
        }

        Storage::disk('local')->makeDirectory('order-templates');
        $parser->normalize($built, $labelToToken, Storage::disk('local')->path('order-templates/ezamiyyet.docx'));
        @unlink($built);

        return OrderWordTemplate::query()->create([
            'code' => 'ezamiyyet', 'label' => 'Ezamiyyət', 'effect' => 'business_trip',
            'docx_path' => 'order-templates/ezamiyyet.docx', 'variables' => $variables, 'is_active' => true,
        ]);
    }

    /**
     * @param  list<array{personnel_id:int,fields?:array<string,mixed>}>  $participants
     */
    private function issue(OrderWordTemplate $template, array $participants, string $number = '100-M')
    {
        return app(OrderCompositionIssuer::class)->issue($template, $this->composition($template, $participants, $number), false);
    }

    /**
     * @param  list<array{personnel_id:int,fields?:array<string,mixed>}>  $participants
     */
    private function composition(OrderWordTemplate $template, array $participants, string $number = '100-M', ?int $personnelId = null): OrderComposition
    {
        return new OrderComposition(
            $template->code, $personnelId, null, null, null, $this->tripFields($template), $number, '08.10.2026', 'Bakı şəhəri',
            participants: $participants,
        );
    }

    /**
     * @return array<string,string>
     */
    private function tripFields(OrderWordTemplate $template): array
    {
        $fields = [];
        foreach ([
            'Ezamiyyətin məqsədi' => 'təlimdə iştirak',
            'Başlama tarixi' => '2026-11-02',
            'Bitmə tarixi' => '2026-11-05',
            'Ezamiyyə yeri' => 'Tbilisi şəhəri',
            'Ezamiyyətin növü' => (string) OrderLookupFieldRegistry::TRIP_TYPE_FOREIGN,
            'Nəqliyyat' => 'avtobus',
            'İşə başlama tarixi' => '2026-11-06',
            'Maliyyələşmə mənbəyi' => 'dövlət büdcəsi hesabına',
            'Əsas mətni' => 'xidməti qeyd',
        ] as $label => $value) {
            $fields[$this->token($template, $label)] = $value;
        }

        return $fields;
    }

    private function vacation(Personnel $personnel): void
    {
        PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no, 'vacation_places' => '', 'duration' => 3,
            'start_date' => '2026-11-04', 'end_date' => '2026-11-06', 'return_work_date' => '2026-11-07',
            'order_given_by' => 'HR', 'vacation_days_total' => 0, 'remaining_days' => 0,
        ]);
    }

    private function documentXml(string $path): string
    {
        return $this->zipPart(Storage::disk('local')->path($path), 'word/document.xml');
    }

    private function zipPart(string $absolutePath, string $part): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($absolutePath) === true);
        $xml = (string) $zip->getFromName($part);
        $zip->close();

        return $xml;
    }

    /**
     * @return array{key:string,label:string,type:string,required:bool,default:mixed,scope:string}
     */
    private function field(OrderWordTemplate $template, string $label): array
    {
        return collect($template->manualFields())->firstWhere('label', $label);
    }

    private function token(OrderWordTemplate $template, string $label): string
    {
        return (string) $this->field($template, $label)['key'];
    }

    private function template(string $code): OrderWordTemplate
    {
        return OrderWordTemplate::query()->where('code', $code)->firstOrFail();
    }

    private function makePersonnel(string $surname, int $gender = 1): Personnel
    {
        $structure = Structure::query()->firstOrCreate(['name' => 'Keşlə'], ['shortname' => 'K']);
        $position = Position::query()->firstOrCreate(['name' => 'operator']);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname,
            'name' => 'Ruslan',
            'patronymic' => 'Bəxtiyar',
            'birthdate' => '1990-01-01',
            'gender' => $gender,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'work_norm_id' => 1,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
