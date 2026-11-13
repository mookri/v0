<?php

namespace App\Modules\Temple;

use App\Domain\Verification\Projector\VerificationProjector;
use App\Modules\Temple\Contracts\ManagesTemples;
use App\Modules\Temple\Models\Temple;
use App\Platform\Audit\AuditWriter;

/**
 * Temple management service (deptrac-blessed ManagesTemples implementation).
 * Ambient tenant context scopes every call.
 */
final class TempleManagementService implements ManagesTemples
{
    use AuthorizesTempleManager;

    public function __construct(
        private readonly ?AuditWriter $audit = null,
        private readonly ?VerificationProjector $projector = null,
    ) {
    }

    private function auditWriter(): AuditWriter
    {
        return $this->audit ?? app(AuditWriter::class);
    }

    private function projector(): VerificationProjector
    {
        return $this->projector ?? app(VerificationProjector::class);
    }
    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   created_at: string
     * }
     */
    public function create(array $data): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        return (new \App\Modules\Temple\Models\Temple())->getConnection()->transaction(function () use ($tenantId, $data) {
            $temple = new \App\Modules\Temple\Models\Temple();
            $temple->forceFill([
                'tenant_id' => $tenantId,
                'slug' => $data['slug'],
                'name' => $data['name'],
                'trust_name' => $data['trust_name'],
                'type' => $data['type'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'images' => $data['images'] ?? [],
                'amenities' => $data['amenities'] ?? [],
                'description' => $data['description'] ?? null,
                'status' => \App\Modules\Temple\Models\Temple::STATUS_DRAFT,
                'verification_status' => \App\Modules\Temple\Models\Temple::VERIFICATION_PENDING,
                'owner_id' => (string) app(\App\Platform\Tenancy\TenantContext::class)->membershipId(),
            ])->save();

            // Create owner as manager
            \App\Modules\Temple\Models\TempleManager::create([
                'tenant_id' => $tenantId,
                'temple_id' => (string) $temple->getKey(),
                'user_id' => (string) app(\App\Platform\Tenancy\TenantContext::class)->membershipId(),
                'role' => \App\Modules\Temple\Models\TempleManager::ROLE_OWNER,
                'invited_at' => now()->toISOString(),
                'accepted_at' => now()->toISOString(),
            ]);

            return $this->toCreated($temple);
        });
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   updated_at: string
     * }
     */
    public function update(string $templeId, array $data): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        try {
            $temple = $this->findAuthorizedTemple($tenantId, $templeId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            throw new TempleNotFoundException('Temple not found or access denied.');
        }

        $allowed = [
            'name', 'trust_name', 'address', 'city', 'state',
            'lat', 'lng', 'images', 'amenities', 'description',
        ];

        $temple->forceFill(array_intersect_key($data, array_flip($allowed)))->save();

        return $this->toUpdated($temple);
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   updated_at: string
     * }
     */
    public function verify(string $templeId): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        return (new Temple())->getConnection()->transaction(function () use ($tenantId, $templeId): array {
            $temple = Temple::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $templeId)
                ->firstOrFail();

            if ($temple->verification_status === Temple::VERIFICATION_VERIFIED) {
                throw new \InvalidArgumentException('Temple is already verified.');
            }

            $before = [
                'verification_status' => $temple->verification_status,
                'status' => $temple->status,
            ];

            $actorId = (string) (app(\App\Platform\Tenancy\TenantContext::class)->membershipId() ?? 'system:admin');
            $claim = $this->projector()->grantManualClaimAndProject(
                subject: $temple,
                subjectType: 'temple',
                actorId: $actorId,
                metadata: ['source' => 'temple.admin.verify'],
            );

            $this->auditWriter()->record(
                'temple.profile.verified',
                ['module' => 'temple', 'resource_type' => 'temple', 'resource_id' => (string) $temple->getKey()],
                $before,
                ['verification_status' => $temple->verification_status, 'status' => $temple->status, 'claim_id' => (string) $claim->getKey()],
            );

            return $this->toUpdated($temple);
        });
    }

    public function publish(string $templeId): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        return (new Temple())->getConnection()->transaction(function () use ($tenantId, $templeId): array {
            $temple = Temple::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $templeId)
                ->firstOrFail();

            if ($temple->verification_status !== Temple::VERIFICATION_VERIFIED) {
                throw new \InvalidArgumentException('Temple must be verified before publishing.');
            }

            if ($temple->status === Temple::STATUS_ACTIVE) {
                throw new \InvalidArgumentException('Temple is already published.');
            }

            $before = ['status' => $temple->status];

            $temple->forceFill(['status' => Temple::STATUS_ACTIVE])->save();

            $this->auditWriter()->record(
                'temple.profile.published',
                ['module' => 'temple', 'resource_type' => 'temple', 'resource_id' => (string) $temple->getKey()],
                $before,
                ['status' => $temple->status],
            );

            return $this->toUpdated($temple);
        });
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   updated_at: string
     * }
     */
    public function suspend(string $templeId): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        return (new Temple())->getConnection()->transaction(function () use ($tenantId, $templeId): array {
            $temple = Temple::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $templeId)
                ->firstOrFail();

            if ($temple->status === Temple::STATUS_INACTIVE) {
                throw new \InvalidArgumentException('Temple is already suspended.');
            }

            $before = ['status' => $temple->status];
            $temple->forceFill(['status' => Temple::STATUS_INACTIVE])->save();

            $this->auditWriter()->record(
                'temple.profile.suspended',
                ['module' => 'temple', 'resource_type' => 'temple', 'resource_id' => (string) $temple->getKey()],
                $before,
                ['status' => $temple->status],
            );

            return $this->toUpdated($temple);
        });
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   updated_at: string
     * }
     */
    public function reactivate(string $templeId): array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        return (new Temple())->getConnection()->transaction(function () use ($tenantId, $templeId): array {
            $temple = Temple::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $templeId)
                ->firstOrFail();

            if ($temple->status === Temple::STATUS_ACTIVE) {
                throw new \InvalidArgumentException('Temple is already active.');
            }

            $before = ['status' => $temple->status];
            $temple->forceFill(['status' => Temple::STATUS_ACTIVE])->save();

            $this->auditWriter()->record(
                'temple.profile.reactivated',
                ['module' => 'temple', 'resource_type' => 'temple', 'resource_id' => (string) $temple->getKey()],
                $before,
                ['status' => $temple->status],
            );

            return $this->toUpdated($temple);
        });
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   address: string,
     *   city: string,
     *   state: string,
     *   lat: float|null,
     *   lng: float|null,
     *   images: list<string>,
     *   amenities: list<string>,
     *   description: string|null,
     *   status: string,
     *   verification_status: string,
     *   created_at: string,
     *   updated_at: string,
     *   managers: list<array{id: string, user_id: string, role: string}>
     * }|null
     */
    public function partnerProfile(string $templeId): ?array
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();

        try {
            $temple = $this->findAuthorizedTemple($tenantId, $templeId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return null;
        }

        $managers = \App\Modules\Temple\Models\TempleManager::query()
            ->where('tenant_id', (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId())
            ->where('temple_id', $templeId)
            ->get()
            ->map(fn ($m) => [
                'id' => (string) $m->getKey(),
                'user_id' => (string) $m->user_id,
                'role' => $m->role,
            ])->all();

        return [
            'id' => (string) $temple->getKey(),
            'slug' => $temple->slug,
            'name' => $temple->name,
            'trust_name' => $temple->trust_name,
            'type' => $temple->type,
            'address' => $temple->address,
            'city' => $temple->city,
            'state' => $temple->state,
            'lat' => $temple->lat,
            'lng' => $temple->lng,
            'images' => $temple->images ?? [],
            'amenities' => $temple->amenities ?? [],
            'description' => $temple->description,
            'status' => $temple->status,
            'verification_status' => $temple->verification_status,
            'created_at' => $temple->created_at?->toISOString(),
            'updated_at' => $temple->updated_at?->toISOString(),
            'managers' => $managers,
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   created_at: string
     * }
     */
    private function toCreated(\App\Modules\Temple\Models\Temple $t): array
    {
        return [
            'id' => (string) $t->getKey(),
            'slug' => $t->slug,
            'name' => $t->name,
            'trust_name' => $t->trust_name,
            'type' => $t->type,
            'city' => $t->city,
            'state' => $t->state,
            'status' => $t->status,
            'verification_status' => $t->verification_status,
            'created_at' => $t->created_at?->toISOString(),
        ];
    }

    /**
     * @return array{
     *   id: string,
     *   slug: string,
     *   name: string,
     *   trust_name: string,
     *   type: string,
     *   city: string,
     *   state: string,
     *   status: string,
     *   verification_status: string,
     *   updated_at: string
     * }
     */
    private function toUpdated(\App\Modules\Temple\Models\Temple $t): array
    {
        return [
            'id' => (string) $t->getKey(),
            'slug' => $t->slug,
            'name' => $t->name,
            'trust_name' => $t->trust_name,
            'type' => $t->type,
            'city' => $t->city,
            'state' => $t->state,
            'status' => $t->status,
            'verification_status' => $t->verification_status,
            'updated_at' => $t->updated_at?->toISOString(),
        ];
    }
}
