<?php

namespace App\Platform\Authz\Http\Middleware;

use App\Platform\Audit\AuditWriter;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\Exceptions\AuthorizationDeniedException;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware enforcing a capability requirement (doc 04 §5, SEC-022):
 * `->middleware('capability:platform:policy:configure')`. Every route
 * capability check passes through the full precedence chain (explicit
 * deny, security constraints, policy constraints, allow grant, default
 * deny) via the PolicyEvaluationEngine (T-0K01-02).
 *
 * Laravel splits middleware parameters on `:`, so the capability code's
 * segments arrive as separate arguments and are re-joined here. Every
 * denial writes an `authz.denied` audit record (DoD item 4, negative AC
 * paths) before rethrowing; the error envelope renders the stable code
 * `authorization_denied`.
 */
final class CheckCapability
{
    public function __construct(
        private readonly PolicyEvaluationEngine $engine,
        private readonly AuditWriter $audit,
        private readonly TenantContext $context,
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$segments): Response
    {
        $capability = implode(':', $segments);

        try {
            $this->engine->ensure(new AuthorizationRequest(
                principal: $request->user(),
                capability: $capability,
            ));
        } catch (AuthorizationDeniedException $exception) {
            $this->recordDenial($capability, $exception->reason);

            throw $exception;
        }

        return $next($request);
    }

    private function recordDenial(string $capability, string $reason): void
    {
        if (! $this->context->isResolved()) {
            // No tenant context -> nowhere tenant-scoped to record the
            // denial; the request is denied regardless (fail-closed).
            return;
        }

        try {
            $this->audit->record(
                action: 'authz.denied',
                resource: ['module' => 'platform', 'resource_type' => 'capability', 'resource_id' => $capability],
                after: ['capability' => $capability, 'reason' => $reason],
            );
        } catch (\Throwable $e) {
            // The denial is the contract; the audit row is evidence.
            // A failing audit write (tenant database down, table not
            // provisioned yet) used to replace the 403 with a 500 and
            // destroy the very signal the alerting keys on.
            report($e);
        }
    }
}
