<?php

namespace App\View\Components;

use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * API context meta tags for admin-shell pages (doc 06 §8.4): page
 * scripts fetch module JSON same-origin with the web session cookie and
 * scope it via X-Tenant-Id. Resolves the shell user's default (first)
 * active membership server-side so templates carry no lookup logic and
 * fail closed to an empty context without one.
 */
final class ApiContext extends Component
{
    /**
     * @var list<array{id: string, name: string}>
     */
    public readonly array $memberships;

    public readonly string $defaultTenantId;

    public function __construct()
    {
        $memberships = [];

        $userId = Auth::id();

        if ($userId !== null) {
            $rows = TenantMembership::query()
                ->where('user_id', $userId)
                ->where('state', TenantMembership::STATE_ACTIVE)
                ->whereHas('tenant', static fn ($query) => $query->where('state', Tenant::STATE_ACTIVE))
                ->with('tenant:id,name')
                ->orderBy('created_at')
                ->get();

            foreach ($rows as $row) {
                if ($row->tenant !== null) {
                    $memberships[] = [
                        'id' => (string) $row->tenant_id,
                        'name' => (string) $row->tenant->name,
                    ];
                }
            }
        }

        $this->memberships = $memberships;
        $this->defaultTenantId = $memberships[0]['id'] ?? '';
    }

    public function render(): View
    {
        return view('components.api-context');
    }
}
