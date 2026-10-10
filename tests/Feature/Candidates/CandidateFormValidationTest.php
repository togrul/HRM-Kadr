<?php

namespace Tests\Feature\Candidates;

use App\Models\Candidate;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Livewire\AddCandidate;
use App\Modules\Candidates\Livewire\EditCandidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Server-side plausibility checks on the add/edit candidate form (audit 08.10.2026): a
 * future birthdate, a non-numeric phone and out-of-order recruitment dates used to save.
 */
class CandidateFormValidationTest extends TestCase
{
    use RefreshDatabase;

    /** The `*` the shared x-label renders for required fields (Blade block comments allowed). */
    private const MARKER = '(?:<!--\[if [A-Z]+\]><!\[endif\]-->)*<span aria-hidden="true" class="ml-0\.5 text-rose-600">\*</span>';

    private Structure $structure;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-08 10:00:00');
        config()->set('candidates.workflow_pack', 'public');

        DB::table('appeal_statuses')->insert(['id' => 10, 'name' => 'Baxılır', 'locale' => app()->getLocale()]);
        $this->structure = Structure::query()->create(['name' => 'Mərkəz', 'shortname' => 'MRK']);

        $user = User::factory()->create();
        grantAllStructures($user);
        $user->givePermissionTo([
            Permission::findOrCreate('add-candidates', 'web'),
            Permission::findOrCreate('edit-candidates', 'web'),
        ]);
        $this->actingAs($user);
    }

    public function test_a_valid_candidate_is_saved_with_a_normalised_phone(): void
    {
        $this->form(['phone' => '+994 (50) 123-45-67'])
            ->call('store')
            ->assertHasNoErrors();

        $candidate = Candidate::query()->firstOrFail();
        $this->assertSame('+994501234567', $candidate->phone);
        $this->assertSame('2026-01-10', $candidate->getRawOriginal('appeal_date'));
    }

    public function test_a_future_birthdate_is_rejected(): void
    {
        $this->form(['birthdate' => '2035-01-01'])
            ->call('store')
            ->assertHasErrors(['candidate.birthdate']);

        $this->assertSame(0, Candidate::query()->count());
        $this->assertStringContainsString('olmalıdır', $this->firstError('candidate.birthdate'));
    }

    public function test_the_age_must_be_between_15_and_100(): void
    {
        $this->form(['birthdate' => '2012-01-01'])->call('store')->assertHasErrors(['candidate.birthdate']);
        $this->form(['birthdate' => '1920-01-01'])->call('store')->assertHasErrors(['candidate.birthdate']);
        $this->form(['birthdate' => '2011-10-08'])->call('store')->assertHasNoErrors(['candidate.birthdate']);
    }

    public function test_a_phone_that_is_not_9_to_15_digits_is_rejected(): void
    {
        foreach (['abc', '12345', '+99450123456789012', '050-12a-45-67'] as $phone) {
            $this->form(['phone' => $phone])->call('store')->assertHasErrors(['candidate.phone']);
        }

        $this->form(['phone' => ''])->call('store')->assertHasNoErrors();
    }

    public function test_recruitment_dates_cannot_be_in_the_future_or_before_2000(): void
    {
        $this->form(['application_date' => '2026-10-09'])->call('store')->assertHasErrors(['candidate.application_date']);
        $this->form(['appeal_date' => '1999-12-31', 'requisition_date' => null])->call('store')->assertHasErrors(['candidate.appeal_date']);
        $this->form(['requisition_date' => '2027-01-01'])->call('store')->assertHasErrors(['candidate.requisition_date']);
    }

    public function test_recruitment_dates_must_follow_requisition_appeal_application_order(): void
    {
        // Ərizə (written application) before Müraciət (appeal) — out of order.
        $component = $this->form(['appeal_date' => '2026-03-01', 'application_date' => '2026-02-01'])->call('store');
        $component->assertHasErrors(['candidate.application_date']);
        $this->assertSame(
            __('candidates::common.validation.date_order', [
                'attribute' => __('candidates::common.labels.application_date'),
                'other' => mb_strtolower(__('candidates::common.labels.appeal_date')),
            ]),
            $this->firstError('candidate.application_date', $component)
        );

        // Müraciət before the Tələbnamə (requisition) it answers — out of order.
        $this->form(['requisition_date' => '2026-02-01', 'appeal_date' => '2026-01-15'])
            ->call('store')
            ->assertHasErrors(['candidate.appeal_date']);

        // Without an appeal date, the application is still checked against the requisition.
        $this->form(['requisition_date' => '2026-02-01', 'appeal_date' => null, 'application_date' => '2026-01-20'])
            ->call('store')
            ->assertHasErrors(['candidate.application_date']);

        // Same day is fine.
        $this->form(['requisition_date' => '2026-01-10', 'appeal_date' => '2026-01-10', 'application_date' => '2026-01-10'])
            ->call('store')
            ->assertHasNoErrors();
    }

    public function test_editing_applies_the_same_rules_and_loads_the_dates(): void
    {
        $candidate = Candidate::query()->create([
            'surname' => 'Məmmədov', 'name' => 'Orxan', 'patronymic' => 'Elşən',
            'structure_id' => $this->structure->id, 'status_id' => 10, 'height' => 0,
            'gender' => 1, 'birthdate' => '1990-05-05', 'appeal_date' => '2026-01-10',
            'application_date' => '2026-02-10',
        ]);

        $component = Livewire::test(EditCandidate::class, ['candidateModel' => $candidate->id])
            ->assertSet('candidate.appeal_date', '10.01.2026')
            ->assertSet('candidate.application_date', '10.02.2026');

        $component->set('candidate.phone', 'abc')->call('store')->assertHasErrors(['candidate.phone']);
        $component->set('candidate.phone', '0501234567')
            ->set('candidate.application_date', '01.01.2026')
            ->call('store')
            ->assertHasErrors(['candidate.application_date']);
    }

    public function test_required_fields_carry_the_required_marker(): void
    {
        $html = Livewire::test(AddCandidate::class)->html();

        foreach (['name', 'surname', 'patronymic', 'birthdate', 'gender'] as $field) {
            $this->assertMatchesRegularExpression(
                '#<label[^>]*for="candidate\.'.$field.'"[^>]*>\s*'.preg_quote(__('candidates::common.labels.'.$field), '#').self::MARKER.'#u',
                $html,
                "{$field} should be marked required."
            );
        }

        foreach (['candidate-structure-label' => 'structure', 'candidate-status-label' => 'status'] as $id => $label) {
            $this->assertMatchesRegularExpression(
                '#<label[^>]*id="'.$id.'"[^>]*>\s*'.preg_quote(__('candidates::common.labels.'.$label), '#').self::MARKER.'#u',
                $html
            );
        }

        // Optional fields stay unmarked.
        $this->assertDoesNotMatchRegularExpression('#for="candidate\.phone"[^>]*>\s*[^<]*'.self::MARKER.'#u', $html);
    }

    public function test_the_form_header_no_longer_shows_the_mode_badge(): void
    {
        Livewire::test(AddCandidate::class)
            ->assertDontSee(__('candidates::common.labels.mode').':')
            ->assertDontSee('Public');
    }

    public function test_the_recruitment_note_is_profile_neutral(): void
    {
        foreach (['az', 'en'] as $locale) {
            $note = mb_strtolower(__('candidates::recruitment.labels.transition_note', [], $locale));

            $this->assertStringNotContainsString('hərbi', $note);
            $this->assertStringNotContainsString('military', $note);
        }
    }

    /**
     * A filled, valid add-candidate form with $overrides applied.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function form(array $overrides = []): Testable
    {
        $component = Livewire::test(AddCandidate::class);

        $values = array_merge([
            'name' => 'Elçin',
            'surname' => 'Hüseynov',
            'patronymic' => 'Vüqar',
            'structure_id' => $this->structure->id,
            'status_id' => 10,
            'gender' => 1,
            'birthdate' => '1995-01-01',
            'phone' => '0501234567',
            'requisition_date' => '2026-01-05',
            'appeal_date' => '2026-01-10',
            'application_date' => '2026-01-12',
        ], $overrides);

        foreach ($values as $key => $value) {
            $component->set('candidate.'.$key, $value);
        }

        return $component;
    }

    private function firstError(string $key, ?Testable $component = null): string
    {
        $component ??= $this->form(['birthdate' => '2035-01-01'])->call('store');

        return (string) collect($component->errors()->get($key))->first();
    }
}
