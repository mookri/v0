<?php

namespace App\Platform\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Performance instrumentation (drill-only, PERF_TIMING-gated).
 *
 * Emits exactly one structured line per request so the perf harness can
 * join client-observed latency to server-side cost by request id:
 *
 *   [perf] rid=req_… method=POST route=api/v1/customers/auth/otp status=202 total_ms=45.2 sql_ms=12.3 queries=9
 *
 * - total_ms — wall time of the whole pipeline (hrtime), measured from
 *   this middleware (prepended globally, so outermost) to the response.
 * - sql_ms / queries — QueryExecuted events across every connection that
 *   shares the app event dispatcher (central + tenant: all connections
 *   resolve through DatabaseManager, which wires that dispatcher in).
 * - route — the matched route PATTERN (`route()->uri()`), so path
 *   parameters never reach the log; `$request->path()` is the fallback
 *   only when no route matched (404). Bodies, headers and query strings
 *   are never logged: no identities, codes or tokens — the request id is
 *   the correlation key (doc 05 §11.1).
 *
 * PERF_TIMING off (unit tests, normal runs) → a single boolean check and
 * pass-through: no clock, no listener, no log, identical behaviour. The
 * DB::listen registration lives for the duration of the request only
 * (the standard PHP lifecycle tears the dispatcher down afterwards);
 * this is a drill flag, not a production switch.
 */
final class RequestTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        // Read from config, never from env(): under `config:cache` a
        // runtime env() lookup returns null and the instrumentation
        // would silently switch off in production.
        if (! (bool) config('observability.perf_timing', false)) {
            return $next($request);
        }

        $start = hrtime(true);
        $queries = 0;
        $sqlMs = 0.0;
        DB::listen(static function (QueryExecuted $query) use (&$queries, &$sqlMs): void {
            $queries++;
            $sqlMs += (float) $query->time;
        });

        $status = 0;
        $error = '';
        try {
            $response = $next($request);
            $status = $response->getStatusCode();

            return $response;
        } catch (Throwable $e) {
            // The kernel renders the envelope after this middleware
            // unwinds, so the final HTTP status is unknown here; the
            // harness records the client-observed status and joins on
            // rid — the exception class is the server-side signal.
            $error = class_basename($e);

            throw $e;
        } finally {
            try {
                $totalMs = (hrtime(true) - $start) / 1_000_000;
                $route = $request->route()?->uri() ?? $request->path();

                Log::info(sprintf(
                    '[perf] rid=%s method=%s route=%s status=%d total_ms=%.1f sql_ms=%.1f queries=%d%s',
                    (string) $request->attributes->get('request_id', '-'),
                    $request->getMethod(),
                    $route,
                    $status,
                    $totalMs,
                    $sqlMs,
                    $queries,
                    $error === '' ? '' : " error={$error}",
                ));
            } catch (Throwable) {
                // Instrumentation must never break the request.
            }
        }
    }
}
