<?php

namespace App\Platform\Authz;

use App\Platform\Api\CodedHttpException;
use App\Platform\Audit\AuditWriter;
use App\Platform\Authz\Contracts\PolicyStatus;
use App\Platform\Authz\Events\PolicyCreated;
use App\Platform\Authz\Events\PolicyStatusChanged;
use App\Platform\Authz\Events\PolicyUpdated;
use App\Platform\Authz\Models\Policy;
use App\Platform\Tenancy\Exceptions\TenantContextMissingException;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;

/**
 * Policy lifecycle service (SEC-021, T-0K01-01.4): create / update /
 * status transitions for tenant policies on the admin surface.
 *
 * Every mutation writes a tenant-scoped audit row (DoD item 5) and emits
 * the matching platform.policy.* event with the full selector payload
 * (AC-T-0K01-01-3). Platform-default policies (tenant_id NULL, LD-15) are
 * read-only on tenant surfaces; name uniqueness is per-tenant so tenants
 * can override platform defaults by name without touching them.
 */
final class PolicyService
{
    public function __construct(
        private readonly PolicyValidator $validator,
        private readonly AuditWriter $audit,
        private readonly TenantContext $context,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Exceptions\InvalidPolicyPayloadException
     */
    public function create(array $input): Policy
    {
        $payload = $this->validator->validateForStore($input);

        if ($this->nameTaken($payload['name'])) {
            throw new CodedHttpException(
                409,
                'conflict',
                sprintf("A policy named '%s' already exists in this tenant.", $payload['name']),
            );
        }

        $policy = Policy::create([
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'effect' => $payload['effect'],
            'capability_selector' => $payload['capability_selector'],
            'scope_bindings' => $payload['scope_bindings'] ?? [],
            'conditions' => $payload['conditions'] ?? [],
            'priority' => $payload['priority'] ?? 0,
            'status' => PolicyStatus::Draft->value,
            'version' => 1,
        ]);

        $this->audit->record(
            action: 'policy.created',
            resource: [
                'module' => 'platform',
                'resource_type' => 'policy',
                'resource_id' => (string) $policy->getKey(),
            ],
            after: $policy->toPayload(),
        );

        event(new PolicyCreated($policy, $this->actorId()));

        return $policy;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws Exceptions\InvalidPolicyPayloadException
     */
    public function update(Policy $policy, array $input): Policy
    {
        $this->assertTenantMutable($policy);

        $payload = $this->validator->validateForUpdate($input);

        if (isset($payload['name'])
            && $payload['name'] !== $policy->name
            && $this->nameTaken($payload['name'], (string) $policy->getKey())) {
            throw new CodedHttpException(
                409,
                'conflict',
                sprintf("A policy named '%s' already exists in this tenant.", $payload['name']),
            );
        }

        $before = $policy->toPayload();

        $policy->fill($payload);
        $policy->version = (int) $policy->version + 1;
        $policy->save();

        $this->audit->record(
            action: 'policy.updated',
            resource: [
                'module' => 'platform',
                'resource_type' => 'policy',
                'resource_id' => (string) $policy->getKey(),
            ],
            before: $before,
            after: $policy->toPayload(),
        );

        event(new PolicyUpdated($policy, $this->actorId()));

        return $policy;
    }

    public function transitionStatus(Policy $policy, PolicyStatus $target): Policy
    {
        $this->assertTenantMutable($policy);

        $from = $policy->statusEnum();

        if (! $from->canTransitionTo($target)) {
            throw new CodedHttpException(
                409,
                'state_forbidden',
                sprintf('A policy cannot transition from %s to %s.', $from->value, $target->value),
            );
        }

        $before = $policy->toPayload();

        $policy->status = $target->value;
        $policy->save();

        $this->audit->record(
            action: 'policy.status_changed',
            resource: [
                'module' => 'platform',
                'resource_type' => 'policy',
                'resource_id' => (string) $policy->getKey(),
            ],
            before: $before,
            after: $policy->toPayload(),
        );

        event(new PolicyStatusChanged($policy, $from->value, $target->value, $this->actorId()));

        return $policy;
    }

    private function nameTaken(string $name, ?string $exceptPolicyId = null): bool
    {
        $tenantId = $this->context->tenantId();

        if (! $this->context->isResolved() || $tenantId === null) {
            throw new TenantContextMissingException('writing policy');
        }

        // Per-tenant uniqueness: platform-default rows (tenant_id NULL) do
        // not conflict — tenants override them by name (LD-15).
        $query = Policy::query()
            ->whereNotNull('tenant_id')
            ->where('name', $name);

        if ($exceptPolicyId !== null) {
            $query->whereKeyNot($exceptPolicyId);
        }

        return $query->exists();
    }

    private function assertTenantMutable(Policy $policy): void
    {
        if ($policy->isPlatformDefault()) {
            throw new CodedHttpException(
                403,
                'authorization_denied',
                'Platform default policies are read-only on tenant admin surfaces (LD-15).',
            );
        }
    }

    private function actorId(): ?string
    {
        $id = Auth::id();

        return $id === null ? null : (string) $id;
    }
}
