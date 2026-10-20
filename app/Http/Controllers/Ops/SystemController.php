<?php

namespace App\Http\Controllers\Ops;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only system health surface (W2): runs the same dependency checks as
 * the `/ready` probe and returns them as JSON for the console. Capability
 * `platform:ops:view` gates the read. No tenant is required — health is
 * platform-global.
 */
final class SystemController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $checks = [
            'central_database' => fn (): bool => $this->ping('central'),
            'tenant_database' => fn (): bool => $this->ping('tenant_shared'),
            'cache' => fn (): bool => $this->pingCache(),
            'queue' => fn (): bool => $this->pingQueue(),
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
            'data' => [
                'status' => $healthy ? 'ready' : 'degraded',
                'checks' => $results,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function ping(string $connection): bool
    {
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
        $connection = config('queue.default');

        return is_int(app('queue')->connection($connection)->size());
    }
}
