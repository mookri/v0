<?php

namespace App\Modules\Search\Http\Controllers;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\BuyerProfile;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Services\SearchNormalizer;
use App\Modules\Vendor\Models\Vendor;
use App\Modules\Vendor\Models\VendorOrder;
use App\Platform\Audit\AuditWriter;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrivilegedSearchController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PolicyEvaluationEngine $engine,
        private readonly AuditWriter $audit,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $traceId = (string) ($request->headers->get('X-Request-Id') ?: Str::ulid());

        $tenantId = $this->tenantContext->tenantId();
        if ($tenantId === null) {
            return response()->json([
                'error' => ['code' => 'tenant_context_missing', 'message' => 'Tenant context not resolved.', 'request_id' => $traceId],
            ], 403);
        }

        $type = trim((string) $request->query('type', 'customer'));
        $q = trim((string) $request->query('q', ''));
        $limit = min(max((int) $request->query('limit', 20), 1), 50);

        if ($q === '') {
            return response()->json(['data' => ['results' => [], 'took_ms' => 0, 'search_version' => null]]);
        }

        $canSeeFullPii = $this->canSeeFullPii($request);

        $start = microtime(true);
        $results = match ($type) {
            'customer' => $this->searchCustomers($tenantId, $q, $limit, $canSeeFullPii),
            'booking' => $this->searchBookings($tenantId, $q, $limit),
            'vendor' => $this->searchVendors($tenantId, $q, $limit),
            'pandit' => $this->searchPandits($tenantId, $q, $limit),
            'order' => $this->searchOrders($tenantId, $q, $limit),
            default => [],
        };

        $tookMs = (int) ((microtime(true) - $start) * 1000);

        $this->audit->record(
            action: 'search.privileged',
            resource: ['module' => 'search', 'resource_type' => $type, 'resource_id' => 'privileged'],
            after: [
                'type' => $type,
                'q_hash' => hash('sha256', Str::lower($q)),
                'result_count' => count($results),
                'trace_id' => $traceId,
                'pii_full' => $canSeeFullPii,
            ],
        );

        $resp = response()->json([
            'data' => [
                'results' => array_values($results),
                'took_ms' => $tookMs,
                'search_version' => null,
            ],
        ]);
        $resp->headers->set('X-Search-Trace', "tid={$traceId}; privileged; type={$type}; n=" . count($results));
        $resp->headers->set('X-Request-Id', $traceId);
        return $resp;
    }

    private function canSeeFullPii(Request $request): bool
    {
        try {
            $this->engine->ensure(new AuthorizationRequest(
                principal: $request->user(),
                capability: 'search:pii:view',
            ));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }
        $len = strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return substr($phone, 0, 3) . str_repeat('*', max(0, $len - 5)) . substr($phone, -2);
    }

    /** @return array<int,array<string,mixed>> */
    private function searchCustomers(string $tenantId, string $q, int $limit, bool $fullPii): array
    {
        $like = '%' . $q . '%';
        $profiles = BuyerProfile::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($qq) use ($like) {
                $qq->where('display_name', 'LIKE', $like);
            })
            ->limit($limit)
            ->get();

        $userIds = $profiles->pluck('user_id')->all();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $out = [];
        foreach ($profiles as $p) {
            $u = $users->get($p->user_id);
            $out[] = [
                'entity_type' => 'customer',
                'id' => (string) $p->id,
                'title' => $p->display_name ?: ('Customer ' . $p->id),
                'subtitle' => $p->status,
                'card' => [
                    'user_id' => $p->user_id,
                    'locale' => $p->locale,
                    'status' => $p->status,
                    'phone' => $fullPii ? ($u?->phone) : $this->maskPhone($u?->phone),
                    'email' => $fullPii ? ($u?->email) : $this->maskEmail($u?->email),
                ],
            ];
        }
        return $out;
    }

    private function maskEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***';
        }
        $local = $parts[0];
        $domain = $parts[1];
        $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        return $masked . '@' . $domain;
    }

    /** @return array<int,array<string,mixed>> */
    private function searchBookings(string $tenantId, string $q, int $limit): array
    {
        $like = '%' . $q . '%';
        $rows = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($qq) use ($like) {
                $qq->where('service_sku', 'LIKE', $like)
                    ->orWhere('status', 'LIKE', $like);
            })
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $b) {
            $out[] = [
                'entity_type' => 'booking',
                'id' => (string) $b->id,
                'title' => 'Booking ' . substr((string) $b->id, -8),
                'subtitle' => $b->status . ' · ' . $b->service_sku,
                'card' => [
                    'status' => $b->status,
                    'service_sku' => $b->service_sku,
                    'scheduled_at' => (string) $b->scheduled_at,
                    'total_minor' => $b->total_minor,
                    'currency' => $b->currency,
                    'user_id' => $b->user_id,
                ],
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private function searchVendors(string $tenantId, string $q, int $limit): array
    {
        $like = '%' . $q . '%';
        $rows = Vendor::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($qq) use ($like) {
                $qq->where('name', 'LIKE', $like)
                    ->orWhere('city', 'LIKE', $like)
                    ->orWhere('description', 'LIKE', $like);
            })
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $v) {
            $out[] = [
                'entity_type' => 'vendor',
                'id' => (string) $v->id,
                'title' => $v->name,
                'subtitle' => $v->city,
                'card' => [
                    'city' => $v->city,
                    'status' => $v->status ?? null,
                ],
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private function searchPandits(string $tenantId, string $q, int $limit): array
    {
        $like = '%' . $q . '%';
        $rows = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($qq) use ($like) {
                $qq->where('display_name', 'LIKE', $like)
                    ->orWhere('bio', 'LIKE', $like);
            })
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $p) {
            $out[] = [
                'entity_type' => 'pandit',
                'id' => (string) $p->id,
                'title' => $p->display_name,
                'subtitle' => $p->verification_status ?? null,
                'card' => [
                    'status' => $p->status,
                    'verification_status' => $p->verification_status ?? null,
                    'rating' => $p->rating_avg ?? null,
                ],
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private function searchOrders(string $tenantId, string $q, int $limit): array
    {
        $like = '%' . $q . '%';
        $rows = VendorOrder::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($qq) use ($like) {
                $qq->where('status', 'LIKE', $like);
            })
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $o) {
            $out[] = [
                'entity_type' => 'order',
                'id' => (string) $o->id,
                'title' => 'Order ' . substr((string) $o->id, -8),
                'subtitle' => $o->status,
                'card' => [
                    'status' => $o->status,
                    'total_minor' => $o->total_minor ?? null,
                ],
            ];
        }
        return $out;
    }
}