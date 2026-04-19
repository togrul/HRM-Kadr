<?php

namespace Tests\Unit\Architecture;

use App\Modules\Candidates\Application\Services\CandidateProfileFieldSchemaService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class RuntimeLocalizationTest extends TestCase
{
    public function test_candidate_validation_attribute_labels_resolve_for_all_supported_locales(): void
    {
        $service = app(CandidateProfileFieldSchemaService::class);
        $originalLocale = app()->getLocale();
        $locales = (array) config('app.locales', [$originalLocale]);

        foreach ($locales as $locale) {
            app()->setLocale($locale);

            foreach (['private', 'public', 'military'] as $pack) {
                foreach ($service->validationAttributeLabels($pack) as $attribute => $label) {
                    $field = Str::after($attribute, 'candidate.');

                    $this->assertStringNotContainsString('::', $label, "{$locale}: {$attribute} label did not resolve.");
                    $this->assertNotSame("candidates::common.labels.{$field}", $label, "{$locale}: {$attribute} label leaked translation key.");
                }
            }
        }

        app()->setLocale($originalLocale);
    }

    public function test_candidate_validation_errors_use_resolved_attribute_labels(): void
    {
        $service = app(CandidateProfileFieldSchemaService::class);
        $originalLocale = app()->getLocale();

        app()->setLocale('az');

        $labels = $service->validationAttributeLabels('military');
        $validator = Validator::make(
            ['candidate' => []],
            ['candidate.structure_id' => ['required']],
            [],
            $labels
        );

        $this->assertTrue($validator->fails());
        $message = $validator->errors()->first('candidate.structure_id');

        $this->assertStringContainsString($labels['candidate.structure_id'], $message);
        $this->assertStringNotContainsString('candidates::', $message);
        $this->assertStringNotContainsString('structure_id', $message);

        app()->setLocale($originalLocale);
    }
}
