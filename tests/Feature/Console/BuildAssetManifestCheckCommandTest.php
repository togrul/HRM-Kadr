<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class BuildAssetManifestCheckCommandTest extends TestCase
{
    public function test_vite_manifest_references_existing_compiled_assets(): void
    {
        $this->artisan('assets:manifest-check', ['--json' => true])
            ->assertExitCode(0);
    }
}
