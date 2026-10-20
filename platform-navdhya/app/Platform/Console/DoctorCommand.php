<?php

namespace App\Platform\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan platform:doctor [--json]` — self-diagnosis for the platform
 * (F2). Fails fast on anything a deploy needs: PHP extensions, APP_KEY,
 * database reachability (central + tenant_shared), Redis reachability,
 * storage writability. Exit 0 = healthy, 1 = at least one check failed.
 * Every check reports ok/fail + detail; --json emits the machine-readable
 * form for pipelines and the future /ready parity check.
 */
final class DoctorCommand extends Command
{
    protected $signature = 'platform:doctor {--json : Emit machine-readable JSON}';

    protected $description = 'Check runtime, config, database, cache and storage health';

    /**
     * @return list<array{check: string, ok: bool, detail: string}>
     */
    public function runChecks(): array
    {
        $checks = [];
        $check = static function (string $name, callable $fn) use (&$checks): void {
            try {
                $detail = (string) $fn();
                $checks[] = ['check' => $name, 'ok' => true, 'detail' => $detail];
            } catch (\Throwable $e) {
                $checks[] = ['check' => $name, 'ok' => false, 'detail' => $e->getMessage()];
            }
        };

        $check('php.extensions', function (): string {
            $required = ['mbstring', 'pdo_mysql', 'sodium'];
            $missing = array_values(array_filter($required, static fn ($ext): bool => ! extension_loaded($ext)));
            if ($missing !== []) {
                throw new \RuntimeException('missing extensions: '.implode(',', $missing));
            }
            $optional = [];
            foreach (['redis', 'intl'] as $ext) {
                if (! extension_loaded($ext)) {
                    $optional[] = $ext;
                }
            }

            return 'required ok'.($optional === [] ? '' : '; missing optional: '.implode(',', $optional));
        });

        $check('config.app_key', function (): string {
            $key = (string) config('app.key', '');
            if ($key === '' || str_starts_with($key, 'base64:') === false && strlen($key) < 16) {
                throw new \RuntimeException('APP_KEY missing or too short');
            }

            return 'present';
        });

        foreach (['central', 'tenant_shared'] as $connection) {
            $check("db.{$connection}", static function () use ($connection): string {
                DB::connection($connection)->select('select 1 as ok');
                $driver = config("database.connections.{$connection}.driver");

                return "reachable (driver={$driver})";
            });
        }

        $check('cache.redis', function (): string {
            if (! extension_loaded('redis') && config('cache.default') === 'redis') {
                throw new \RuntimeException('cache=default redis but phpredis extension missing');
            }
            try {
                app('redis')->connection()->ping();
            } catch (\Throwable $e) {
                if (config('cache.default') === 'redis' || config('session.driver') === 'redis' || config('queue.default') === 'redis') {
                    throw new \RuntimeException('redis required by config but unreachable: '.$e->getMessage());
                }

                return 'not configured for use (cache/session/queue are non-redis)';
            }

            return 'pong';
        });

        $check('storage.writable', function (): string {
            $probe = storage_path('framework/doctor.probe');
            if (file_put_contents($probe, 'ok') === false) {
                throw new \RuntimeException('storage/framework not writable');
            }
            @unlink($probe);

            return storage_path();
        });

        return $checks;
    }

    public function handle(): int
    {
        $checks = $this->runChecks();
        $failed = array_filter($checks, static fn ($c): bool => ! $c['ok']);

        if ($this->option('json')) {
            $this->line((string) json_encode(['healthy' => $failed === [], 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $failed === [] ? self::SUCCESS : self::FAILURE;
        }

        $rows = [];
        foreach ($checks as $c) {
            $rows[] = [$c['ok'] ? '<info>ok</info>' : '<error>FAIL</error>', $c['check'], $c['detail']];
        }
        $this->table(['', 'check', 'detail'], $rows);

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
