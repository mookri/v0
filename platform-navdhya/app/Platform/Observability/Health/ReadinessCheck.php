<?php

namespace App\Platform\Observability\Health;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * `/ready` readiness probe (doc 05 §11.2, doc 06 §11).
 *
 * Checks (each degrades independently; any failure => 503, node leaves LB):
 *  - central DB ping
 *  - tenant shared DB ping
 *  - Redis/cache ping (production cache store)
 *  - queue connectivity probe
 *  - outbox relay heartbeat age < 120 s (M3 — stubbed until the outbox lands;
 *    the check is wired through a KernelHealth contract so M3 replaces the
 *    stub without touching this controller)
 *
 * `/up` (process alive, no dependencies) ships with the framework health
 * route in bootstrap/app.php.
 */
final class ReadinessCheck
{
    public function __invoke(Request $request): JsonResponse
    {
        $checks = [
            'central_database' => fn (): bool => $this->ping('central'),
            'tenant_database' => fn (): bool => $this->ping('tenant_shared'),
            'cache' => fn (): bool => $this->pingCache(),
            'queue' => fn (): bool => $this->pingQueue(),
            'outbox_relay' => fn (): bool => $this->outboxRelayFresh(),
        ];

        $results = [];
        $healthy = true;

        foreach ($checks as $name => $check) {
            try {
                $ok = (bool) $check();
            } catch (Throwable) {
                $ok = false;
            }

            $results[$name] = $ok ? 'ok' : 'failed';
            $healthy = $healthy && $ok;
        }

        return new JsonResponse([
            'status' => $healthy ? 'ready' : 'degraded',
            'checks' => $results,
        ], $healthy ? 200 : 503);
    }

    private function ping(string $connection): bool
    {
        // SQLite: SELECT 1 works; MySQL: same. Fail-closed on any exception.
        DB::connection($connection)->select('SELECT 1');

        return true;
    }

    private function pingCache(): bool
    {
        $store = cache()->store();
        $key = 'platform:ready:'.uniqid('', true);

        $store->put($key, 1, 5);
        $value = $store->get($key);
        $store->forget($key);

        return (int) $value === 1;
    }

    private function pingQueue(): bool
    {
        // M0 ships with QUEUE_CONNECTION=database; the size() probe verifies
        // the queue backend is reachable without enqueuing real work.
        $connection = config('queue.default');

        return is_int(app('queue')->connection($connection)->size());
    }

    /**
     * Outbox relay heartbeat (doc 05 §11.2): relay writes a heartbeat row;
     * stale > 120 s means the relay is down. Until M3 lands the outbox,
     * this check reports ok when the outbox feature is not yet installed
     * (default-safe branch, doc 05 §10.2 rule 1).
     */
    private function outboxRelayFresh(): bool
    {
        if (! app()->bound('platform.outbox_heartbeat')) {
            return true;
        }

        $lastBeat = app()->make('platform.outbox_heartbeat')->lastBeatAt();

        return $lastBeat !== null && $lastBeat->diffInSeconds(now()) < 120;
    }
}
