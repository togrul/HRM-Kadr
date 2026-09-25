<?php

namespace Tests\Unit\Support;

use App\Support\Permissions\PermissionDescriptionCatalog;
use Tests\TestCase;

class PermissionDescriptionCatalogTest extends TestCase
{
    public function test_stored_descriptions_stay_azerbaijani_whatever_the_request_locale(): void
    {
        app()->setLocale('en');

        $this->assertSame('Əmək haqqı run-larını kilidləmək və yenidən açmaq icazəsi verir.', PermissionDescriptionCatalog::describe('lock-payroll'));
        $this->assertSame('Allows locking and reopening payroll runs.', PermissionDescriptionCatalog::label('lock-payroll'));
    }

    public function test_every_permission_is_described_in_every_language(): void
    {
        $az = PermissionDescriptionCatalog::all('az');

        $this->assertCount(111, $az);
        $this->assertSame(array_keys($az), array_keys(PermissionDescriptionCatalog::all('en')));
        // Dotted permission names are map keys, not nested lookups.
        $this->assertArrayHasKey('candidate-applications.create', $az);

        app()->setLocale('en');
        $this->assertSame('This permission grants access to the related screens and functions.', PermissionDescriptionCatalog::label('unknown-permission'));
    }
}
