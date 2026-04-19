<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionDebugConfigurationTest extends TestCase
{
    public function test_debugbar_stays_disabled_for_production_environment(): void
    {
        $originalEnv = $_ENV['APP_ENV'] ?? null;
        $originalServerEnv = $_SERVER['APP_ENV'] ?? null;
        $originalDebugbar = $_ENV['DEBUGBAR_ENABLED'] ?? null;
        $originalServerDebugbar = $_SERVER['DEBUGBAR_ENABLED'] ?? null;
        $originalStorage = $_ENV['DEBUGBAR_STORAGE_ENABLED'] ?? null;
        $originalServerStorage = $_SERVER['DEBUGBAR_STORAGE_ENABLED'] ?? null;

        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
        $_ENV['DEBUGBAR_ENABLED'] = 'true';
        $_SERVER['DEBUGBAR_ENABLED'] = 'true';
        $_ENV['DEBUGBAR_STORAGE_ENABLED'] = 'true';
        $_SERVER['DEBUGBAR_STORAGE_ENABLED'] = 'true';

        try {
            $config = require base_path('config/debugbar.php');

            $this->assertFalse($config['enabled']);
            $this->assertFalse($config['storage']['enabled']);
            $this->assertFalse($config['storage']['open']);
        } finally {
            $this->restoreEnvValue('APP_ENV', $originalEnv, $originalServerEnv);
            $this->restoreEnvValue('DEBUGBAR_ENABLED', $originalDebugbar, $originalServerDebugbar);
            $this->restoreEnvValue('DEBUGBAR_STORAGE_ENABLED', $originalStorage, $originalServerStorage);
        }
    }

    public function test_env_example_defaults_to_safe_debug_settings(): void
    {
        $envExample = File::get(base_path('.env.example'));

        $this->assertStringContainsString('APP_DEBUG=false', $envExample);
        $this->assertStringContainsString('DEBUGBAR_ENABLED=false', $envExample);
        $this->assertStringContainsString('DEBUGBAR_STORAGE_ENABLED=false', $envExample);
    }

    private function restoreEnvValue(string $key, ?string $envValue, ?string $serverValue): void
    {
        if ($envValue === null) {
            unset($_ENV[$key]);
        } else {
            $_ENV[$key] = $envValue;
        }

        if ($serverValue === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $serverValue;
        }
    }
}
