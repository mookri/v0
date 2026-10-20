<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\CustomerActivity;
use App\Platform\Api\CursorPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Account activity feed (B6): cursor-paginated reads over the
 * customer-safe activity read model (never kernel audit rows — those
 * are staff-only). Optional ?kind= narrows within the closed kind set;
 * unknown kinds are ignored, not errors.
 */
final class CustomerActivityController extends CustomerSelfServiceController
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $query = CustomerActivity::query()->where('user_id', $user->getAuthIdentifier());

        $kind = $request->query('kind');
        if (is_string($kind) && in_array($kind, CustomerActivity::KINDS, true)) {
            $query->where('kind', $kind);
        }

        [$activities, $page] = CursorPagination::paginate($query, $request);

        return new JsonResponse([
            'data' => array_map(
                static fn (CustomerActivity $activity): array => [
                    'id' => (string) $activity->getKey(),
                    'object' => 'customer_activity',
                    'kind' => $activity->kind,
                    'label' => $activity->label,
                    'at' => $activity->created_at,
                ],
                $activities
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }
}
