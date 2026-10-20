<?php

namespace App\Platform\Authz;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * K-013 AuthorizationRequest: everything the SEC-022 chain needs to
 * decide one capability check — the principal, the capability code, an
 * optional resource reference and Working Context (opaque until
 * T-0K02-02), and the bounded condition attributes (amount, currency,
 * org, time, status).
 *
 * membershipId/principalId may be set explicitly for explain paths that
 * evaluate another principal than the ambient context's; both default
 * to the resolved TenantContext at evaluation time.
 */
final class AuthorizationRequest
{
    public const KIND_USER = 'user';

    public const KIND_AGENT = 'agent';

    public const KIND_SERVICE = 'service';

    public const KIND_PARTNER = 'partner';

    /**
     * @param  array<string, mixed>|null  $resource
     * @param  array<string, mixed>|null  $workingContext
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly ?Authenticatable $principal,
        public readonly string $capability,
        public readonly string $principalKind = self::KIND_USER,
        public readonly ?string $membershipId = null,
        public readonly ?string $principalId = null,
        public readonly ?array $resource = null,
        public readonly ?array $workingContext = null,
        public readonly array $attributes = [],
    ) {
    }
}
