<?php

namespace Tests\Platform;

use App\Platform\Console\DoctorCommand;
use Tests\TestCase;

/**
 * platform:doctor (F2): every check reports ok/fail + detail, JSON form is
 * machine-readable, exit codes reflect health. Runs against the test env
 * (sqlite + non-redis cache), so redis-missing must NOT fail here.
 */
final class DoctorCommandTest extends TestCase
{
    public function test_checks_report_structured_results(): void
    {
        $command = new DoctorCommand();
        $checks = $command->runChecks();

        $this->assertNotEmpty($checks);
        foreach ($checks as $check) {
            $this->assertArrayHasKey('check', $check);
            $this->assertArrayHasKey('ok', $check);
            $this->assertArrayHasKey('detail', $check);
            $this->assertIsString($check['detail']);
        }

        $names = array_column($checks, 'check');
        foreach (['php.extensions', 'config.app_key', 'db.central', 'db.tenant_shared', 'cache.redis', 'storage.writable'] as $expected) {
            $this->assertContains($expected, $names);
        }
    }

    public function test_healthy_in_test_env(): void
    {
        $command = new DoctorCommand();
        $failed = array_filter($command->runChecks(), static fn ($c): bool => ! $c['ok']);

        $this->assertSame([], array_values($failed));
    }

    public function test_detects_missing_app_key(): void
    {
        config(['app.key' => '']);

        $command = new DoctorCommand();
        $byName = [];
        foreach ($command->runChecks() as $check) {
            $byName[$check['check']] = $check;
        }

        $this->assertFalse($byName['config.app_key']['ok']);
        $this->assertStringContainsString('APP_KEY', $byName['config.app_key']['detail']);
    }
}
