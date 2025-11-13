<?php

namespace Tests\Unit\Services;

use App\Services\CalculateSeniorityService;
use App\Services\EducationDurationService;
use Mockery;
use Tests\TestCase;

class EducationDurationServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_empty_array_when_admission_year_missing(): void
    {
        $mock = Mockery::mock(CalculateSeniorityService::class);
        $mock->shouldNotReceive('calculateEducation');

        $service = new EducationDurationService($mock);

        $this->assertSame([], $service->education([]));
    }

    public function test_it_memoizes_education_calculations(): void
    {
        $payload = [
            'admission_year' => '2020-01-01',
            'graduated_year' => '2022-01-01',
            'coefficient' => 1,
        ];

        $mock = Mockery::mock(CalculateSeniorityService::class);
        $mock->shouldReceive('calculateEducation')
            ->once()
            ->with($payload)
            ->andReturn(['diff' => 24]);

        $service = new EducationDurationService($mock);

        $firstCall = $service->education($payload);
        $secondCall = $service->education($payload);

        $this->assertSame($firstCall, $secondCall);
    }

    public function test_it_memoizes_extra_education_sets(): void
    {
        $payload = [
            [
                'admission_year' => '2015-01-01',
                'graduated_year' => '2017-01-01',
                'coefficient' => 1,
            ],
        ];

        $mock = Mockery::mock(CalculateSeniorityService::class);
        $mock->shouldReceive('calculateMultiEducation')
            ->once()
            ->with($payload)
            ->andReturn(['total_duration' => 24]);

        $service = new EducationDurationService($mock);

        $firstCall = $service->extraEducations($payload);
        $secondCall = $service->extraEducations($payload);

        $this->assertSame($firstCall, $secondCall);
    }
}
