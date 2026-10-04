<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Models\BuyerProfile;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared self-serve guards: machine principals are denied (key
 * administration and customer data are human-only surfaces), and the
 * caller's buyer profile is resolved (404 when the actor never created
 * one — login backfills, so this is the staff-actor edge only).
 */
abstract class CustomerSelfServiceController
{
    protected function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    protected function machineDenial(Request $request): ?JsonResponse
    {
        if ($request->user() instanceof \App\Platform\Identity\MachinePrincipal) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        return null;
    }

    /**
     * @return array{user: User, profile: BuyerProfile}|JsonResponse
     */
    protected function ownProfile(Request $request): array|JsonResponse
    {
        $user = $this->ownUser($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $profile = BuyerProfile::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereNull('deleted_at')
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Profile not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        return ['user' => $user, 'profile' => $profile];
    }

    protected function ownUser(Request $request): User|JsonResponse
    {
        if ($denied = $this->machineDenial($request)) {
            return $denied;
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        return $user;
    }

    protected function membershipId(): ?string
    {
        return app(TenantContext::class)->membershipId();
    }

    public static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || strlen($phone) < 4) {
            return $phone === null ? null : '****';
        }

        return substr($phone, 0, 3).'******'.substr($phone, -2);
    }

    public static function maskEmail(?string $email): ?string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);

        // Synthetic phone.local addresses (signup without email) are not
        // contact points — report them as absent, never as verified email.
        if ($domain === 'phone.local') {
            return null;
        }

        $shown = strlen($local) <= 1 ? '*' : $local[0].'***';

        return $shown.'@'.$domain;
    }
}
