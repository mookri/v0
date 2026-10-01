<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Models\CustomerAddress;
use App\Modules\Customers\CustomerActivityRecorder;
use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer address book (B4): cursor list, capped creates (20/customer),
 * updates, deletes with default promotion, and explicit default swaps.
 * All rows are caller-scoped (user_id + tenant scope); foreign ids 404.
 */
final class CustomerAddressController extends CustomerSelfServiceController
{
    public function __construct(private readonly AuditWriter $audit, private readonly CustomerActivityRecorder $activity)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        [$addresses, $page] = CursorPagination::paginate(
            CustomerAddress::query()->where('user_id', $user->getAuthIdentifier()),
            $request
        );

        return new JsonResponse([
            'data' => array_map(
                static fn (CustomerAddress $address): array => self::serialize($address),
                $addresses
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:64'],
            'line1' => ['required', 'string', 'max:190'],
            'line2' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:24'],
            'geo' => ['nullable', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if (CustomerAddress::query()->where('user_id', $userId)->count() >= CustomerAddress::MAX_PER_CUSTOMER) {
            return new JsonResponse([
                'error' => ['code' => 'conflict', 'message' => 'Address book is full.', 'request_id' => $this->requestId($request)],
            ], 409);
        }

        $isDefault = (bool) ($validated['is_default'] ?? false);
        if ($isDefault) {
            $this->clearDefaults($userId);
        }

        $address = new CustomerAddress();
        $address->forceFill([
            'user_id' => $userId,
            'label' => $validated['label'],
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'geo' => $validated['geo'] ?? null,
            'is_default' => $isDefault,
        ]);
        $address->save();

        $this->audit->record(
            'customers.address.added',
            ['module' => 'customers', 'resource_type' => 'customer_address', 'resource_id' => (string) $address->getKey()],
            null,
            ['user_id' => $userId]
        );
        $this->activity->record($userId, 'address_added', "Address '{$validated['label']}' added");

        return new JsonResponse([
            'data' => self::serialize($address),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $address = $this->ownAddress($request, $userId, $id);
        if ($address instanceof JsonResponse) {
            return $address;
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:64'],
            'line1' => ['sometimes', 'string', 'max:190'],
            'line2' => ['sometimes', 'nullable', 'string', 'max:190'],
            'city' => ['sometimes', 'string', 'max:120'],
            'state' => ['sometimes', 'string', 'max:120'],
            'postal_code' => ['sometimes', 'string', 'max:24'],
            'geo' => ['sometimes', 'nullable', 'array'],
        ]);

        $address->forceFill($validated)->save();

        $this->audit->record(
            'customers.address.updated',
            ['module' => 'customers', 'resource_type' => 'customer_address', 'resource_id' => (string) $address->getKey()],
            null,
            ['user_id' => $userId, 'fields' => array_keys($validated)]
        );
        $this->activity->record($userId, 'address_updated', "Address '{$address->label}' updated");

        return new JsonResponse([
            'data' => self::serialize($address->fresh()),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $address = $this->ownAddress($request, $userId, $id);
        if ($address instanceof JsonResponse) {
            return $address;
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Deleting the default promotes the most recently updated
        // remaining address so the book always has a default when non-empty.
        if ($wasDefault) {
            $next = CustomerAddress::query()->where('user_id', $userId)->orderByDesc('updated_at')->first();
            if ($next !== null) {
                $next->forceFill(['is_default' => true])->save();
            }
        }

        $this->audit->record(
            'customers.address.removed',
            ['module' => 'customers', 'resource_type' => 'customer_address', 'resource_id' => $id],
            null,
            ['user_id' => $userId]
        );
        $this->activity->record($userId, 'address_removed', 'Address removed');

        return new JsonResponse([
            'data' => ['object' => 'customer_address_deletion', 'deleted' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function makeDefault(Request $request, string $id): JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $userId = (string) $user->getAuthIdentifier();

        $address = $this->ownAddress($request, $userId, $id);
        if ($address instanceof JsonResponse) {
            return $address;
        }

        $this->clearDefaults($userId);
        $address->forceFill(['is_default' => true])->save();

        return new JsonResponse([
            'data' => self::serialize($address->fresh()),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function ownAddress(Request $request, string $userId, string $id): CustomerAddress|JsonResponse
    {
        $address = CustomerAddress::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($address === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Address not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return $address;
    }

    private function clearDefaults(string $userId): void
    {
        CustomerAddress::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(CustomerAddress $address): array
    {
        return [
            'id' => (string) $address->getKey(),
            'object' => 'customer_address',
            'label' => $address->label,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'geo' => $address->geo,
            'is_default' => $address->is_default,
        ];
    }
}
